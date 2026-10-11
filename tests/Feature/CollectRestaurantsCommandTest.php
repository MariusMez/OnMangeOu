<?php

namespace Tests\Feature;

use App\Contracts\MenuAnalyst;
use App\Contracts\PhotoClassifier;
use App\Enums\DishSource;
use App\Enums\PhotoKind;
use App\Exceptions\MenuAnalysisException;
use App\Jobs\CollectRestaurantMenu;
use App\Models\Analysis;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Collecte en mode démo : géographie, Google Places, tri des photos et lecture des cartes simulés. */
class CollectRestaurantsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['opticarte.mock' => true]);
        Storage::fake('local');
    }

    public function test_the_menus_of_a_postal_code_feed_the_dish_base(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 2));

        $this->artisan('opticarte:collect', ['--code-postal' => ['69001'], '--limit' => 2, '--sync' => true])
            ->expectsOutputToContain('Zone : Code postal 69001')
            ->expectsOutputToContain('fermé définitivement')
            ->assertSuccessful();

        $collected = Restaurant::query()->whereNotNull('collected_at')->orderBy('id')->get();
        $this->assertCount(2, $collected);
        $this->assertSame(7, Restaurant::query()->count());

        $restaurant = $collected->first();
        $this->assertSame(4, $restaurant->photos()->count());
        $this->assertSame(1, $restaurant->photos()->where('kind', PhotoKind::Menu)->count());
        $this->assertSame(17, $restaurant->dishes()->count());

        $dish = $restaurant->dishes()->first();
        $this->assertSame(DishSource::GooglePlaces, $dish->source);
        $this->assertNull($dish->analysis_id);
        $this->assertSame(['69001', '69', '84', 2026], [$dish->postal_code, $dish->departement_code, $dish->region_code, $dish->year]);
        $this->assertSame($restaurant->restaurant_type->value, $dish->restaurant_type);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_dry_run_lists_the_restaurants_without_recording_anything(): void
    {
        $this->artisan('opticarte:collect', ['--code-postal' => ['69001'], '--dry-run' => true])
            ->expectsOutputToContain('à collecter')
            ->assertSuccessful();

        $this->assertDatabaseCount('restaurants', 0);
        $this->assertDatabaseCount('dishes', 0);
    }

    public function test_restaurants_are_sent_to_horizon_by_default(): void
    {
        Queue::fake([CollectRestaurantMenu::class]);

        $this->artisan('opticarte:collect', ['--code-postal' => ['69001'], '--limit' => 3, '--photos' => 5])
            ->expectsOutputToContain('en file')
            ->assertSuccessful();

        Queue::assertPushed(CollectRestaurantMenu::class, fn (CollectRestaurantMenu $job): bool => $job->maxPhotos === 5);
        Queue::assertPushedTimes(CollectRestaurantMenu::class, 3);
    }

    public function test_a_menu_photo_is_read_only_once_even_when_the_restaurant_is_collected_again(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 2));
        $collect = fn () => $this->artisan('opticarte:collect', ['--code-postal' => ['69001'], '--limit' => 1, '--sync' => true]);

        $collect()->assertSuccessful();
        $first = Restaurant::query()->whereNotNull('collected_at')->sole();

        $collect()->expectsOutputToContain('déjà collecté le 02/03/2026')->assertSuccessful();
        $this->assertSame(2, Restaurant::query()->whereNotNull('collected_at')->count());

        $this->travel(200)->days();
        $collect()->expectsOutputToContain('0 nouvelle(s) photo(s), 0 carte(s), 0 plat(s)')->assertSuccessful();

        $this->assertSame(17, $first->dishes()->count());
        $this->assertSame(4, $first->photos()->count());
        $this->assertTrue($first->fresh()->collected_at->isToday());
    }

    public function test_a_menu_photo_without_readable_prices_is_recorded_but_not_read(): void
    {
        $this->app->instance(PhotoClassifier::class, new class implements PhotoClassifier
        {
            public function classify(array $images): array
            {
                return array_fill(0, count($images), ['kind' => PhotoKind::Menu->value, 'has_prices' => false]);
            }
        });

        $this->artisan('opticarte:collect', ['--code-postal' => ['69001'], '--limit' => 1, '--sync' => true])
            ->expectsOutputToContain('4 nouvelle(s) photo(s), 0 carte(s), 0 plat(s)')
            ->assertSuccessful();

        $restaurant = Restaurant::query()->whereNotNull('collected_at')->sole();
        $this->assertSame(4, $restaurant->photos()->where('has_prices', false)->count());
        $this->assertDatabaseCount('dishes', 0);
    }

    public function test_a_failed_reading_leaves_the_photos_to_examine_next_time(): void
    {
        $this->app->instance(MenuAnalyst::class, new class implements MenuAnalyst
        {
            public function extract(array $images): array
            {
                throw new MenuAnalysisException('Lecture impossible.');
            }

            public function optimize(array $menu, array $omnes, Analysis $analysis): array
            {
                return [];
            }
        });

        $this->artisan('opticarte:collect', ['--code-postal' => ['69001'], '--limit' => 1, '--sync' => true])
            ->expectsOutputToContain('échec : Lecture impossible.')
            ->assertSuccessful();

        $this->assertDatabaseCount('restaurant_photos', 0);
        $this->assertSame(0, Restaurant::query()->whereNotNull('collected_at')->count());
    }

    public function test_menu_photos_are_kept_on_disk_only_when_asked(): void
    {
        config(['opticarte.collect.keep_photos' => true]);

        $this->artisan('opticarte:collect', ['--code-postal' => ['69001'], '--limit' => 1, '--sync' => true])->assertSuccessful();

        $menuPhoto = Restaurant::query()->whereNotNull('collected_at')->sole()->photos()->where('kind', PhotoKind::Menu)->sole();
        Storage::disk('local')->assertExists($menuPhoto->path);
    }

    public function test_an_invalid_zone_stops_the_command(): void
    {
        $this->artisan('opticarte:collect', ['--code-postal' => ['6900']])
            ->expectsOutputToContain('Code postal invalide : 6900')
            ->assertFailed();

        $this->artisan('opticarte:collect')
            ->expectsOutputToContain('Indiquez une zone')
            ->assertFailed();
    }

    public function test_the_real_google_client_needs_an_api_key(): void
    {
        config(['opticarte.mock' => false, 'services.google_places.key' => null]);
        Http::preventStrayRequests();

        $this->artisan('opticarte:collect', ['--lat' => '45.77', '--lng' => '4.83', '--rayon' => '1'])
            ->expectsOutputToContain('Clé Google Places absente')
            ->assertFailed();
    }
}

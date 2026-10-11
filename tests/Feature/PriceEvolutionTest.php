<?php

namespace Tests\Feature;

use App\Models\Dish;
use App\Models\Restaurant;
use App\Services\PriceObservatory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Évolution des prix d'une zone : à périmètre constant, par département, rayon ou région. */
class PriceEvolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['opticarte.mock' => true]);
        Storage::fake('local');
    }

    public function test_the_price_index_compares_the_same_dishes_in_the_same_restaurants(): void
    {
        $this->seedTwoYearsOfPrices();
        $observatory = app(PriceObservatory::class);

        $this->assertSame(
            [['from' => 2025, 'to' => 2026, 'pairs' => 3, 'median' => 10.0, 'mean' => 11.7]],
            $observatory->priceIndex(['departement_codes' => ['69']]),
        );
        $this->assertSame(15.0, $observatory->priceIndex()[0]['median']);
        $this->assertSame([], $observatory->priceIndex(['restaurant_ids' => []]));
    }

    public function test_restaurants_serving_the_same_dish_are_compared_and_counted_separately(): void
    {
        $corner = Restaurant::factory()->create();
        $station = Restaurant::factory()->create();

        foreach ([[$corner, 2025, 2.0], [$corner, 2026, 2.2], [$station, 2025, 3.0], [$station, 2026, 3.0]] as [$restaurant, $year, $price]) {
            Dish::factory()->collectedAt($restaurant)->create(['year' => $year, 'name' => 'Café', 'price' => $price]);
        }

        $observatory = app(PriceObservatory::class);

        $this->assertSame([['from' => 2025, 'to' => 2026, 'pairs' => 2, 'median' => 5.0, 'mean' => 5.0]], $observatory->priceIndex());
        $this->assertSame([2025 => 2, 2026 => 2], $observatory->coverage()->mapWithKeys(fn (object $row): array => [(int) $row->year => (int) $row->restaurants])->all());
    }

    public function test_the_restaurants_of_a_zone_are_counted_by_status_and_type(): void
    {
        $this->seedTwoYearsOfPrices();
        Restaurant::factory()->closed()->create();

        $this->assertSame([
            'total' => 3,
            'collected' => 2,
            'with_dishes' => 2,
            'closed' => 1,
            'by_type' => ['Bistrot / brasserie' => 3],
        ], app(PriceObservatory::class)->restaurants(['departement_codes' => ['69']]));
    }

    public function test_the_observatory_page_shows_the_evolution_of_a_departement(): void
    {
        $this->seedTwoYearsOfPrices();

        $this->get(route('stats.index', ['departement' => '69']))
            ->assertOk()
            ->assertSee('Évolution à périmètre constant')
            ->assertSee('2025 → 2026')
            ->assertSee('+10,0 %')
            ->assertSee('Médiane sur 3 plats comparés')
            ->assertSee('Restaurants collectés');
    }

    public function test_the_stats_command_reports_the_evolution_around_a_point(): void
    {
        $this->seedTwoYearsOfPrices();

        $this->artisan('opticarte:stats', ['--lat' => '45.7680', '--lng' => '4.8300', '--rayon' => '1'])
            ->expectsOutputToContain('1 km autour de 45.7680, 4.8300')
            ->expectsOutputToContain('+15,0 %')
            ->assertSuccessful();
    }

    public function test_the_stats_command_warns_when_the_zone_has_no_price(): void
    {
        $this->artisan('opticarte:stats', ['--departement' => 'Finistère'])
            ->expectsOutputToContain('Aucun prix relevé dans cette zone')
            ->assertSuccessful();
    }

    /**
     * Deux restaurants collectés dans le 1er arrondissement de Lyon (+10 % et +20 % sur les
     * plats repris), une analyse lyonnaise (+5 %) et un restaurant rennais (+50 %).
     */
    private function seedTwoYearsOfPrices(): void
    {
        $bouchon = Restaurant::factory()->collected()->locatedAt('69001', '69', '84', 45.7680, 4.8310)->create();
        $burger = Restaurant::factory()->collected()->locatedAt('69001', '69', '84', 45.7690, 4.8290)->create();
        $creperie = Restaurant::factory()->collected()->locatedAt('35000', '35', '53', 48.1110, -1.6800)->create();

        foreach ([[2025, 'Steak frites', 20], [2025, 'Tarte tatin', 10], [2026, 'Steak frites', 22], [2026, 'Tarte tatin', 12], [2026, 'Plat nouveau', 50]] as [$year, $name, $price]) {
            Dish::factory()->collectedAt($bouchon)->create(['year' => $year, 'name' => $name, 'price' => $price]);
        }

        Dish::factory()->collectedAt($burger)->create(['year' => 2026, 'name' => 'Burger', 'price' => 30]);

        foreach ([[2025, 14], [2026, 14.7]] as [$year, $price]) {
            Dish::factory()->create(['restaurant_name' => 'Chez Lucien', 'city' => 'Lyon', 'year' => $year, 'name' => 'Quenelle', 'price' => $price]);
        }

        foreach ([[2025, 10], [2026, 15]] as [$year, $price]) {
            Dish::factory()->collectedAt($creperie)->create(['year' => $year, 'name' => 'Galette complète', 'price' => $price]);
        }
    }
}

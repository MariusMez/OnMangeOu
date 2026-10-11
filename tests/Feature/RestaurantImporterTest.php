<?php

namespace Tests\Feature;

use App\Enums\RestaurantType;
use App\Models\Restaurant;
use App\Services\Collect\RestaurantImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_departement_named_by_google_wins_over_the_postal_code(): void
    {
        $attributes = app(RestaurantImporter::class)->attributes($this->place(['postal_code' => '01700', 'departement_name' => 'Rhône']));

        $this->assertSame(['69', '84'], [$attributes['departement_code'], $attributes['region_code']]);
    }

    public function test_a_place_outside_france_gets_no_departement(): void
    {
        $attributes = app(RestaurantImporter::class)->attributes($this->place(['postal_code' => '75011', 'country_code' => 'BE']));

        $this->assertNull($attributes['departement_code']);
    }

    public function test_a_type_refined_from_the_menu_is_not_reset_by_a_generic_google_type(): void
    {
        $restaurant = Restaurant::factory()->create(['google_place_id' => 'p1', 'restaurant_type' => RestaurantType::Creperie]);
        $importer = app(RestaurantImporter::class);

        $importer->upsert($importer->attributes($this->place(['id' => 'p1', 'name' => 'Ty Breizh'])));

        $this->assertSame(RestaurantType::Creperie, $restaurant->fresh()->restaurant_type);
        $this->assertSame('Ty Breizh', $restaurant->fresh()->name);
    }

    /**
     * Lieu Google générique (type « restaurant ») à Lyon, modifiable champ par champ.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{id: string, name: string, address: ?string, postal_code: ?string, city: ?string, departement_name: ?string, country_code: ?string, latitude: ?float, longitude: ?float, primary_type: ?string, types: list<string>, price_level: ?int, rating: ?float, rating_count: ?int, website_url: ?string, business_status: ?string}
     */
    private function place(array $overrides = []): array
    {
        return [
            'id' => 'p0',
            'name' => 'Chez Paulette',
            'address' => null,
            'postal_code' => '69001',
            'city' => 'Lyon',
            'departement_name' => null,
            'country_code' => 'FR',
            'latitude' => 45.77,
            'longitude' => 4.83,
            'primary_type' => 'restaurant',
            'types' => ['restaurant'],
            'price_level' => null,
            'rating' => null,
            'rating_count' => null,
            'website_url' => null,
            'business_status' => 'OPERATIONAL',
            ...$overrides,
        ];
    }
}

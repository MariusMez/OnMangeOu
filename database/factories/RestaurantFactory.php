<?php

namespace Database\Factories;

use App\Enums\RestaurantType;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    /**
     * Restaurant du 1er arrondissement de Lyon, ouvert et jamais collecté.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'google_place_id' => 'place-'.fake()->unique()->bothify('????????##'),
            'name' => fake()->company(),
            'address' => fake()->streetAddress().', 69001 Lyon',
            'postal_code' => '69001',
            'city' => 'Lyon',
            'departement_code' => '69',
            'region_code' => '84',
            'latitude' => fake()->randomFloat(6, 45.764, 45.772),
            'longitude' => fake()->randomFloat(6, 4.826, 4.836),
            'restaurant_type' => RestaurantType::Bistrot,
            'google_primary_type' => 'french_restaurant',
            'google_types' => ['french_restaurant', 'restaurant'],
            'price_level' => 2,
            'rating' => 4.3,
            'rating_count' => 250,
            'business_status' => 'OPERATIONAL',
            'collected_at' => null,
        ];
    }

    /** Restaurant situé ailleurs : code postal, département et coordonnées donnés. */
    public function locatedAt(string $postalCode, string $departementCode, string $regionCode, float $latitude, float $longitude): static
    {
        return $this->state(fn (): array => [
            'postal_code' => $postalCode,
            'departement_code' => $departementCode,
            'region_code' => $regionCode,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => ['business_status' => Restaurant::CLOSED_PERMANENTLY]);
    }

    public function collected(): static
    {
        return $this->state(fn (): array => ['collected_at' => now()]);
    }
}

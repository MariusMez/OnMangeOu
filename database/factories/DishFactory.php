<?php

namespace Database\Factories;

use App\Enums\DishCategory;
use App\Enums\DishSource;
use App\Enums\RestaurantType;
use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dish>
 */
class DishFactory extends Factory
{
    /**
     * Plat d'un bistrot lyonnais, relevé cette année par une analyse.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'analysis_id' => null,
            'restaurant_id' => null,
            'source' => DishSource::Analysis,
            'restaurant_name' => fake()->company(),
            'city' => 'Lyon',
            'postal_code' => '69001',
            'departement_code' => '69',
            'region_code' => '84',
            'restaurant_type' => RestaurantType::Bistrot->value,
            'year' => (int) now()->year,
            'section' => 'Plats',
            'name' => fake()->words(3, true),
            'description' => null,
            'category' => DishCategory::Plat->value,
            'price' => fake()->randomFloat(1, 12, 30),
            'currency' => 'EUR',
            'is_highlighted' => false,
        ];
    }

    /** Plat relevé par la collecte sur la fiche du restaurant donné, à son adresse. */
    public function collectedAt(Restaurant $restaurant): static
    {
        return $this->state(fn (): array => [
            'restaurant_id' => $restaurant->id,
            'source' => DishSource::GooglePlaces,
            'restaurant_name' => $restaurant->name,
            'city' => $restaurant->city ?? 'Inconnue',
            'postal_code' => $restaurant->postal_code,
            'departement_code' => $restaurant->departement_code,
            'region_code' => $restaurant->region_code,
            'restaurant_type' => $restaurant->restaurant_type->value,
        ]);
    }
}

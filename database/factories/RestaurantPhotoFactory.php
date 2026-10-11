<?php

namespace Database\Factories;

use App\Enums\PhotoKind;
use App\Models\Restaurant;
use App\Models\RestaurantPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RestaurantPhoto>
 */
class RestaurantPhotoFactory extends Factory
{
    /**
     * Photo de plat déjà examinée.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'thumbnail_hash' => sha1(fake()->unique()->uuid()),
            'kind' => PhotoKind::Food,
            'has_prices' => false,
            'attribution' => fake()->name(),
            'path' => null,
        ];
    }

    public function menu(): static
    {
        return $this->state(fn (): array => ['kind' => PhotoKind::Menu, 'has_prices' => true]);
    }
}

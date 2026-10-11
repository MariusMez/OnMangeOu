<?php

namespace App\Services;

use App\Models\Analysis;
use App\Models\Dish;

/** Alimente la base de plats (prix / année / ville / type) à partir d'une carte extraite. */
class DishImporter
{
    /** @param  array<string, mixed>  $menu */
    public function import(Analysis $analysis, array $menu): void
    {
        $rows = [];
        $year = $analysis->created_at->year;

        foreach ($menu['sections'] ?? [] as $section) {
            foreach ($section['items'] ?? [] as $item) {
                $rows[] = [
                    'analysis_id' => $analysis->id,
                    'restaurant_name' => $analysis->restaurant_name,
                    'city' => $analysis->city,
                    'restaurant_type' => $analysis->restaurant_type,
                    'year' => $year,
                    'section' => mb_substr((string) $section['name'], 0, 120),
                    'name' => mb_substr((string) $item['name'], 0, 200),
                    'description' => $item['description'] ?? null,
                    'category' => $item['category'] ?? 'autre',
                    'price' => is_numeric($item['price'] ?? null) ? (float) $item['price'] : null,
                    'currency' => mb_substr((string) ($menu['currency'] ?? 'EUR'), 0, 3) ?: 'EUR',
                    'is_highlighted' => (bool) ($item['is_highlighted'] ?? false),
                ];
            }
        }

        $analysis->dishes()->delete();
        foreach (array_chunk($rows, 100) as $chunk) {
            Dish::insert($chunk);
        }
    }
}

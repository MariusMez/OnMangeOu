<?php

namespace App\Services;

use App\Enums\DishSource;
use App\Models\Analysis;
use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;

/** Alimente la base de plats (prix / année / localisation / type) à partir d'une carte extraite. */
class DishImporter
{
    /** @param  array<string, mixed>  $menu */
    public function import(Analysis $analysis, array $menu): void
    {
        $rows = $this->rows($menu, [
            'analysis_id' => $analysis->id,
            'restaurant_id' => null,
            'source' => DishSource::Analysis->value,
            'restaurant_name' => $analysis->restaurant_name,
            'city' => $analysis->city,
            'postal_code' => null,
            'departement_code' => null,
            'region_code' => null,
            'restaurant_type' => $analysis->restaurant_type,
            'year' => $analysis->created_at->year,
        ]);

        $analysis->dishes()->delete();
        $this->insert($rows);
    }

    /**
     * Plats lus sur la fiche d'un restaurant collecté, datés de l'année en cours. Un plat déjà
     * relevé cette année dans ce restaurant est remplacé par sa nouvelle lecture (comparaison
     * insensible à la casse ASCII, comme LOWER() de SQLite).
     *
     * @param  array<string, mixed>  $menu
     */
    public function importCollected(Restaurant $restaurant, array $menu): int
    {
        $year = (int) now()->year;
        $rows = $this->rows($menu, [
            'analysis_id' => null,
            'restaurant_id' => $restaurant->id,
            'source' => DishSource::GooglePlaces->value,
            'restaurant_name' => mb_substr($restaurant->name, 0, 120),
            'city' => $restaurant->city ?? $restaurant->postal_code ?? 'Inconnue',
            'postal_code' => $restaurant->postal_code,
            'departement_code' => $restaurant->departement_code,
            'region_code' => $restaurant->region_code,
            'restaurant_type' => $restaurant->restaurant_type->value,
            'year' => $year,
        ]);

        DB::transaction(function () use ($restaurant, $rows, $year): void {
            $restaurant->dishes()
                ->where('year', $year)
                ->whereIn(DB::raw('LOWER(TRIM(name))'), array_map(fn (array $row): string => strtolower(trim($row['name'])), $rows))
                ->delete();

            $this->insert($rows);
        });

        return count($rows);
    }

    /**
     * @param  array<string, mixed>  $menu
     * @param  array<string, mixed>  $base  Colonnes communes à tous les plats de la carte
     * @return list<array<string, mixed>>
     */
    private function rows(array $menu, array $base): array
    {
        $rows = [];

        foreach ($menu['sections'] ?? [] as $section) {
            foreach ($section['items'] ?? [] as $item) {
                $rows[] = [
                    ...$base,
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

        return $rows;
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function insert(array $rows): void
    {
        foreach (array_chunk($rows, 100) as $chunk) {
            Dish::insert($chunk);
        }
    }
}

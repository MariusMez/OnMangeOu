<?php

namespace App\Services\Omnes;

use App\Enums\DishCategory;
use App\Support\Price;

/**
 * Principes d'Omnes : quatre règles classiques d'ingénierie de menu utilisées
 * en restauration pour construire une carte cohérente. Calculs déterministes,
 * sans LLM, par catégorie (entrées, plats, desserts, boissons).
 */
class OmnesAnalyzer
{
    private const array ANALYSED_CATEGORIES = [
        DishCategory::Entree,
        DishCategory::Plat,
        DishCategory::Dessert,
        DishCategory::Boisson,
    ];

    private const int MIN_ITEMS = 3;

    /**
     * @param  array<string, mixed>  $menu  Carte extraite (voir App\Claude\Schemas\ExtractedMenu)
     * @return array{categories: list<array<string, mixed>>, skipped: list<array<string, mixed>>, score: int, notes: list<string>}
     */
    public function analyze(array $menu): array
    {
        $groups = $this->groupPricedItems($menu);
        $categories = [];
        $skipped = [];

        foreach (self::ANALYSED_CATEGORIES as $category) {
            $items = $groups[$category->value] ?? [];

            if (count($items) < self::MIN_ITEMS) {
                if ($items !== []) {
                    $skipped[] = [
                        'category' => $category->value,
                        'label' => $category->label(),
                        'reason' => sprintf('%d plat(s) avec prix : il en faut au moins %d pour appliquer les principes.', count($items), self::MIN_ITEMS),
                    ];
                }

                continue;
            }

            $categories[] = $this->analyzeCategory($category, $items);
        }

        $evaluated = [];
        foreach ($categories as $report) {
            foreach ($report['principles'] as $principle) {
                if ($principle['status'] !== 'info') {
                    $evaluated[] = $principle['status'];
                }
            }
        }
        $ok = count(array_filter($evaluated, fn (string $status) => $status === 'ok'));
        $score = $evaluated === [] ? 0 : (int) round($ok / count($evaluated) * 100);

        return [
            'categories' => $categories,
            'skipped' => $skipped,
            'score' => $score,
            'notes' => $this->notes($menu),
        ];
    }

    /**
     * @param  list<array{name: string, price: float, highlighted: bool}>  $items
     * @return array<string, mixed>
     */
    public function analyzeCategory(DishCategory $category, array $items): array
    {
        $prices = array_map(fn (array $item) => $item['price'], $items);
        $min = min($prices);
        $max = max($prices);
        $count = count($prices);
        $mean = array_sum($prices) / $count;

        // Principe 1 : ouverture de gamme (prix max / prix min)
        $openingThreshold = $count < 9 ? 2.5 : 3;
        $openingRatio = $max / $min;

        // Principe 2 : dispersion des prix en trois zones de largeur égale
        $width = ($max - $min) / 3;
        $zoneOf = function (float $price) use ($width, $min): string {
            if ($width == 0) {
                return 'mid';
            }
            if ($price < $min + $width) {
                return 'low';
            }

            return $price < $min + 2 * $width ? 'mid' : 'high';
        };

        $zones = [
            'low' => ['from' => round($min, 2), 'to' => round($min + $width, 2), 'count' => 0, 'items' => []],
            'mid' => ['from' => round($min + $width, 2), 'to' => round($min + 2 * $width, 2), 'count' => 0, 'items' => []],
            'high' => ['from' => round($min + 2 * $width, 2), 'to' => round($max, 2), 'count' => 0, 'items' => []],
        ];
        foreach ($items as $item) {
            $zone = $zoneOf($item['price']);
            $zones[$zone]['count']++;
            $zones[$zone]['items'][] = $item['name'];
        }

        $principles = [];

        $openingOk = $openingRatio <= $openingThreshold;
        $principles[] = [
            'key' => 'ouverture',
            'title' => 'Ouverture de gamme',
            'status' => $openingOk ? 'ok' : 'warning',
            'value' => sprintf('×%s (max %s)', self::number($openingRatio), self::number($openingThreshold)),
            'detail' => $openingOk
                ? sprintf('Le prix le plus élevé (%s) reste dans un rapport acceptable avec le plus bas (%s).', Price::euro($max), Price::euro($min))
                : sprintf('Écart trop large entre %s et %s : le client ne sait plus se repérer. Resserrer la gamme ou créer une rubrique dédiée.', Price::euro($min), Price::euro($max)),
        ];

        $midDominates = $zones['mid']['count'] >= $zones['low']['count'] + $zones['high']['count'];
        $principles[] = [
            'key' => 'dispersion',
            'title' => 'Dispersion des prix',
            'status' => $width == 0 ? 'info' : ($midDominates ? 'ok' : 'warning'),
            'value' => sprintf('%d / %d / %d (bas / médian / haut)', $zones['low']['count'], $zones['mid']['count'], $zones['high']['count']),
            'detail' => $width == 0
                ? "Tous les plats sont au même prix : la dispersion ne s'applique pas."
                : ($midDominates
                    ? "La zone médiane concentre l'offre : la carte guide naturellement vers le cœur de gamme."
                    : sprintf('La zone médiane (%s–%s) devrait contenir au moins autant de plats que les zones basse et haute réunies.', Price::euro($zones['mid']['from']), Price::euro($zones['mid']['to']))),
        ];

        $principles[] = [
            'key' => 'rapport',
            'title' => 'Prix demandé / prix offert',
            'status' => 'info',
            'value' => sprintf('prix moyen offert %s', Price::euro($mean)),
            'detail' => 'Le ratio prix moyen demandé (ventes réelles) / prix moyen offert doit se situer entre 0,9 et 1. Il nécessite les données de ventes : à importer dans une version ultérieure.',
        ];

        $highlighted = array_values(array_filter($items, fn (array $item) => $item['highlighted']));
        $outsideMid = array_values(array_filter($highlighted, fn (array $item) => $zoneOf($item['price']) !== 'mid'));
        $principles[] = [
            'key' => 'promotion',
            'title' => 'Promotion (plats mis en avant)',
            'status' => $highlighted === [] ? 'info' : ($outsideMid === [] ? 'ok' : 'warning'),
            'value' => $highlighted === []
                ? 'aucun plat mis en avant'
                : sprintf('%d mis en avant, %d hors zone médiane', count($highlighted), count($outsideMid)),
            'detail' => $highlighted === []
                ? 'Aucune suggestion ni plat du jour repéré : mettre en avant un plat rentable situé dans la zone médiane.'
                : ($outsideMid === []
                    ? 'Les plats mis en avant sont dans la zone médiane : la promotion est cohérente avec la gamme.'
                    : sprintf('Plats promus hors zone médiane : %s. Une promotion doit porter sur le cœur de gamme.', implode(', ', array_column($outsideMid, 'name')))),
        ];

        sort($prices);
        $middle = intdiv($count, 2);
        $median = $count % 2 === 0 ? ($prices[$middle - 1] + $prices[$middle]) / 2 : $prices[$middle];

        return [
            'category' => $category->value,
            'label' => $category->label(),
            'count' => count($items),
            'priced_count' => $count,
            'min' => round($min, 2),
            'max' => round($max, 2),
            'mean' => round($mean, 2),
            'median' => round($median, 2),
            'opening_ratio' => round($openingRatio, 2),
            'opening_threshold' => $openingThreshold,
            'zones' => $zones,
            'principles' => $principles,
        ];
    }

    /**
     * @param  array<string, mixed>  $menu
     * @return array<string, list<array{name: string, price: float, highlighted: bool}>>
     */
    public function groupPricedItems(array $menu): array
    {
        $groups = [];
        foreach ($menu['sections'] ?? [] as $section) {
            foreach ($section['items'] ?? [] as $item) {
                $price = $item['price'] ?? null;
                if (! is_numeric($price) || (float) $price <= 0) {
                    continue;
                }
                $groups[$item['category'] ?? DishCategory::Autre->value][] = [
                    'name' => (string) $item['name'],
                    'price' => (float) $price,
                    'highlighted' => (bool) ($item['is_highlighted'] ?? false),
                ];
            }
        }

        return $groups;
    }

    /**
     * @param  array<string, mixed>  $menu
     * @return list<string>
     */
    private function notes(array $menu): array
    {
        $notes = [];
        $total = 0;
        $unpriced = 0;
        $bigSections = [];
        foreach ($menu['sections'] ?? [] as $section) {
            $items = $section['items'] ?? [];
            $total += count($items);
            $unpriced += count(array_filter($items, fn (array $item) => ($item['price'] ?? null) === null));
            if (count($items) > 7) {
                $bigSections[] = $section['name'];
            }
        }
        if ($unpriced > 0) {
            $notes[] = sprintf('%d plat(s) sur %d sans prix lisible : ils sont exclus des calculs.', $unpriced, $total);
        }
        if ($bigSections !== []) {
            $notes[] = sprintf('Rubriques de plus de 7 plats (%s) : au-delà, le choix devient anxiogène et dilue les ventes.', implode(', ', $bigSections));
        }

        return $notes;
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ''), '0'), ',');
    }
}

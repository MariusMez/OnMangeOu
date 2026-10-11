<?php

namespace App\Services;

use App\Models\Analysis;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Support\France;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Statistiques de prix construites sur la base de plats (analyses et collectes Google Places).
 *
 * Les filtres sont un tableau array{city?: ?string, type?: ?string, postal_codes?: list<string>,
 * departement_codes?: list<string>, region_code?: ?string, restaurant_ids?: list<int>} ; une clé
 * restaurant_ids vide (rayon sans restaurant collecté) ne retient aucun plat.
 */
class PriceObservatory
{
    /**
     * Nombre de restaurants d'un groupe de plats. Un plat vient soit d'un restaurant collecté
     * (restaurant_id), soit d'une analyse (analysis_id) : la somme des deux comptes distincts
     * évite une concaténation, car `||` n'en est pas une en MySQL.
     */
    private const string RESTAURANT_COUNT = 'COUNT(DISTINCT restaurant_id) + COUNT(DISTINCT analysis_id)';

    /**
     * Un restaurant est un couple (nom, ville) distinct, sans tenir compte de la casse. Il est
     * compté par sous-requête, car `||` n'est pas une concaténation en MySQL.
     *
     * @return array{analyses: int, dishes: int, restaurants: int, collected: int}
     */
    public function summary(): array
    {
        return [
            'analyses' => Analysis::query()->where('status', 'done')->count(),
            'dishes' => Dish::query()->count(),
            'restaurants' => DB::query()
                ->fromSub(
                    Dish::query()->selectRaw('LOWER(restaurant_name) AS restaurant, LOWER(city) AS city')->distinct(),
                    'restaurants',
                )
                ->count(),
            'collected' => Restaurant::query()->whereNotNull('collected_at')->count(),
        ];
    }

    /** @return array{cities: list<string>, types: list<string>, departements: array<string, string>, regions: array<string, string>} */
    public function filters(): array
    {
        $departements = [];
        $regions = [];

        foreach (Dish::query()->whereNotNull('departement_code')->distinct()->orderBy('departement_code')->pluck('departement_code') as $code) {
            $departements[$code] = $code.' · '.(France::departementName($code) ?? $code);
        }

        foreach (Dish::query()->whereNotNull('region_code')->distinct()->pluck('region_code') as $code) {
            $regions[$code] = France::regionName($code) ?? $code;
        }

        asort($regions);

        return [
            'cities' => Dish::query()->distinct()->orderBy('city')->pluck('city')->all(),
            'types' => Dish::query()->distinct()->orderBy('restaurant_type')->pluck('restaurant_type')->all(),
            'departements' => $departements,
            'regions' => $regions,
        ];
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return Collection<int, object>
     */
    public function priceStats(array $filter = []): Collection
    {
        return $this->priced($filter)
            ->select('category', 'city', 'restaurant_type', 'year')
            ->selectRaw('COUNT(*) AS dishes')
            ->selectRaw(self::RESTAURANT_COUNT.' AS restaurants')
            ->selectRaw('ROUND(AVG(price), 2) AS avg_price')
            ->selectRaw('MIN(price) AS min_price')
            ->selectRaw('MAX(price) AS max_price')
            ->groupBy('category', 'city', 'restaurant_type', 'year')
            ->orderByDesc('year')
            ->orderBy('city')
            ->orderBy('restaurant_type')
            ->orderBy('category')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return Collection<string, Collection<int, object>> Lignes groupées par catégorie
     */
    public function yearTrend(array $filter = []): Collection
    {
        return $this->priced($filter)
            ->select('category', 'year')
            ->selectRaw('COUNT(*) AS dishes')
            ->selectRaw('ROUND(AVG(price), 2) AS avg_price')
            ->groupBy('category', 'year')
            ->orderBy('category')
            ->orderBy('year')
            ->get()
            ->groupBy('category');
    }

    /**
     * Plats et restaurants relevés chaque année dans le périmètre.
     *
     * @param  array<string, mixed>  $filter
     * @return Collection<int, object>
     */
    public function coverage(array $filter = []): Collection
    {
        return $this->priced($filter)
            ->select('year')
            ->selectRaw('COUNT(*) AS dishes')
            ->selectRaw(self::RESTAURANT_COUNT.' AS restaurants')
            ->groupBy('year')
            ->orderBy('year')
            ->get();
    }

    /**
     * Évolution des prix à périmètre constant : pour chaque paire d'années consécutives, variation
     * des plats relevés les deux années dans le même restaurant (médiane et moyenne, en %).
     * Contrairement au prix moyen, elle ne dépend pas des plats ou restaurants entrés ou sortis.
     *
     * @param  array<string, mixed>  $filter
     * @return list<array{from: int, to: int, pairs: int, median: ?float, mean: ?float}>
     */
    public function priceIndex(array $filter = []): array
    {
        $prices = [];

        $rows = $this->priced($filter)
            ->select('restaurant_id', 'restaurant_name', 'city', 'name', 'year', 'price')
            ->toBase()
            ->get();

        foreach ($rows as $row) {
            $restaurantKey = $row->restaurant_id !== null
                ? 'r'.$row->restaurant_id
                : 'a'.mb_strtolower(trim($row->restaurant_name)).'|'.mb_strtolower(trim($row->city));

            $prices[$restaurantKey.'#'.mb_strtolower(trim($row->name))][(int) $row->year][] = (float) $row->price;
        }

        $years = array_values(array_unique(array_map(fn (object $row): int => (int) $row->year, $rows->all())));
        sort($years);
        $index = [];

        for ($i = 1; $i < count($years); $i++) {
            [$from, $to] = [$years[$i - 1], $years[$i]];
            $changes = [];

            foreach ($prices as $byYear) {
                if (isset($byYear[$from], $byYear[$to])) {
                    $before = array_sum($byYear[$from]) / count($byYear[$from]);
                    $after = array_sum($byYear[$to]) / count($byYear[$to]);

                    if ($before > 0) {
                        $changes[] = ($after / $before - 1) * 100;
                    }
                }
            }

            $index[] = [
                'from' => $from,
                'to' => $to,
                'pairs' => count($changes),
                'median' => $changes === [] ? null : round($this->median($changes), 1),
                'mean' => $changes === [] ? null : round(array_sum($changes) / count($changes), 1),
            ];
        }

        return $index;
    }

    /**
     * Restaurants collectés du périmètre : effectifs, fermetures et répartition par type.
     *
     * @param  array<string, mixed>  $filter
     * @return array{total: int, collected: int, with_dishes: int, closed: int, by_type: array<string, int>}
     */
    public function restaurants(array $filter = []): array
    {
        return [
            'total' => $this->restaurantQuery($filter)->count(),
            'collected' => $this->restaurantQuery($filter)->whereNotNull('collected_at')->count(),
            'with_dishes' => $this->restaurantQuery($filter)->whereHas('dishes')->count(),
            'closed' => $this->restaurantQuery($filter)->where('business_status', Restaurant::CLOSED_PERMANENTLY)->count(),
            'by_type' => $this->restaurantQuery($filter)
                ->toBase()
                ->select('restaurant_type')
                ->selectRaw('COUNT(*) AS n')
                ->groupBy('restaurant_type')
                ->orderByDesc('n')
                ->pluck('n', 'restaurant_type')
                ->map(fn (mixed $count): int => (int) $count)
                ->all(),
        ];
    }

    /** @return Collection<int, object> */
    public function topDishes(int $limit = 15): Collection
    {
        return Dish::query()
            ->whereNotNull('price')
            ->selectRaw('LOWER(TRIM(name)) AS name')
            ->addSelect('category')
            ->selectRaw('COUNT(*) AS occurrences')
            ->selectRaw('ROUND(AVG(price), 2) AS avg_price')
            ->groupBy(DB::raw('LOWER(TRIM(name))'), 'category')
            ->having('occurrences', '>', 1)
            ->orderByDesc('occurrences')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<Dish>
     */
    private function priced(array $filter): Builder
    {
        return Dish::query()
            ->whereNotNull('price')
            ->when($filter['city'] ?? null, fn (Builder $query, string $city) => $query->where('city', $city))
            ->when($filter['type'] ?? null, fn (Builder $query, string $type) => $query->where('restaurant_type', $type))
            ->when($filter['postal_codes'] ?? null, fn (Builder $query, array $codes) => $query->whereIn('postal_code', $codes))
            ->when($filter['departement_codes'] ?? null, fn (Builder $query, array $codes) => $query->whereIn('departement_code', $codes))
            ->when($filter['region_code'] ?? null, fn (Builder $query, string $code) => $query->where('region_code', $code))
            ->when(array_key_exists('restaurant_ids', $filter), fn (Builder $query) => $query->whereIn('restaurant_id', $filter['restaurant_ids']));
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<Restaurant>
     */
    private function restaurantQuery(array $filter): Builder
    {
        return Restaurant::query()
            ->when($filter['city'] ?? null, fn (Builder $query, string $city) => $query->where('city', $city))
            ->when($filter['type'] ?? null, fn (Builder $query, string $type) => $query->where('restaurant_type', $type))
            ->when($filter['postal_codes'] ?? null, fn (Builder $query, array $codes) => $query->whereIn('postal_code', $codes))
            ->when($filter['departement_codes'] ?? null, fn (Builder $query, array $codes) => $query->whereIn('departement_code', $codes))
            ->when($filter['region_code'] ?? null, fn (Builder $query, string $code) => $query->where('region_code', $code))
            ->when(array_key_exists('restaurant_ids', $filter), fn (Builder $query) => $query->whereIn('id', $filter['restaurant_ids']));
    }

    /** @param  non-empty-list<float>  $values */
    private function median(array $values): float
    {
        sort($values);
        $middle = intdiv(count($values), 2);

        return count($values) % 2 === 1 ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
    }
}

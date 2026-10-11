<?php

namespace App\Services;

use App\Models\Analysis;
use App\Models\Dish;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Statistiques de prix construites sur la base de plats alimentée par les analyses. */
class PriceObservatory
{
    /** @return array{analyses: int, dishes: int, restaurants: int} */
    public function summary(): array
    {
        return [
            'analyses' => Analysis::query()->where('status', 'done')->count(),
            'dishes' => Dish::query()->count(),
            'restaurants' => (int) Dish::query()
                ->selectRaw('COUNT(DISTINCT LOWER(restaurant_name) || \'|\' || LOWER(city)) AS n')
                ->value('n'),
        ];
    }

    /** @return array{cities: list<string>, types: list<string>} */
    public function filters(): array
    {
        return [
            'cities' => Dish::query()->distinct()->orderBy('city')->pluck('city')->all(),
            'types' => Dish::query()->distinct()->orderBy('restaurant_type')->pluck('restaurant_type')->all(),
        ];
    }

    /** @return Collection<int, object> */
    public function priceStats(?string $city, ?string $type): Collection
    {
        return $this->priced($city, $type)
            ->select('category', 'city', 'restaurant_type', 'year')
            ->selectRaw('COUNT(*) AS dishes')
            ->selectRaw('COUNT(DISTINCT analysis_id) AS restaurants')
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

    /** @return Collection<string, Collection<int, object>> Lignes groupées par catégorie */
    public function yearTrend(?string $city, ?string $type): Collection
    {
        return $this->priced($city, $type)
            ->select('category', 'year')
            ->selectRaw('COUNT(*) AS dishes')
            ->selectRaw('ROUND(AVG(price), 2) AS avg_price')
            ->groupBy('category', 'year')
            ->orderBy('category')
            ->orderBy('year')
            ->get()
            ->groupBy('category');
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

    /** @return Builder<Dish> */
    private function priced(?string $city, ?string $type): Builder
    {
        return Dish::query()
            ->whereNotNull('price')
            ->when($city, fn (Builder $query) => $query->where('city', $city))
            ->when($type, fn (Builder $query) => $query->where('restaurant_type', $type));
    }
}

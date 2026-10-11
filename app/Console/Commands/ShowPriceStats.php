<?php

namespace App\Console\Commands;

use App\Enums\DishCategory;
use App\Exceptions\CollectException;
use App\Services\Geo\Zones;
use App\Services\PriceObservatory;
use App\Support\Price;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('opticarte:stats
    {--code-postal=* : Code(s) postal(aux), ex. 69001}
    {--commune= : Nom ou code INSEE d\'une commune}
    {--departement= : Code ou nom du département}
    {--region= : Code INSEE ou nom de la région}
    {--lat= : Latitude du centre, avec --lng et --rayon}
    {--lng= : Longitude du centre, avec --lat et --rayon}
    {--rayon= : Rayon en km autour du centre (restaurants collectés uniquement)}
    {--type= : Type de restaurant, ex. « Bistrot / brasserie »}')]
#[Description('Évolution des prix et des restaurants d\'une zone française (base de plats OptiCarte)')]
class ShowPriceStats extends Command
{
    public function handle(Zones $zones, PriceObservatory $observatory): int
    {
        $criteria = $this->criteria();
        $label = 'Toute la base';
        $filter = [];

        if ($this->hasZone($criteria)) {
            try {
                $zone = $zones->resolve($criteria);
            } catch (CollectException $exception) {
                $this->components->error($exception->getMessage());

                return self::FAILURE;
            }

            $label = $zone['label'];
            $filter = $zones->dishFilter($zone);
        }

        if (filled($this->option('type'))) {
            $filter['type'] = (string) $this->option('type');
            $label .= ' · '.$filter['type'];
        }

        $this->components->info("Zone : {$label}");

        $coverage = $observatory->coverage($filter);

        if ($coverage->isEmpty()) {
            $this->components->warn('Aucun prix relevé dans cette zone. Lancez d\'abord php artisan opticarte:collect.');
        } else {
            $this->line('  <options=bold>Prix moyen par catégorie et par année</>');
            $this->table(['Catégorie', ...$coverage->pluck('year')->all()], $this->trendRows($observatory->yearTrend($filter), $coverage->pluck('year')->all()));

            $this->line('  <options=bold>Évolution à périmètre constant</> (mêmes plats, mêmes restaurants)');
            $index = $observatory->priceIndex($filter);

            if ($index === []) {
                $this->components->warn('Il faut des relevés sur au moins deux années pour mesurer l\'évolution.');
            } else {
                $this->table(['Période', 'Plats comparés', 'Variation médiane', 'Variation moyenne'], array_map(fn (array $period): array => [
                    "{$period['from']} → {$period['to']}",
                    $period['pairs'],
                    $this->percent($period['median']),
                    $this->percent($period['mean']),
                ], $index));
            }

            $this->line('  <options=bold>Relevés par année</>');
            $this->table(['Année', 'Restaurants', 'Plats'], $coverage->map(fn (object $row): array => [$row->year, $row->restaurants, $row->dishes])->all());
        }

        $restaurants = $observatory->restaurants($filter);

        if ($restaurants['total'] > 0) {
            $this->line('  <options=bold>Restaurants collectés</>');
            $this->components->twoColumnDetail('Référencés', (string) $restaurants['total']);
            $this->components->twoColumnDetail('Avec une carte lue', (string) $restaurants['with_dishes']);
            $this->components->twoColumnDetail('Fermés définitivement', (string) $restaurants['closed']);

            foreach ($restaurants['by_type'] as $type => $count) {
                $this->components->twoColumnDetail("  {$type}", (string) $count);
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param  iterable<string, iterable<int, object>>  $trend
     * @param  list<int>  $years
     * @return list<list<string>>
     */
    private function trendRows(iterable $trend, array $years): array
    {
        $rows = [];

        foreach ($trend as $category => $points) {
            $byYear = [];

            foreach ($points as $point) {
                $byYear[(int) $point->year] = (float) $point->avg_price;
            }

            $row = [DishCategory::labelFor($category)];
            $previous = null;

            foreach ($years as $year) {
                $price = $byYear[(int) $year] ?? null;
                $cell = $price === null ? '—' : Price::euro($price);

                if ($price !== null && $previous !== null && $previous > 0) {
                    $cell .= ' ('.$this->percent(($price / $previous - 1) * 100).')';
                }

                $row[] = $cell;
                $previous = $price ?? $previous;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function percent(?float $value): string
    {
        return $value === null ? '—' : ($value >= 0 ? '+' : '').number_format($value, 1, ',', ' ').' %';
    }

    /** @return array{postal_codes: list<string>, commune: ?string, departement: ?string, region: ?string, latitude: ?float, longitude: ?float, radius_km: ?float} */
    private function criteria(): array
    {
        $number = fn (?string $value): ?float => is_numeric($value) ? (float) $value : null;

        return [
            'postal_codes' => array_values($this->option('code-postal')),
            'commune' => $this->option('commune'),
            'departement' => $this->option('departement'),
            'region' => $this->option('region'),
            'latitude' => $number($this->option('lat')),
            'longitude' => $number($this->option('lng')),
            'radius_km' => $number($this->option('rayon')),
        ];
    }

    /** @param  array<string, mixed>  $criteria */
    private function hasZone(array $criteria): bool
    {
        return array_filter($criteria, fn (mixed $value): bool => $value !== null && $value !== []) !== [];
    }
}

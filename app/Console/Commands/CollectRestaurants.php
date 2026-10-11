<?php

namespace App\Console\Commands;

use App\Contracts\PlacesClient;
use App\Exceptions\CollectException;
use App\Jobs\CollectRestaurantMenu;
use App\Models\Restaurant;
use App\Services\Collect\MenuCollector;
use App\Services\Collect\RestaurantImporter;
use App\Services\Geo\Zones;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('opticarte:collect
    {--code-postal=* : Code(s) postal(aux), ex. 69001 (option répétable ou liste séparée par des virgules)}
    {--commune= : Nom ou code INSEE d\'une commune, ex. Rennes ou 35238}
    {--departement= : Code ou nom du département, ex. 69 ou Rhône}
    {--region= : Code INSEE ou nom de la région, ex. 53 ou Bretagne}
    {--lat= : Latitude du centre, avec --lng et --rayon}
    {--lng= : Longitude du centre, avec --lat et --rayon}
    {--rayon= : Rayon en km autour du centre (code postal, commune ou --lat / --lng)}
    {--limit=20 : Nombre maximum de restaurants à collecter}
    {--photos=10 : Photos examinées par restaurant (10 au plus)}
    {--max-recherches=30 : Nombre maximum de zones de recherche Google Places (3 pages de 20 résultats au plus chacune)}
    {--refresh= : Ne recollecte pas un restaurant collecté depuis moins de N jours (180 par défaut)}
    {--force : Recollecte même les restaurants récemment collectés}
    {--dry-run : Liste les restaurants trouvés sans rien enregistrer, télécharger ni envoyer à l\'IA}
    {--sync : Collecte immédiatement, sans passer par la file Horizon}')]
#[Description('Collecte les cartes des restaurants d\'une zone française (Google Places + IA) pour alimenter la base de plats')]
class CollectRestaurants extends Command
{
    public function handle(Zones $zones, PlacesClient $places, RestaurantImporter $importer, MenuCollector $collector): int
    {
        try {
            $zone = $zones->resolve($this->criteria());
        } catch (CollectException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $maxPhotos = max(1, min((int) config('opticarte.collect.max_photos'), (int) $this->option('photos')));
        $maxSearches = max(1, (int) $this->option('max-recherches'));
        $refreshDays = (int) ($this->option('refresh') ?? config('opticarte.collect.refresh_days'));
        $dryRun = (bool) $this->option('dry-run');
        $sync = (bool) $this->option('sync');

        $this->components->info(sprintf('Zone : %s%s', $zone['label'], $dryRun ? ' (simulation)' : ''));

        $seen = [];
        $rows = [];
        $selected = 0;
        $searches = 0;
        $found = 0;

        try {
            foreach ($zones->searchAreas($zone, (int) config('opticarte.collect.cell_radius_m')) as $area) {
                if ($selected >= $limit || $searches >= $maxSearches) {
                    break;
                }

                $searches++;

                foreach ($places->searchRestaurants($area, 60) as $place) {
                    if (isset($seen[$place['id']])) {
                        continue;
                    }

                    $seen[$place['id']] = true;
                    $attributes = $importer->attributes($place);

                    if (! $zones->contains($zone, $attributes)) {
                        continue;
                    }

                    $found++;
                    $restaurant = $dryRun
                        ? Restaurant::query()->firstWhere('google_place_id', $place['id'])
                        : $importer->upsert($attributes);
                    $status = $this->skipReason($attributes['business_status'], $restaurant, $refreshDays);

                    if ($status === null && $selected >= $limit) {
                        continue;
                    }

                    if ($status === null) {
                        $selected++;
                        $status = match (true) {
                            $dryRun => 'à collecter',
                            $sync => $this->collectNow($collector, $restaurant, $maxPhotos),
                            default => $this->queue($restaurant, $maxPhotos),
                        };
                    }

                    $rows[] = [
                        $attributes['name'],
                        trim(($attributes['postal_code'] ?? '').' '.($attributes['city'] ?? '')),
                        ($restaurant?->restaurant_type ?? $attributes['restaurant_type'])->label(),
                        $attributes['rating'] !== null ? number_format($attributes['rating'], 1, ',', '') : '—',
                        $status,
                    ];
                }
            }
        } catch (CollectException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($rows !== []) {
            $this->table(['Restaurant', 'Localisation', 'Type', 'Note', 'Statut'], $rows);
        }

        $this->components->twoColumnDetail('Zones de recherche Google Places', (string) $searches);
        $this->components->twoColumnDetail('Restaurants trouvés dans la zone', (string) $found);
        $this->components->twoColumnDetail($dryRun ? 'Restaurants à collecter' : ($sync ? 'Restaurants collectés' : 'Restaurants envoyés à Horizon'), (string) $selected);

        if (! $dryRun && ! $sync && $selected > 0) {
            $this->components->info('Suivi des collectes sur /horizon ; statistiques avec php artisan opticarte:stats.');
        }

        return self::SUCCESS;
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

    /** Raison de ne pas collecter ce restaurant, ou null s'il faut le collecter. */
    private function skipReason(?string $businessStatus, ?Restaurant $restaurant, int $refreshDays): ?string
    {
        if ($businessStatus === Restaurant::CLOSED_PERMANENTLY) {
            return 'fermé définitivement';
        }

        if ($restaurant !== null && ! $this->option('force') && ! $restaurant->needsCollection($refreshDays)) {
            return 'déjà collecté le '.$restaurant->collected_at->format('d/m/Y');
        }

        return null;
    }

    private function collectNow(MenuCollector $collector, Restaurant $restaurant, int $maxPhotos): string
    {
        try {
            $result = $collector->collect($restaurant, $maxPhotos);
        } catch (Throwable $exception) {
            report($exception);

            return 'échec : '.mb_strimwidth($exception->getMessage(), 0, 80, '…');
        }

        return sprintf('%d nouvelle(s) photo(s), %d carte(s), %d plat(s)', $result['new_photos'], $result['menu_photos'], $result['dishes']);
    }

    private function queue(Restaurant $restaurant, int $maxPhotos): string
    {
        CollectRestaurantMenu::dispatch($restaurant, $maxPhotos);

        return 'en file';
    }
}

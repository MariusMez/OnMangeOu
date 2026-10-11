<?php

namespace App\Services\Geo;

use App\Contracts\FrenchGeography;
use App\Exceptions\CollectException;
use App\Models\Restaurant;
use App\Support\France;
use App\Support\GeoMath;
use Generator;

/**
 * Zones géographiques françaises de collecte et de statistiques : résolution des critères
 * (code postal, commune, département, région, rayon), découpage en cercles de recherche,
 * appartenance d'un restaurant à la zone et filtre correspondant sur la base de plats.
 *
 * Une zone est un tableau array{label: string, postal_codes: list<string>, departement_codes: list<string>,
 * region_code: ?string, center: ?array{latitude: float, longitude: float}, radius_m: ?int,
 * circles: list<array{latitude: float, longitude: float, radius_m: int, label: string, postal_code: ?string}>}.
 */
class Zones
{
    private const float MAX_RADIUS_KM = 100.0;

    private const int MIN_COMMUNE_RADIUS_M = 500;

    private const int MAX_COMMUNE_RADIUS_M = 20_000;

    public function __construct(private readonly FrenchGeography $geography) {}

    /**
     * @param  array{postal_codes?: list<string>, commune?: ?string, departement?: ?string, region?: ?string, latitude?: ?float, longitude?: ?float, radius_km?: ?float}  $criteria
     * @return array{label: string, postal_codes: list<string>, departement_codes: list<string>, region_code: ?string, center: ?array{latitude: float, longitude: float}, radius_m: ?int, circles: list<array{latitude: float, longitude: float, radius_m: int, label: string, postal_code: ?string}>}
     */
    public function resolve(array $criteria): array
    {
        $postalCodes = $this->postalCodes($criteria['postal_codes'] ?? []);
        $commune = filled($criteria['commune'] ?? null) ? trim($criteria['commune']) : null;
        $latitude = $criteria['latitude'] ?? null;
        $longitude = $criteria['longitude'] ?? null;
        $radiusKm = $criteria['radius_km'] ?? null;

        if (($latitude === null) !== ($longitude === null)) {
            throw new CollectException('Indiquez à la fois --lat et --lng.');
        }

        if ($radiusKm !== null) {
            return $this->circleZone($radiusKm, $latitude, $longitude, $postalCodes, $commune);
        }

        if ($latitude !== null) {
            throw new CollectException('Précisez un rayon (--rayon, en km) autour du point --lat / --lng.');
        }

        if ($postalCodes !== []) {
            return $this->postalCodeZone($postalCodes);
        }

        if ($commune !== null) {
            $found = $this->geography->commune($commune) ?? throw new CollectException("Commune introuvable : {$commune}.");

            return $this->zone(
                label: "Commune de {$found['name']} ({$found['code']})",
                communes: [$found],
                postalCodes: $found['postal_codes'],
                departementCodes: [$found['departement_code']],
            );
        }

        if (filled($criteria['departement'] ?? null)) {
            $code = France::findDepartement($criteria['departement']) ?? throw new CollectException("Département inconnu : {$criteria['departement']}.");

            return $this->zone(
                label: 'Département '.France::departementName($code)." ({$code})",
                communes: $this->geography->communesOfDepartement($code),
                departementCodes: [$code],
            );
        }

        if (filled($criteria['region'] ?? null)) {
            $code = France::findRegion($criteria['region']) ?? throw new CollectException("Région inconnue : {$criteria['region']}.");

            return $this->zone(
                label: 'Région '.France::regionName($code),
                communes: $this->geography->communesOfRegion($code),
                departementCodes: France::departementsOfRegion($code),
                regionCode: $code,
            );
        }

        throw new CollectException('Indiquez une zone : --code-postal, --commune, --departement, --region, ou --lat / --lng avec --rayon.');
    }

    /**
     * Cercles de recherche de la zone, pavés en cellules de $cellRadiusMeters : les communes les plus
     * peuplées d'abord, puis du centre vers la périphérie de chaque commune.
     *
     * @param  array{circles: list<array{latitude: float, longitude: float, radius_m: int, label: string, postal_code: ?string}>}  $zone
     * @return Generator<int, array{latitude: float, longitude: float, radius_m: int, label: string, postal_code: ?string}>
     */
    public function searchAreas(array $zone, int $cellRadiusMeters): Generator
    {
        foreach ($zone['circles'] as $circle) {
            foreach (GeoMath::tileCircle($circle['latitude'], $circle['longitude'], $circle['radius_m'], $cellRadiusMeters) as $cell) {
                yield [...$cell, 'label' => $circle['label'], 'postal_code' => $circle['postal_code']];
            }
        }
    }

    /**
     * Le restaurant (attributs : postal_code, departement_code, latitude, longitude) est-il dans la zone ?
     * Un restaurant sans département français est toujours exclu.
     *
     * @param  array{postal_codes: list<string>, departement_codes: list<string>, region_code: ?string, center: ?array{latitude: float, longitude: float}, radius_m: ?int}  $zone
     * @param  array{postal_code?: ?string, departement_code?: ?string, latitude?: ?float, longitude?: ?float}  $restaurant
     */
    public function contains(array $zone, array $restaurant): bool
    {
        $departement = $restaurant['departement_code'] ?? null;

        if ($departement === null) {
            return false;
        }

        if ($zone['center'] !== null && $zone['radius_m'] !== null) {
            return isset($restaurant['latitude'], $restaurant['longitude'])
                && GeoMath::distanceMeters($zone['center']['latitude'], $zone['center']['longitude'], $restaurant['latitude'], $restaurant['longitude']) <= $zone['radius_m'];
        }

        if ($zone['postal_codes'] !== []) {
            return in_array($restaurant['postal_code'] ?? null, $zone['postal_codes'], true);
        }

        if ($zone['region_code'] !== null) {
            return France::regionForDepartement($departement) === $zone['region_code'];
        }

        return $zone['departement_codes'] === [] || in_array($departement, $zone['departement_codes'], true);
    }

    /**
     * Filtre de la base de plats correspondant à la zone (voir PriceObservatory).
     *
     * @param  array{postal_codes: list<string>, departement_codes: list<string>, region_code: ?string, center: ?array{latitude: float, longitude: float}, radius_m: ?int}  $zone
     * @return array{postal_codes?: list<string>, departement_codes?: list<string>, region_code?: string, restaurant_ids?: list<int>}
     */
    public function dishFilter(array $zone): array
    {
        if ($zone['center'] !== null && $zone['radius_m'] !== null) {
            return ['restaurant_ids' => $this->restaurantsWithinRadius($zone['center'], $zone['radius_m'])];
        }

        if ($zone['postal_codes'] !== []) {
            return ['postal_codes' => $zone['postal_codes']];
        }

        if ($zone['region_code'] !== null) {
            return ['region_code' => $zone['region_code']];
        }

        return $zone['departement_codes'] === [] ? [] : ['departement_codes' => $zone['departement_codes']];
    }

    /**
     * @param  array{latitude: float, longitude: float}  $center
     * @return list<int>
     */
    private function restaurantsWithinRadius(array $center, int $radiusMeters): array
    {
        $box = GeoMath::boundingBox($center['latitude'], $center['longitude'], $radiusMeters);

        return Restaurant::query()
            ->whereBetween('latitude', [$box['low']['latitude'], $box['high']['latitude']])
            ->whereBetween('longitude', [$box['low']['longitude'], $box['high']['longitude']])
            ->get(['id', 'latitude', 'longitude'])
            ->filter(fn (Restaurant $restaurant): bool => GeoMath::distanceMeters($center['latitude'], $center['longitude'], $restaurant->latitude, $restaurant->longitude) <= $radiusMeters)
            ->pluck('id')
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $postalCodes
     * @return array{label: string, postal_codes: list<string>, departement_codes: list<string>, region_code: ?string, center: array{latitude: float, longitude: float}, radius_m: int, circles: list<array{latitude: float, longitude: float, radius_m: int, label: string, postal_code: ?string}>}
     */
    private function circleZone(float $radiusKm, ?float $latitude, ?float $longitude, array $postalCodes, ?string $commune): array
    {
        if ($radiusKm <= 0 || $radiusKm > self::MAX_RADIUS_KM) {
            throw new CollectException('Le rayon doit être compris entre 0 et '.(int) self::MAX_RADIUS_KM.' km.');
        }

        $postalCode = $postalCodes[0] ?? null;

        if ($latitude !== null && $longitude !== null) {
            $centerLabel = sprintf('%.4f, %.4f', $latitude, $longitude);
        } elseif ($postalCode !== null || $commune !== null) {
            $found = $postalCode !== null
                ? $this->mostPopulated($this->geography->communesByPostalCode($postalCode))
                : $this->geography->commune($commune);

            if ($found === null) {
                throw new CollectException('Centre introuvable pour '.($postalCode ?? $commune).'.');
            }

            [$latitude, $longitude, $centerLabel] = [$found['latitude'], $found['longitude'], $found['name']];
            $postalCode ??= $found['postal_codes'][0] ?? null;
        } else {
            throw new CollectException('Indiquez le centre du rayon : --code-postal, --commune, ou --lat / --lng.');
        }

        $radiusMeters = (int) round($radiusKm * 1000);
        $label = sprintf('%s km autour de %s', str_replace('.', ',', (string) $radiusKm), $centerLabel);

        return [
            'label' => $label,
            'postal_codes' => [],
            'departement_codes' => [],
            'region_code' => null,
            'center' => ['latitude' => $latitude, 'longitude' => $longitude],
            'radius_m' => $radiusMeters,
            'circles' => [['latitude' => $latitude, 'longitude' => $longitude, 'radius_m' => $radiusMeters, 'label' => $label, 'postal_code' => $postalCode]],
        ];
    }

    /**
     * @param  list<string>  $postalCodes
     * @return array{label: string, postal_codes: list<string>, departement_codes: list<string>, region_code: ?string, center: null, radius_m: null, circles: list<array{latitude: float, longitude: float, radius_m: int, label: string, postal_code: ?string}>}
     */
    private function postalCodeZone(array $postalCodes): array
    {
        $communes = [];

        foreach ($postalCodes as $postalCode) {
            $found = $this->geography->communesByPostalCode($postalCode);

            if ($found === []) {
                throw new CollectException("Aucune commune trouvée pour le code postal {$postalCode}.");
            }

            array_push($communes, ...$found);
        }

        return $this->zone(
            label: (count($postalCodes) > 1 ? 'Codes postaux ' : 'Code postal ').implode(', ', $postalCodes),
            communes: $communes,
            postalCodes: $postalCodes,
            departementCodes: array_values(array_unique(array_column($communes, 'departement_code'))),
        );
    }

    /**
     * @param  list<array{code: string, name: string, postal_codes: list<string>, departement_code: string, region_code: string, latitude: float, longitude: float, surface_km2: float, population: int}>  $communes
     * @param  list<string>  $postalCodes
     * @param  list<string>  $departementCodes
     * @return array{label: string, postal_codes: list<string>, departement_codes: list<string>, region_code: ?string, center: null, radius_m: null, circles: list<array{latitude: float, longitude: float, radius_m: int, label: string, postal_code: ?string}>}
     */
    private function zone(string $label, array $communes, array $postalCodes = [], array $departementCodes = [], ?string $regionCode = null): array
    {
        usort($communes, fn (array $a, array $b): int => $b['population'] <=> $a['population']);

        $circles = array_map(fn (array $commune): array => [
            'latitude' => $commune['latitude'],
            'longitude' => $commune['longitude'],
            'radius_m' => $this->communeRadius($commune['surface_km2']),
            'label' => $commune['name'],
            'postal_code' => array_values(array_intersect($commune['postal_codes'], $postalCodes))[0] ?? $commune['postal_codes'][0] ?? null,
        ], $communes);

        return [
            'label' => $label,
            'postal_codes' => array_values($postalCodes),
            'departement_codes' => array_values($departementCodes),
            'region_code' => $regionCode,
            'center' => null,
            'radius_m' => null,
            'circles' => $circles,
        ];
    }

    /** Rayon du disque de même surface que la commune, majoré de 15 % pour couvrir ses contours. */
    private function communeRadius(float $surfaceKm2): int
    {
        $radius = (int) round(sqrt(max($surfaceKm2, 0.01) / M_PI) * 1000 * 1.15);

        return max(self::MIN_COMMUNE_RADIUS_M, min(self::MAX_COMMUNE_RADIUS_M, $radius));
    }

    /**
     * @param  list<array{code: string, name: string, postal_codes: list<string>, departement_code: string, region_code: string, latitude: float, longitude: float, surface_km2: float, population: int}>  $communes
     * @return array{code: string, name: string, postal_codes: list<string>, departement_code: string, region_code: string, latitude: float, longitude: float, surface_km2: float, population: int}|null
     */
    private function mostPopulated(array $communes): ?array
    {
        usort($communes, fn (array $a, array $b): int => $b['population'] <=> $a['population']);

        return $communes[0] ?? null;
    }

    /**
     * @param  list<string>  $postalCodes
     * @return list<string>
     */
    private function postalCodes(array $postalCodes): array
    {
        $codes = [];

        foreach ($postalCodes as $postalCode) {
            foreach (preg_split('/[\s,;]+/', (string) $postalCode, flags: PREG_SPLIT_NO_EMPTY) as $code) {
                if (preg_match('/^\d{5}$/', $code) !== 1 || France::departementForPostalCode($code) === null) {
                    throw new CollectException("Code postal invalide : {$code} (5 chiffres, France uniquement).");
                }

                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }
}

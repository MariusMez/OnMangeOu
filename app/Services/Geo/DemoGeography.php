<?php

namespace App\Services\Geo;

use App\Contracts\FrenchGeography;
use App\Support\France;

/**
 * Mode démo : quelques communes réelles embarquées, et une commune fictive (centrée sur Lyon)
 * pour tout autre code postal, département ou région. Aucun appel réseau.
 */
class DemoGeography implements FrenchGeography
{
    /** @var list<array{code: string, name: string, postal_codes: list<string>, departement_code: string, region_code: string, latitude: float, longitude: float, surface_km2: float, population: int}> */
    private const array COMMUNES = [
        ['code' => '69381', 'name' => 'Lyon 1er Arrondissement', 'postal_codes' => ['69001'], 'departement_code' => '69', 'region_code' => '84', 'latitude' => 45.7701, 'longitude' => 4.8264, 'surface_km2' => 1.54, 'population' => 28717],
        ['code' => '69382', 'name' => 'Lyon 2e Arrondissement', 'postal_codes' => ['69002'], 'departement_code' => '69', 'region_code' => '84', 'latitude' => 45.7486, 'longitude' => 4.8272, 'surface_km2' => 3.19, 'population' => 30575],
        ['code' => '75111', 'name' => 'Paris 11e Arrondissement', 'postal_codes' => ['75011'], 'departement_code' => '75', 'region_code' => '11', 'latitude' => 48.8601, 'longitude' => 2.3816, 'surface_km2' => 3.65, 'population' => 138170],
        ['code' => '35238', 'name' => 'Rennes', 'postal_codes' => ['35000', '35200', '35700'], 'departement_code' => '35', 'region_code' => '53', 'latitude' => 48.1159, 'longitude' => -1.6884, 'surface_km2' => 50.33, 'population' => 230890],
    ];

    public function communesByPostalCode(string $postalCode): array
    {
        $communes = array_values(array_filter(self::COMMUNES, fn (array $commune): bool => in_array($postalCode, $commune['postal_codes'], true)));

        return $communes !== [] ? $communes : [$this->placeholder($postalCode, France::departementForPostalCode($postalCode) ?? '69')];
    }

    public function commune(string $codeOrName): ?array
    {
        $needle = mb_strtolower(trim($codeOrName));

        foreach (self::COMMUNES as $commune) {
            if ($commune['code'] === strtoupper($needle) || mb_strtolower($commune['name']) === $needle) {
                return $commune;
            }
        }

        return null;
    }

    public function communesOfDepartement(string $departementCode): array
    {
        $communes = array_values(array_filter(self::COMMUNES, fn (array $commune): bool => $commune['departement_code'] === $departementCode));

        return $communes !== [] ? $communes : [$this->placeholder(null, $departementCode)];
    }

    public function communesOfRegion(string $regionCode): array
    {
        $communes = array_values(array_filter(self::COMMUNES, fn (array $commune): bool => $commune['region_code'] === $regionCode));

        return $communes !== [] ? $communes : [$this->placeholder(null, France::departementsOfRegion($regionCode)[0] ?? '69')];
    }

    /** @return array{code: string, name: string, postal_codes: list<string>, departement_code: string, region_code: string, latitude: float, longitude: float, surface_km2: float, population: int} */
    private function placeholder(?string $postalCode, string $departementCode): array
    {
        $postalCode ??= str_pad($departementCode, 5, '0');

        return [
            'code' => $departementCode.'000',
            'name' => 'Commune de démonstration '.$postalCode,
            'postal_codes' => [$postalCode],
            'departement_code' => $departementCode,
            'region_code' => France::regionForDepartement($departementCode) ?? '84',
            'latitude' => 45.7640,
            'longitude' => 4.8357,
            'surface_km2' => 5.0,
            'population' => 10000,
        ];
    }
}

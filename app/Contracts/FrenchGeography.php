<?php

namespace App\Contracts;

/**
 * Découpage administratif français : communes (et arrondissements municipaux de Paris, Lyon et
 * Marseille) avec leur centre, leur surface et leurs codes postaux.
 *
 * Une commune est un tableau array{code: string, name: string, postal_codes: list<string>,
 * departement_code: string, region_code: string, latitude: float, longitude: float,
 * surface_km2: float, population: int}.
 */
interface FrenchGeography
{
    /**
     * Communes desservies par un code postal ; pour Paris, Lyon et Marseille, les arrondissements.
     *
     * @return list<array{code: string, name: string, postal_codes: list<string>, departement_code: string, region_code: string, latitude: float, longitude: float, surface_km2: float, population: int}>
     */
    public function communesByPostalCode(string $postalCode): array;

    /**
     * Commune par code INSEE ou par nom (la plus peuplée en cas d'homonymes).
     *
     * @return array{code: string, name: string, postal_codes: list<string>, departement_code: string, region_code: string, latitude: float, longitude: float, surface_km2: float, population: int}|null
     */
    public function commune(string $codeOrName): ?array;

    /**
     * @return list<array{code: string, name: string, postal_codes: list<string>, departement_code: string, region_code: string, latitude: float, longitude: float, surface_km2: float, population: int}>
     */
    public function communesOfDepartement(string $departementCode): array;

    /**
     * @return list<array{code: string, name: string, postal_codes: list<string>, departement_code: string, region_code: string, latitude: float, longitude: float, surface_km2: float, population: int}>
     */
    public function communesOfRegion(string $regionCode): array;
}

<?php

namespace App\Contracts;

/**
 * Annuaire de lieux (Google Places) : restaurants d'une zone et photos publiées sur leur fiche.
 *
 * Un lieu est un tableau array{id: string, name: string, address: ?string, postal_code: ?string,
 * city: ?string, departement_name: ?string, country_code: ?string, latitude: ?float, longitude: ?float,
 * primary_type: ?string, types: list<string>, price_level: ?int, rating: ?float, rating_count: ?int,
 * website_url: ?string, business_status: ?string}.
 */
interface PlacesClient
{
    /**
     * Restaurants situés dans le carré englobant le cercle donné (60 au plus).
     *
     * @param  array{latitude: float, longitude: float, radius_m: int, postal_code?: ?string}  $area
     * @return list<array{id: string, name: string, address: ?string, postal_code: ?string, city: ?string, departement_name: ?string, country_code: ?string, latitude: ?float, longitude: ?float, primary_type: ?string, types: list<string>, price_level: ?int, rating: ?float, rating_count: ?int, website_url: ?string, business_status: ?string}>
     */
    public function searchRestaurants(array $area, int $limit): array;

    /**
     * Photos actuelles d'un lieu (10 au plus). Les noms de photo expirent : ne jamais les conserver.
     *
     * @return list<array{name: string, width: int, height: int, attribution: ?string}>
     */
    public function photos(string $placeId): array;

    /** Contenu de l'image, à $maxWidthPx de large au plus. */
    public function photo(string $photoName, int $maxWidthPx): string;
}

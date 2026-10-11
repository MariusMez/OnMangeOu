<?php

namespace App\Services\Collect;

use App\Enums\RestaurantType;
use App\Models\Restaurant;
use App\Support\France;

/** Enregistre un lieu Google Places comme restaurant : localisation française et classification. */
class RestaurantImporter
{
    public function __construct(private readonly RestaurantTypeClassifier $types) {}

    /**
     * Attributs du restaurant. Le département vient du nom renvoyé par Google, sinon du code postal ;
     * un lieu hors de France n'a pas de département.
     *
     * @param  array{id: string, name: string, address: ?string, postal_code: ?string, city: ?string, departement_name: ?string, country_code: ?string, latitude: ?float, longitude: ?float, primary_type: ?string, types: list<string>, price_level: ?int, rating: ?float, rating_count: ?int, website_url: ?string, business_status: ?string}  $place
     * @return array<string, mixed>
     */
    public function attributes(array $place): array
    {
        $postalCode = preg_match('/^\d{5}$/', (string) $place['postal_code']) === 1 ? $place['postal_code'] : null;
        $departement = null;

        if (in_array($place['country_code'], [null, 'FR'], true)) {
            $departement = ($place['departement_name'] !== null ? France::findDepartement($place['departement_name']) : null)
                ?? France::departementForPostalCode($postalCode);
        }

        return [
            'google_place_id' => $place['id'],
            'name' => mb_substr($place['name'], 0, 160),
            'address' => $place['address'] !== null ? mb_substr($place['address'], 0, 255) : null,
            'postal_code' => $postalCode,
            'city' => $place['city'] !== null ? mb_substr($place['city'], 0, 120) : null,
            'departement_code' => $departement,
            'region_code' => France::regionForDepartement($departement),
            'latitude' => $place['latitude'],
            'longitude' => $place['longitude'],
            'restaurant_type' => $this->types->fromGoogle($place['primary_type'], $place['types'], $place['price_level']),
            'google_primary_type' => $place['primary_type'] !== null ? mb_substr($place['primary_type'], 0, 80) : null,
            'google_types' => $place['types'],
            'price_level' => $place['price_level'],
            'rating' => $place['rating'],
            'rating_count' => $place['rating_count'],
            'website_url' => $place['website_url'] !== null ? mb_substr($place['website_url'], 0, 500) : null,
            'business_status' => $place['business_status'] !== null ? mb_substr($place['business_status'], 0, 30) : null,
        ];
    }

    /**
     * Crée ou met à jour le restaurant ; un type déjà affiné par la lecture de la carte n'est pas
     * ramené à « Autre ».
     *
     * @param  array<string, mixed>  $attributes
     */
    public function upsert(array $attributes): Restaurant
    {
        $restaurant = Restaurant::query()->firstOrNew(['google_place_id' => $attributes['google_place_id']]);

        if ($restaurant->exists && $attributes['restaurant_type'] === RestaurantType::Autre) {
            unset($attributes['restaurant_type']);
        }

        $restaurant->fill($attributes)->save();

        return $restaurant;
    }
}

<?php

namespace App\Services\Places;

use App\Contracts\PlacesClient;
use App\Exceptions\CollectException;
use App\Support\GeoMath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Google Places API (New) : Text Search pour les restaurants d'une zone, Place Details pour les
 * photos d'un lieu et Place Photos pour leur contenu.
 */
class GooglePlacesClient implements PlacesClient
{
    private const string BASE_URL = 'https://places.googleapis.com/v1/';

    private const string SEARCH_FIELDS = 'places.id,places.displayName,places.formattedAddress,places.addressComponents,places.location,places.types,places.primaryType,places.priceLevel,places.rating,places.userRatingCount,places.websiteUri,places.businessStatus,nextPageToken';

    private const int PAGE_SIZE = 20;

    private const array PRICE_LEVELS = [
        'PRICE_LEVEL_FREE' => 0,
        'PRICE_LEVEL_INEXPENSIVE' => 1,
        'PRICE_LEVEL_MODERATE' => 2,
        'PRICE_LEVEL_EXPENSIVE' => 3,
        'PRICE_LEVEL_VERY_EXPENSIVE' => 4,
    ];

    public function searchRestaurants(array $area, int $limit): array
    {
        $body = [
            'textQuery' => 'restaurant',
            'includedType' => 'restaurant',
            'languageCode' => 'fr',
            'regionCode' => 'FR',
            'pageSize' => self::PAGE_SIZE,
            'locationRestriction' => [
                'rectangle' => GeoMath::boundingBox($area['latitude'], $area['longitude'], $area['radius_m']),
            ],
        ];
        $places = [];
        $pageToken = null;

        do {
            $payload = $this->send(fn (PendingRequest $request): Response => $request
                ->withHeaders(['X-Goog-FieldMask' => self::SEARCH_FIELDS])
                ->post('places:searchText', $pageToken === null ? $body : [...$body, 'pageToken' => $pageToken]));

            foreach ($payload['places'] ?? [] as $place) {
                $places[] = $this->place($place);
            }

            $pageToken = $payload['nextPageToken'] ?? null;
        } while ($pageToken !== null && count($places) < $limit);

        return array_slice($places, 0, $limit);
    }

    public function photos(string $placeId): array
    {
        $payload = $this->send(fn (PendingRequest $request): Response => $request
            ->withHeaders(['X-Goog-FieldMask' => 'photos'])
            ->get('places/'.rawurlencode($placeId)));

        return array_map(fn (array $photo): array => [
            'name' => (string) $photo['name'],
            'width' => (int) ($photo['widthPx'] ?? 0),
            'height' => (int) ($photo['heightPx'] ?? 0),
            'attribution' => isset($photo['authorAttributions'][0]['displayName'])
                ? mb_substr((string) $photo['authorAttributions'][0]['displayName'], 0, 255)
                : null,
        ], $payload['photos'] ?? []);
    }

    public function photo(string $photoName, int $maxWidthPx): string
    {
        $payload = $this->send(fn (PendingRequest $request): Response => $request
            ->get($photoName.'/media', ['maxWidthPx' => max(1, min(4800, $maxWidthPx)), 'skipHttpRedirect' => 'true']));

        $uri = $payload['photoUri'] ?? throw new CollectException("Google Places n'a pas renvoyé l'adresse de la photo {$photoName}.");

        try {
            return Http::connectTimeout(5)->timeout(60)->retry([500, 2000], 0)->get($uri)->throw()->body();
        } catch (ConnectionException|RequestException $exception) {
            throw new CollectException('Téléchargement de la photo impossible : '.$exception->getMessage(), previous: $exception);
        }
    }

    /**
     * @param  array<string, mixed>  $place
     * @return array{id: string, name: string, address: ?string, postal_code: ?string, city: ?string, departement_name: ?string, country_code: ?string, latitude: ?float, longitude: ?float, primary_type: ?string, types: list<string>, price_level: ?int, rating: ?float, rating_count: ?int, website_url: ?string, business_status: ?string}
     */
    private function place(array $place): array
    {
        $component = function (string $type, string $field = 'longText') use ($place): ?string {
            foreach ($place['addressComponents'] ?? [] as $component) {
                if (in_array($type, $component['types'] ?? [], true)) {
                    return isset($component[$field]) ? (string) $component[$field] : null;
                }
            }

            return null;
        };

        return [
            'id' => (string) $place['id'],
            'name' => (string) ($place['displayName']['text'] ?? 'Restaurant sans nom'),
            'address' => $place['formattedAddress'] ?? null,
            'postal_code' => $component('postal_code'),
            'city' => $component('locality') ?? $component('postal_town'),
            'departement_name' => $component('administrative_area_level_2'),
            'country_code' => $component('country', 'shortText'),
            'latitude' => isset($place['location']['latitude']) ? (float) $place['location']['latitude'] : null,
            'longitude' => isset($place['location']['longitude']) ? (float) $place['location']['longitude'] : null,
            'primary_type' => $place['primaryType'] ?? null,
            'types' => array_values(array_map('strval', $place['types'] ?? [])),
            'price_level' => self::PRICE_LEVELS[$place['priceLevel'] ?? ''] ?? null,
            'rating' => isset($place['rating']) ? (float) $place['rating'] : null,
            'rating_count' => isset($place['userRatingCount']) ? (int) $place['userRatingCount'] : null,
            'website_url' => $place['websiteUri'] ?? null,
            'business_status' => $place['businessStatus'] ?? null,
        ];
    }

    /**
     * Requête en lecture seule, relancée sur erreur réseau, quota (429) ou erreur serveur.
     *
     * @param  callable(PendingRequest): Response  $call
     * @return array<string, mixed>
     */
    private function send(callable $call): array
    {
        $key = config('services.google_places.key');

        if (blank($key)) {
            throw new CollectException('Clé Google Places absente : renseignez GOOGLE_PLACES_API_KEY dans .env (ou OPTICARTE_MOCK=true pour la démo).');
        }

        $request = Http::baseUrl(self::BASE_URL)
            ->withHeaders(['X-Goog-Api-Key' => $key])
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(30)
            ->retry([500, 2000, 5000], 0, fn (Throwable $exception): bool => $exception instanceof ConnectionException
                || ($exception instanceof RequestException
                    && ($exception->response->serverError() || $exception->response->status() === 429)), throw: false);

        try {
            $response = $call($request);
        } catch (ConnectionException $exception) {
            throw new CollectException('Google Places ne répond pas : '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw new CollectException(sprintf(
                'Google Places a refusé la requête (HTTP %d) : %s',
                $response->status(),
                $response->json('error.message') ?? $response->reason(),
            ));
        }

        return $response->json() ?? [];
    }
}

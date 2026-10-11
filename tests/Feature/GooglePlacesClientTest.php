<?php

namespace Tests\Feature;

use App\Exceptions\CollectException;
use App\Services\Places\GooglePlacesClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Client Google Places (New), réponses de l'API simulées. */
class GooglePlacesClientTest extends TestCase
{
    public function test_restaurants_are_searched_in_the_area_and_paginated(): void
    {
        config(['services.google_places.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake([
            'places.googleapis.com/v1/places:searchText' => Http::sequence()
                ->push(['places' => [$this->googlePlace('p1', 'Le Bouchon')], 'nextPageToken' => 'page-2'])
                ->push(['places' => [$this->googlePlace('p2', 'Sushi Kan')]]),
        ]);

        $places = app(GooglePlacesClient::class)->searchRestaurants(['latitude' => 45.77, 'longitude' => 4.83, 'radius_m' => 800], 60);

        $this->assertSame(['p1', 'p2'], array_column($places, 'id'));
        $this->assertSame([
            'id' => 'p1',
            'name' => 'Le Bouchon',
            'address' => '12 Rue Mercière, 69001 Lyon',
            'postal_code' => '69001',
            'city' => 'Lyon',
            'departement_name' => 'Rhône',
            'country_code' => 'FR',
            'latitude' => 45.7621,
            'longitude' => 4.8327,
            'primary_type' => 'french_restaurant',
            'types' => ['french_restaurant', 'restaurant'],
            'price_level' => 2,
            'rating' => 4.4,
            'rating_count' => 812,
            'website_url' => 'https://bouchon.example',
            'business_status' => 'OPERATIONAL',
        ], $places[0]);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('X-Goog-Api-Key', 'test-key')
            && str_contains($request->header('X-Goog-FieldMask')[0], 'places.addressComponents')
            && $request['includedType'] === 'restaurant'
            && $request['locationRestriction']['rectangle']['low']['latitude'] < 45.77
            && $request['locationRestriction']['rectangle']['high']['longitude'] > 4.83
            && ! isset($request['pageToken']));
        Http::assertSent(fn (Request $request): bool => ($request['pageToken'] ?? null) === 'page-2');
    }

    public function test_photos_are_fetched_fresh_and_downloaded_without_leaking_the_key(): void
    {
        config(['services.google_places.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake([
            'places.googleapis.com/v1/places/p1/photos/a1/media*' => Http::response(['photoUri' => 'https://lh3.googleusercontent.com/a1=s512']),
            'places.googleapis.com/v1/places/p1' => Http::response(['photos' => [
                ['name' => 'places/p1/photos/a1', 'widthPx' => 1200, 'heightPx' => 1600, 'authorAttributions' => [['displayName' => 'Marie D.']]],
            ]]),
            'lh3.googleusercontent.com/*' => Http::response('jpeg-bytes'),
        ]);
        $client = app(GooglePlacesClient::class);

        $photos = $client->photos('p1');
        $binary = $client->photo($photos[0]['name'], 512);

        $this->assertSame([['name' => 'places/p1/photos/a1', 'width' => 1200, 'height' => 1600, 'attribution' => 'Marie D.']], $photos);
        $this->assertSame('jpeg-bytes', $binary);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/media?')
            && (int) $request['maxWidthPx'] === 512
            && $request['skipHttpRedirect'] === 'true');
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'googleusercontent') && $request->hasHeader('X-Goog-Api-Key'));
    }

    public function test_a_missing_key_is_reported_before_any_request(): void
    {
        config(['services.google_places.key' => null]);
        Http::preventStrayRequests();

        $this->expectException(CollectException::class);
        $this->expectExceptionMessage('Clé Google Places absente');

        app(GooglePlacesClient::class)->photos('p1');
    }

    public function test_a_refusal_from_google_is_reported_with_its_reason(): void
    {
        config(['services.google_places.key' => 'bad-key']);
        Http::preventStrayRequests();
        Http::fake(['places.googleapis.com/v1/places/p1' => Http::response(['error' => ['code' => 403, 'message' => 'API key not valid.', 'status' => 'PERMISSION_DENIED']], 403)]);

        $this->expectException(CollectException::class);
        $this->expectExceptionMessage('Google Places a refusé la requête (HTTP 403) : API key not valid.');

        app(GooglePlacesClient::class)->photos('p1');
    }

    /** @return array<string, mixed> */
    private function googlePlace(string $id, string $name): array
    {
        return [
            'id' => $id,
            'displayName' => ['text' => $name, 'languageCode' => 'fr'],
            'formattedAddress' => '12 Rue Mercière, 69001 Lyon',
            'addressComponents' => [
                ['longText' => '69001', 'shortText' => '69001', 'types' => ['postal_code']],
                ['longText' => 'Lyon', 'shortText' => 'Lyon', 'types' => ['locality', 'political']],
                ['longText' => 'Rhône', 'shortText' => 'Rhône', 'types' => ['administrative_area_level_2', 'political']],
                ['longText' => 'France', 'shortText' => 'FR', 'types' => ['country', 'political']],
            ],
            'location' => ['latitude' => 45.7621, 'longitude' => 4.8327],
            'types' => ['french_restaurant', 'restaurant'],
            'primaryType' => 'french_restaurant',
            'priceLevel' => 'PRICE_LEVEL_MODERATE',
            'rating' => 4.4,
            'userRatingCount' => 812,
            'websiteUri' => 'https://bouchon.example',
            'businessStatus' => 'OPERATIONAL',
        ];
    }
}

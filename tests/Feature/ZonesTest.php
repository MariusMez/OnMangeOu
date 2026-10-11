<?php

namespace Tests\Feature;

use App\Exceptions\CollectException;
use App\Models\Restaurant;
use App\Services\Geo\Zones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Zones résolues avec le vrai client geo.api.gouv.fr, dont les réponses sont simulées. */
class ZonesTest extends TestCase
{
    use RefreshDatabase;

    private const array LYON_1 = ['nom' => 'Lyon 1er Arrondissement', 'code' => '69381', 'centre' => ['type' => 'Point', 'coordinates' => [4.8264, 45.7701]], 'surface' => 154.37, 'population' => 28717, 'codesPostaux' => ['69001'], 'codeDepartement' => '69', 'codeRegion' => '84'];

    private const array RENNES = ['nom' => 'Rennes', 'code' => '35238', 'centre' => ['type' => 'Point', 'coordinates' => [-1.6884, 48.1159]], 'surface' => 5033.19, 'population' => 230890, 'codesPostaux' => ['35000', '35200', '35700'], 'codeDepartement' => '35', 'codeRegion' => '53'];

    private const array CESSON = ['nom' => 'Cesson-Sévigné', 'code' => '35051', 'centre' => ['type' => 'Point', 'coordinates' => [-1.6029, 48.1213]], 'surface' => 3216.0, 'population' => 18000, 'codesPostaux' => ['35510'], 'codeDepartement' => '35', 'codeRegion' => '53'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['opticarte.mock' => false]);
    }

    public function test_a_postal_code_targets_its_arrondissement_and_only_its_restaurants(): void
    {
        Http::preventStrayRequests();
        Http::fake(['geo.api.gouv.fr/communes?codePostal=69001&type=arrondissement-municipal*' => Http::response([self::LYON_1])]);

        $zones = app(Zones::class);
        $zone = $zones->resolve(['postal_codes' => ['69001']]);

        $this->assertSame('Code postal 69001', $zone['label']);
        $this->assertSame(['latitude' => 45.7701, 'longitude' => 4.8264, 'radius_m' => 805, 'label' => 'Lyon 1er Arrondissement', 'postal_code' => '69001'], $zone['circles'][0]);
        $this->assertTrue($zones->contains($zone, ['postal_code' => '69001', 'departement_code' => '69']));
        $this->assertFalse($zones->contains($zone, ['postal_code' => '69002', 'departement_code' => '69']));
        $this->assertFalse($zones->contains($zone, ['postal_code' => '69001', 'departement_code' => null]));
    }

    public function test_a_postal_code_without_arrondissement_falls_back_to_its_communes(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'geo.api.gouv.fr/communes?codePostal=35000&type=arrondissement-municipal*' => Http::response([]),
            'geo.api.gouv.fr/communes?codePostal=35000&fields=*' => Http::response([self::RENNES]),
        ]);

        $zone = app(Zones::class)->resolve(['postal_codes' => ['35000']]);

        $this->assertSame('Rennes', $zone['circles'][0]['label']);
        $this->assertSame(['35'], $zone['departement_codes']);
    }

    public function test_a_radius_keeps_the_restaurants_within_the_distance(): void
    {
        Http::preventStrayRequests();
        Http::fake(['geo.api.gouv.fr/communes?codePostal=69001&type=arrondissement-municipal*' => Http::response([self::LYON_1])]);
        $near = Restaurant::factory()->locatedAt('69001', '69', '84', 45.7741, 4.8264)->create();
        Restaurant::factory()->locatedAt('69004', '69', '84', 45.7841, 4.8264)->create();

        $zones = app(Zones::class);
        $zone = $zones->resolve(['postal_codes' => ['69001'], 'radius_km' => 1]);

        $this->assertSame('1 km autour de Lyon 1er Arrondissement', $zone['label']);
        $this->assertTrue($zones->contains($zone, ['departement_code' => '69', 'latitude' => 45.7741, 'longitude' => 4.8264]));
        $this->assertFalse($zones->contains($zone, ['departement_code' => '69', 'latitude' => 45.7841, 'longitude' => 4.8264]));
        $this->assertSame(['restaurant_ids' => [$near->id]], $zones->dishFilter($zone));
    }

    public function test_a_departement_named_in_full_is_searched_from_its_most_populated_commune(): void
    {
        Http::preventStrayRequests();
        Http::fake(['geo.api.gouv.fr/departements/35/communes*' => Http::response([self::CESSON, self::RENNES])]);

        $zones = app(Zones::class);
        $zone = $zones->resolve(['departement' => 'ille et vilaine']);
        $firstArea = $zones->searchAreas($zone, 1500)->current();

        $this->assertSame('Département Ille-et-Vilaine (35)', $zone['label']);
        $this->assertSame(['Rennes', 'Cesson-Sévigné'], array_column($zone['circles'], 'label'));
        $this->assertSame(['latitude' => 48.1159, 'longitude' => -1.6884, 'radius_m' => 1500, 'label' => 'Rennes', 'postal_code' => '35000'], $firstArea);
        $this->assertTrue($zones->contains($zone, ['postal_code' => '35510', 'departement_code' => '35']));
        $this->assertFalse($zones->contains($zone, ['postal_code' => '44000', 'departement_code' => '44']));
    }

    public function test_a_region_keeps_the_restaurants_of_all_its_departements(): void
    {
        Http::preventStrayRequests();
        Http::fake(['geo.api.gouv.fr/communes?codeRegion=53*' => Http::response([self::RENNES])]);

        $zones = app(Zones::class);
        $zone = $zones->resolve(['region' => 'Bretagne']);

        $this->assertSame('Région Bretagne', $zone['label']);
        $this->assertTrue($zones->contains($zone, ['postal_code' => '29200', 'departement_code' => '29']));
        $this->assertFalse($zones->contains($zone, ['postal_code' => '44000', 'departement_code' => '44']));
        $this->assertSame(['region_code' => '53'], $zones->dishFilter($zone));
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function invalidZones(): array
    {
        return [
            'aucune zone' => [[], 'Indiquez une zone'],
            'code postal tronqué' => [['postal_codes' => ['6900']], 'Code postal invalide : 6900'],
            'latitude sans longitude' => [['latitude' => 45.7], 'Indiquez à la fois --lat et --lng.'],
            'point sans rayon' => [['latitude' => 45.7, 'longitude' => 4.8], 'Précisez un rayon'],
            'rayon démesuré' => [['latitude' => 45.7, 'longitude' => 4.8, 'radius_km' => 150.0], 'entre 0 et 100 km'],
            'département inconnu' => [['departement' => 'Atlantide'], 'Département inconnu : Atlantide.'],
        ];
    }

    /** @param  array<string, mixed>  $criteria */
    #[DataProvider('invalidZones')]
    public function test_an_inconsistent_zone_is_rejected_with_a_readable_message(array $criteria, string $message): void
    {
        Http::preventStrayRequests();

        $this->expectException(CollectException::class);
        $this->expectExceptionMessage($message);

        app(Zones::class)->resolve($criteria);
    }

    public function test_an_unavailable_geography_service_is_reported_after_retries(): void
    {
        Sleep::fake();
        Http::preventStrayRequests();
        Http::fake(['geo.api.gouv.fr/departements/35/communes*' => Http::response('', 503)]);

        try {
            app(Zones::class)->resolve(['departement' => '35']);
            $this->fail('Une CollectException était attendue.');
        } catch (CollectException $exception) {
            $this->assertStringContainsString('geo.api.gouv.fr ne répond pas', $exception->getMessage());
        }

        Http::assertSentCount(4);
    }
}

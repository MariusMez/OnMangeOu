<?php

namespace App\Services\Geo;

use App\Contracts\FrenchGeography;
use App\Exceptions\CollectException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Découpage administratif via l'API publique geo.api.gouv.fr (sans clé). */
class GeoApiGeography implements FrenchGeography
{
    private const string FIELDS = 'nom,code,centre,surface,population,codesPostaux,codeDepartement,codeRegion';

    public function communesByPostalCode(string $postalCode): array
    {
        $districts = $this->communes('communes', ['codePostal' => $postalCode, 'type' => 'arrondissement-municipal']);

        return $districts !== [] ? $districts : $this->communes('communes', ['codePostal' => $postalCode]);
    }

    public function commune(string $codeOrName): ?array
    {
        $value = trim($codeOrName);

        if (preg_match('/^(\d{5}|2[AB]\d{3})$/i', $value) === 1) {
            return $this->communes('communes', ['code' => strtoupper($value)])[0] ?? null;
        }

        return $this->communes('communes', ['nom' => $value, 'boost' => 'population', 'limit' => 1])[0] ?? null;
    }

    public function communesOfDepartement(string $departementCode): array
    {
        return $this->communes('departements/'.$departementCode.'/communes');
    }

    public function communesOfRegion(string $regionCode): array
    {
        return $this->communes('communes', ['codeRegion' => $regionCode]);
    }

    /**
     * @param  array<string, string|int>  $query
     * @return list<array{code: string, name: string, postal_codes: list<string>, departement_code: string, region_code: string, latitude: float, longitude: float, surface_km2: float, population: int}>
     */
    private function communes(string $path, array $query = []): array
    {
        try {
            $rows = $this->request()->get($path, [...$query, 'fields' => self::FIELDS])->throw()->json();
        } catch (ConnectionException|RequestException $exception) {
            throw new CollectException("L'API géographique geo.api.gouv.fr ne répond pas : ".$exception->getMessage(), previous: $exception);
        }

        $communes = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $coordinates = $row['centre']['coordinates'] ?? null;

            if (! is_array($coordinates) || count($coordinates) !== 2) {
                continue;
            }

            $communes[] = [
                'code' => (string) $row['code'],
                'name' => (string) $row['nom'],
                'postal_codes' => array_values(array_map('strval', $row['codesPostaux'] ?? [])),
                'departement_code' => (string) ($row['codeDepartement'] ?? ''),
                'region_code' => (string) ($row['codeRegion'] ?? ''),
                'latitude' => (float) $coordinates[1],
                'longitude' => (float) $coordinates[0],
                'surface_km2' => round((float) ($row['surface'] ?? 0) / 100, 2),
                'population' => (int) ($row['population'] ?? 0),
            ];
        }

        return $communes;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(config('opticarte.collect.geo_api_url'))
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(30)
            ->retry([300, 1000, 3000], 0, fn (Throwable $exception): bool => $exception instanceof ConnectionException
                || ($exception instanceof RequestException
                    && ($exception->response->serverError() || $exception->response->status() === 429)));
    }
}

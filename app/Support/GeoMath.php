<?php

namespace App\Support;

/** Calculs géographiques simples (sphère terrestre), suffisants à l'échelle d'une région. */
final class GeoMath
{
    private const float EARTH_RADIUS_M = 6_371_000.0;

    /** 2πR / 360, cohérent avec EARTH_RADIUS_M. */
    private const float METERS_PER_DEGREE = 111_194.93;

    /** Distance à vol d'oiseau en mètres (formule de haversine). */
    public static function distanceMeters(float $latitude1, float $longitude1, float $latitude2, float $longitude2): float
    {
        $deltaLatitude = deg2rad($latitude2 - $latitude1);
        $deltaLongitude = deg2rad($longitude2 - $longitude1);

        $a = sin($deltaLatitude / 2) ** 2
            + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($deltaLongitude / 2) ** 2;

        return 2 * self::EARTH_RADIUS_M * asin(min(1.0, sqrt($a)));
    }

    /**
     * Point décalé de $eastMeters vers l'est et $northMeters vers le nord.
     *
     * @return array{latitude: float, longitude: float}
     */
    public static function offset(float $latitude, float $longitude, float $eastMeters, float $northMeters): array
    {
        return [
            'latitude' => round($latitude + $northMeters / self::METERS_PER_DEGREE, 6),
            'longitude' => round($longitude + $eastMeters / (self::METERS_PER_DEGREE * cos(deg2rad($latitude))), 6),
        ];
    }

    /**
     * Rectangle englobant un cercle (coin sud-ouest « low », coin nord-est « high »).
     *
     * @return array{low: array{latitude: float, longitude: float}, high: array{latitude: float, longitude: float}}
     */
    public static function boundingBox(float $latitude, float $longitude, float $radiusMeters): array
    {
        return [
            'low' => self::offset($latitude, $longitude, -$radiusMeters, -$radiusMeters),
            'high' => self::offset($latitude, $longitude, $radiusMeters, $radiusMeters),
        ];
    }

    /**
     * Pave un cercle avec des cellules hexagonales de rayon $cellRadiusMeters, de la plus proche
     * du centre à la plus lointaine. Un cercle plus petit qu'une cellule reste d'un seul tenant.
     *
     * @return list<array{latitude: float, longitude: float, radius_m: int}>
     */
    public static function tileCircle(float $latitude, float $longitude, float $radiusMeters, float $cellRadiusMeters): array
    {
        if ($radiusMeters <= $cellRadiusMeters) {
            return [['latitude' => $latitude, 'longitude' => $longitude, 'radius_m' => (int) round($radiusMeters)]];
        }

        $columnSpacing = sqrt(3) * $cellRadiusMeters;
        $rowSpacing = 1.5 * $cellRadiusMeters;
        $rows = (int) ceil($radiusMeters / $rowSpacing) + 1;
        $columns = (int) ceil($radiusMeters / $columnSpacing) + 1;
        $cells = [];

        for ($row = -$rows; $row <= $rows; $row++) {
            $shift = $row % 2 === 0 ? 0.0 : $columnSpacing / 2;

            for ($column = -$columns; $column <= $columns; $column++) {
                $east = $column * $columnSpacing + $shift;
                $north = $row * $rowSpacing;
                $distance = sqrt($east ** 2 + $north ** 2);

                if ($distance - $cellRadiusMeters < $radiusMeters) {
                    $cells[] = [
                        'distance' => $distance,
                        ...self::offset($latitude, $longitude, $east, $north),
                        'radius_m' => (int) round($cellRadiusMeters),
                    ];
                }
            }
        }

        usort($cells, fn (array $a, array $b): int => $a['distance'] <=> $b['distance']);

        return array_map(fn (array $cell): array => [
            'latitude' => $cell['latitude'],
            'longitude' => $cell['longitude'],
            'radius_m' => $cell['radius_m'],
        ], $cells);
    }
}

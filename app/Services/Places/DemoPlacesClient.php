<?php

namespace App\Services\Places;

use App\Contracts\PlacesClient;
use App\Support\GeoMath;

/**
 * Mode démo : quelques restaurants fictifs autour de chaque zone de recherche, avec une photo
 * de carte (en portrait) et des photos d'ambiance (en paysage) générées localement.
 */
class DemoPlacesClient implements PlacesClient
{
    /** @var list<array{0: string, 1: string, 2: list<string>, 3: ?int, 4: string}> Nom, type principal, types, niveau de prix, statut */
    private const array RESTAURANTS = [
        ['Le Bouchon des Halles', 'french_restaurant', ['french_restaurant', 'restaurant', 'food'], 2, 'OPERATIONAL'],
        ['Pizzeria Bella Vita', 'pizza_restaurant', ['pizza_restaurant', 'italian_restaurant', 'restaurant'], 1, 'OPERATIONAL'],
        ['Sushi Kan', 'japanese_restaurant', ['japanese_restaurant', 'sushi_restaurant', 'restaurant'], 2, 'OPERATIONAL'],
        ['La Table de Margaux', 'fine_dining_restaurant', ['fine_dining_restaurant', 'restaurant'], 4, 'OPERATIONAL'],
        ['Le Comptoir à Vins', 'wine_bar', ['wine_bar', 'bar', 'restaurant'], 2, 'OPERATIONAL'],
        ['Chez Paulette', 'restaurant', ['restaurant', 'food'], null, 'OPERATIONAL'],
        ["L'Ancien Relais", 'french_restaurant', ['french_restaurant', 'restaurant'], 2, 'CLOSED_PERMANENTLY'],
    ];

    private const int PHOTOS_PER_PLACE = 4;

    public function searchRestaurants(array $area, int $limit): array
    {
        $seed = substr(md5(sprintf('%.4f,%.4f', $area['latitude'], $area['longitude'])), 0, 8);
        $postalCode = $area['postal_code'] ?? '69001';
        $places = [];

        foreach (self::RESTAURANTS as $index => [$name, $primaryType, $types, $priceLevel, $status]) {
            $angle = 2 * M_PI * $index / count(self::RESTAURANTS);
            $distance = $area['radius_m'] * 0.5;

            $places[] = [
                'id' => "demo-{$seed}-{$index}",
                'name' => $name,
                'address' => sprintf('%d rue de la Démonstration, %s Démoville', 10 + $index, $postalCode),
                'postal_code' => $postalCode,
                'city' => 'Démoville',
                'departement_name' => null,
                'country_code' => 'FR',
                ...GeoMath::offset($area['latitude'], $area['longitude'], cos($angle) * $distance, sin($angle) * $distance),
                'primary_type' => $primaryType,
                'types' => $types,
                'price_level' => $priceLevel,
                'rating' => 4.0 + ($index % 5) / 10,
                'rating_count' => 120 + 37 * $index,
                'website_url' => null,
                'business_status' => $status,
            ];
        }

        return array_slice($places, 0, $limit);
    }

    public function photos(string $placeId): array
    {
        return array_map(fn (int $index): array => [
            'name' => "places/{$placeId}/photos/{$index}",
            'width' => $index === 0 ? 1200 : 1600,
            'height' => $index === 0 ? 1600 : 1200,
            'attribution' => 'Photo de démonstration',
        ], range(0, self::PHOTOS_PER_PLACE - 1));
    }

    public function photo(string $photoName, int $maxWidthPx): string
    {
        $isMenu = str_ends_with($photoName, '/photos/0');
        $width = min($maxWidthPx, $isMenu ? 600 : 800);
        $height = (int) round($isMenu ? $width * 4 / 3 : $width * 3 / 4);
        $image = imagecreatetruecolor($width, $height);
        $hue = crc32($photoName);

        if ($isMenu) {
            imagefill($image, 0, 0, imagecolorallocate($image, 250, 243, 232));
            $ink = imagecolorallocate($image, 47, 47, 46);
            imagestring($image, 5, 20, 20, 'LA CARTE', $ink);
            imagestring($image, 2, 20, 44, $photoName, $ink);

            foreach (['Soupe a l oignon .... 8', 'Steak frites .... 17', 'Confit de canard .... 19', 'Creme brulee .... 7'] as $line => $text) {
                imagestring($image, 4, 20, 80 + 30 * $line, $text, $ink);
            }
        } else {
            imagefill($image, 0, 0, imagecolorallocate($image, 80 + $hue % 120, 60 + ($hue >> 8) % 100, 40 + ($hue >> 16) % 80));
            imagefilledellipse($image, intdiv($width, 2), intdiv($height, 2), intdiv($width, 2), intdiv($width, 2), imagecolorallocate($image, 245, 245, 240));
        }

        ob_start();
        imagejpeg($image, null, 80);

        return (string) ob_get_clean();
    }
}

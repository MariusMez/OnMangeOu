<?php

namespace App\Services\Collect;

use App\Contracts\MenuAnalyst;
use App\Contracts\PhotoClassifier;
use App\Contracts\PlacesClient;
use App\Enums\PhotoKind;
use App\Models\Restaurant;
use App\Services\DishImporter;
use App\Services\ImageNormalizer;
use Illuminate\Support\Facades\Storage;

/**
 * Collecte d'un restaurant : photos de sa fiche Google Places → tri par l'IA → lecture des
 * photos de carte → base de plats. Chaque photo n'est examinée qu'une fois : une carte
 * déjà lue une année précédente n'est pas recomptée, seules les nouvelles photos alimentent
 * l'année en cours.
 */
class MenuCollector
{
    public function __construct(
        private readonly PlacesClient $places,
        private readonly PhotoClassifier $classifier,
        private readonly MenuAnalyst $analyst,
        private readonly ImageNormalizer $normalizer,
        private readonly DishImporter $dishes,
        private readonly RestaurantTypeClassifier $types,
    ) {}

    /** @return array{photos: int, new_photos: int, menu_photos: int, dishes: int} */
    public function collect(Restaurant $restaurant, int $maxPhotos): array
    {
        $photos = array_slice($this->places->photos((string) $restaurant->google_place_id), 0, $maxPhotos);
        $known = $restaurant->photos()->pluck('thumbnail_hash')->all();
        $fresh = [];

        foreach ($photos as $photo) {
            $thumbnail = $this->normalizer->normalizeBinary(
                $this->places->photo($photo['name'], (int) config('opticarte.collect.thumbnail_px')),
                (int) config('opticarte.collect.thumbnail_px'),
            );
            $hash = sha1($thumbnail);

            if (! in_array($hash, $known, true)) {
                $known[] = $hash;
                $fresh[] = ['photo' => $photo, 'thumbnail' => $thumbnail, 'hash' => $hash];
            }
        }

        $verdicts = $this->classifier->classify(array_column($fresh, 'thumbnail'));
        $records = [];
        $menuImages = [];

        foreach ($fresh as $index => $item) {
            $verdict = $verdicts[$index] ?? ['kind' => PhotoKind::Other->value, 'has_prices' => false];
            $isReadableMenu = $verdict['kind'] === PhotoKind::Menu->value && $verdict['has_prices'];
            $path = null;

            if ($isReadableMenu) {
                $image = $this->normalizer->normalizeBinary($this->places->photo($item['photo']['name'], ImageNormalizer::MAX_EDGE));
                $menuImages[] = $image;

                if (config('opticarte.collect.keep_photos')) {
                    $path = "collect/{$restaurant->id}/{$item['hash']}.jpg";
                    Storage::disk(config('opticarte.disk'))->put($path, $image);
                }
            }

            $records[] = [
                'thumbnail_hash' => $item['hash'],
                'kind' => $verdict['kind'],
                'has_prices' => $verdict['has_prices'],
                'attribution' => $item['photo']['attribution'],
                'path' => $path,
            ];
        }

        $dishCount = 0;

        if ($menuImages !== []) {
            $menu = $this->analyst->extract($menuImages);
            $restaurant->restaurant_type = $this->types->refine($restaurant->restaurant_type, $menu['cuisine_type'] ?? null);
            $restaurant->save();
            $dishCount = $this->dishes->importCollected($restaurant, $menu);
        }

        $restaurant->photos()->createMany($records);
        $restaurant->forceFill(['collected_at' => now()])->save();

        return [
            'photos' => count($photos),
            'new_photos' => count($fresh),
            'menu_photos' => count($menuImages),
            'dishes' => $dishCount,
        ];
    }
}

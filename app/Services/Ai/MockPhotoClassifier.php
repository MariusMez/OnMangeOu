<?php

namespace App\Services\Ai;

use App\Contracts\PhotoClassifier;
use App\Enums\PhotoKind;

/** Mode démo : une photo en portrait est une carte lisible, les autres sont des plats. Aucun appel API. */
class MockPhotoClassifier implements PhotoClassifier
{
    public function classify(array $images): array
    {
        return array_map(function (string $binary): array {
            $size = getimagesizefromstring($binary);
            $isPortrait = $size !== false && $size[1] > $size[0];

            return [
                'kind' => $isPortrait ? PhotoKind::Menu->value : PhotoKind::Food->value,
                'has_prices' => $isPortrait,
            ];
        }, $images);
    }
}

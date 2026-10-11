<?php

namespace App\Services\Ai;

use App\Ai\Agents\MenuPhotoClassifier;
use App\Contracts\PhotoClassifier;
use App\Enums\PhotoKind;
use App\Exceptions\CollectException;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Responses\StructuredAgentResponse;

/** Tri des photos d'un restaurant par l'agent MenuPhotoClassifier, en un seul appel. */
class AiPhotoClassifier implements PhotoClassifier
{
    public function __construct(private readonly MenuPhotoClassifier $agent) {}

    public function classify(array $images): array
    {
        if ($images === []) {
            return [];
        }

        $response = $this->agent->prompt(
            sprintf('Voici %d photo(s) de la fiche du restaurant, numérotées de 1 à %d dans l\'ordre. Classe chacune.', count($images), count($images)),
            attachments: array_map(fn (string $binary) => Image::fromBase64(base64_encode($binary), 'image/jpeg'), $images),
        );

        $data = $response instanceof StructuredAgentResponse ? $response->toArray() : json_decode($response->text, true);

        if (! is_array($data['photos'] ?? null)) {
            throw new CollectException('Réponse du modèle inexploitable pendant le tri des photos.');
        }

        $verdicts = array_fill(0, count($images), ['kind' => PhotoKind::Other->value, 'has_prices' => false]);

        foreach ($data['photos'] as $photo) {
            $index = (int) ($photo['index'] ?? 0) - 1;

            if (array_key_exists($index, $verdicts)) {
                $verdicts[$index] = [
                    'kind' => PhotoKind::tryFrom((string) ($photo['kind'] ?? ''))?->value ?? PhotoKind::Other->value,
                    'has_prices' => (bool) ($photo['has_prices'] ?? false),
                ];
            }
        }

        return $verdicts;
    }
}

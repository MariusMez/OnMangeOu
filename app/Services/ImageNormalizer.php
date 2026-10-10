<?php

namespace App\Services;

use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

/**
 * Normalise une photo de carte avant envoi au modèle : orientation EXIF
 * appliquée, 1600 px max, JPEG qualité 85. Chaque image reste ainsi bien
 * sous la limite de 5 Mo de l'API.
 */
class ImageNormalizer
{
    public const int MAX_EDGE = 1600;

    public function __construct(private readonly ImageManager $manager) {}

    public function normalize(string $path): string
    {
        return $this->manager->decodePath($path)
            ->orient()
            ->scaleDown(width: self::MAX_EDGE, height: self::MAX_EDGE)
            ->encode(new JpegEncoder(quality: 85))
            ->toString();
    }
}

<?php

namespace App\Enums;

/** Origine d'un plat de la base : analyse d'un restaurateur ou collecte Google Places. */
enum DishSource: string
{
    case Analysis = 'analysis';
    case GooglePlaces = 'google_places';

    public function label(): string
    {
        return match ($this) {
            self::Analysis => 'Analyse',
            self::GooglePlaces => 'Collecte Google Places',
        };
    }
}

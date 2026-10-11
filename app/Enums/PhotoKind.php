<?php

namespace App\Enums;

/** Nature d'une photo publiée sur la fiche d'un restaurant, déterminée par l'IA. */
enum PhotoKind: string
{
    case Menu = 'menu';
    case Food = 'food';
    case Interior = 'interior';
    case Exterior = 'exterior';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Menu => 'Carte ou ardoise',
            self::Food => 'Plat',
            self::Interior => 'Salle',
            self::Exterior => 'Façade',
            self::Other => 'Autre',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}

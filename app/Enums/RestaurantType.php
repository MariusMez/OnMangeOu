<?php

namespace App\Enums;

/** Types de restaurant proposés à l'analyse et attribués aux restaurants collectés. */
enum RestaurantType: string
{
    case Bistrot = 'Bistrot / brasserie';
    case Pizzeria = 'Pizzeria / italien';
    case Gastronomique = 'Gastronomique';
    case RestaurationRapide = 'Restauration rapide';
    case Asiatique = 'Asiatique';
    case Creperie = 'Crêperie';
    case BarAVin = 'Bar à vin / tapas';
    case CuisineDuMonde = 'Cuisine du monde';
    case Autre = 'Autre';

    public function label(): string
    {
        return $this->value;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}

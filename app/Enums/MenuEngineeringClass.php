<?php

namespace App\Enums;

enum MenuEngineeringClass: string
{
    case Etoile = 'etoile';
    case VacheALait = 'vache_a_lait';
    case Enigme = 'enigme';
    case PoidsMort = 'poids_mort';

    public function label(): string
    {
        return match ($this) {
            self::Etoile => 'Étoile (populaire & rentable)',
            self::VacheALait => 'Vache à lait (populaire, peu rentable)',
            self::Enigme => 'Énigme (rentable, peu populaire)',
            self::PoidsMort => 'Poids mort (ni populaire ni rentable)',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}

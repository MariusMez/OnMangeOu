<?php

namespace App\Enums;

enum DishCategory: string
{
    case Entree = 'entree';
    case Plat = 'plat';
    case Dessert = 'dessert';
    case Boisson = 'boisson';
    case Menu = 'menu';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Entree => 'Entrées',
            self::Plat => 'Plats',
            self::Dessert => 'Desserts',
            self::Boisson => 'Boissons',
            self::Menu => 'Menus & formules',
            self::Autre => 'Autres',
        };
    }

    public static function labelFor(string $value): string
    {
        return self::tryFrom($value)?->label() ?? $value;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}

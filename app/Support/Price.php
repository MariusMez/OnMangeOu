<?php

namespace App\Support;

final class Price
{
    /** Prix affiché dans l'interface : 14 → "14 €", 14.5 → "14,50 €". */
    public static function euro(float|int|string|null $price): string
    {
        if ($price === null || $price === '') {
            return '—';
        }

        return self::menu((float) $price).' €';
    }

    /** Prix sur la carte, sans symbole monétaire : 14 → "14", 14.5 → "14,50". */
    public static function menu(float|int|string|null $price): string
    {
        if ($price === null || $price === '') {
            return '';
        }

        $formatted = number_format((float) $price, 2, ',', ' ');

        return str_ends_with($formatted, ',00') ? substr($formatted, 0, -3) : $formatted;
    }
}

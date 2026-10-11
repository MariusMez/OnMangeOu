<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Référentiel administratif français (codes INSEE des régions et départements, source
 * geo.api.gouv.fr) et correspondances code postal → département → région.
 */
final class France
{
    /** Régions : code INSEE => nom. */
    public const array REGIONS = [
        '01' => 'Guadeloupe',
        '02' => 'Martinique',
        '03' => 'Guyane',
        '04' => 'La Réunion',
        '06' => 'Mayotte',
        '11' => 'Île-de-France',
        '24' => 'Centre-Val de Loire',
        '27' => 'Bourgogne-Franche-Comté',
        '28' => 'Normandie',
        '32' => 'Hauts-de-France',
        '44' => 'Grand Est',
        '52' => 'Pays de la Loire',
        '53' => 'Bretagne',
        '75' => 'Nouvelle-Aquitaine',
        '76' => 'Occitanie',
        '84' => 'Auvergne-Rhône-Alpes',
        '93' => 'Provence-Alpes-Côte d\'Azur',
        '94' => 'Corse',
    ];

    /** Départements : code INSEE => [nom, code région]. */
    public const array DEPARTEMENTS = [
        '01' => ['Ain', '84'],
        '02' => ['Aisne', '32'],
        '03' => ['Allier', '84'],
        '04' => ['Alpes-de-Haute-Provence', '93'],
        '05' => ['Hautes-Alpes', '93'],
        '06' => ['Alpes-Maritimes', '93'],
        '07' => ['Ardèche', '84'],
        '08' => ['Ardennes', '44'],
        '09' => ['Ariège', '76'],
        '10' => ['Aube', '44'],
        '11' => ['Aude', '76'],
        '12' => ['Aveyron', '76'],
        '13' => ['Bouches-du-Rhône', '93'],
        '14' => ['Calvados', '28'],
        '15' => ['Cantal', '84'],
        '16' => ['Charente', '75'],
        '17' => ['Charente-Maritime', '75'],
        '18' => ['Cher', '24'],
        '19' => ['Corrèze', '75'],
        '21' => ['Côte-d\'Or', '27'],
        '22' => ['Côtes-d\'Armor', '53'],
        '23' => ['Creuse', '75'],
        '24' => ['Dordogne', '75'],
        '25' => ['Doubs', '27'],
        '26' => ['Drôme', '84'],
        '27' => ['Eure', '28'],
        '28' => ['Eure-et-Loir', '24'],
        '29' => ['Finistère', '53'],
        '2A' => ['Corse-du-Sud', '94'],
        '2B' => ['Haute-Corse', '94'],
        '30' => ['Gard', '76'],
        '31' => ['Haute-Garonne', '76'],
        '32' => ['Gers', '76'],
        '33' => ['Gironde', '75'],
        '34' => ['Hérault', '76'],
        '35' => ['Ille-et-Vilaine', '53'],
        '36' => ['Indre', '24'],
        '37' => ['Indre-et-Loire', '24'],
        '38' => ['Isère', '84'],
        '39' => ['Jura', '27'],
        '40' => ['Landes', '75'],
        '41' => ['Loir-et-Cher', '24'],
        '42' => ['Loire', '84'],
        '43' => ['Haute-Loire', '84'],
        '44' => ['Loire-Atlantique', '52'],
        '45' => ['Loiret', '24'],
        '46' => ['Lot', '76'],
        '47' => ['Lot-et-Garonne', '75'],
        '48' => ['Lozère', '76'],
        '49' => ['Maine-et-Loire', '52'],
        '50' => ['Manche', '28'],
        '51' => ['Marne', '44'],
        '52' => ['Haute-Marne', '44'],
        '53' => ['Mayenne', '52'],
        '54' => ['Meurthe-et-Moselle', '44'],
        '55' => ['Meuse', '44'],
        '56' => ['Morbihan', '53'],
        '57' => ['Moselle', '44'],
        '58' => ['Nièvre', '27'],
        '59' => ['Nord', '32'],
        '60' => ['Oise', '32'],
        '61' => ['Orne', '28'],
        '62' => ['Pas-de-Calais', '32'],
        '63' => ['Puy-de-Dôme', '84'],
        '64' => ['Pyrénées-Atlantiques', '75'],
        '65' => ['Hautes-Pyrénées', '76'],
        '66' => ['Pyrénées-Orientales', '76'],
        '67' => ['Bas-Rhin', '44'],
        '68' => ['Haut-Rhin', '44'],
        '69' => ['Rhône', '84'],
        '70' => ['Haute-Saône', '27'],
        '71' => ['Saône-et-Loire', '27'],
        '72' => ['Sarthe', '52'],
        '73' => ['Savoie', '84'],
        '74' => ['Haute-Savoie', '84'],
        '75' => ['Paris', '11'],
        '76' => ['Seine-Maritime', '28'],
        '77' => ['Seine-et-Marne', '11'],
        '78' => ['Yvelines', '11'],
        '79' => ['Deux-Sèvres', '75'],
        '80' => ['Somme', '32'],
        '81' => ['Tarn', '76'],
        '82' => ['Tarn-et-Garonne', '76'],
        '83' => ['Var', '93'],
        '84' => ['Vaucluse', '93'],
        '85' => ['Vendée', '52'],
        '86' => ['Vienne', '75'],
        '87' => ['Haute-Vienne', '75'],
        '88' => ['Vosges', '44'],
        '89' => ['Yonne', '27'],
        '90' => ['Territoire de Belfort', '27'],
        '91' => ['Essonne', '11'],
        '92' => ['Hauts-de-Seine', '11'],
        '93' => ['Seine-Saint-Denis', '11'],
        '94' => ['Val-de-Marne', '11'],
        '95' => ['Val-d\'Oise', '11'],
        '971' => ['Guadeloupe', '01'],
        '972' => ['Martinique', '02'],
        '973' => ['Guyane', '03'],
        '974' => ['La Réunion', '04'],
        '976' => ['Mayotte', '06'],
    ];

    /** Département d'un code postal : deux premiers chiffres, trois en outre-mer, 2A / 2B en Corse. */
    public static function departementForPostalCode(?string $postalCode): ?string
    {
        if ($postalCode === null || preg_match('/^\d{5}$/', $postalCode) !== 1) {
            return null;
        }

        if (str_starts_with($postalCode, '20')) {
            return (int) $postalCode[2] < 2 ? '2A' : '2B';
        }

        $code = str_starts_with($postalCode, '97') ? substr($postalCode, 0, 3) : substr($postalCode, 0, 2);

        return isset(self::DEPARTEMENTS[$code]) ? $code : null;
    }

    public static function regionForDepartement(?string $departementCode): ?string
    {
        return $departementCode === null ? null : (self::DEPARTEMENTS[$departementCode][1] ?? null);
    }

    public static function departementName(?string $departementCode): ?string
    {
        return $departementCode === null ? null : (self::DEPARTEMENTS[$departementCode][0] ?? null);
    }

    public static function regionName(?string $regionCode): ?string
    {
        return $regionCode === null ? null : (self::REGIONS[$regionCode] ?? null);
    }

    /** @return list<string> */
    public static function departementsOfRegion(string $regionCode): array
    {
        $codes = [];

        foreach (self::DEPARTEMENTS as $code => [, $region]) {
            if ($region === $regionCode) {
                $codes[] = (string) $code;
            }
        }

        return $codes;
    }

    /** Code d'un département à partir de son code (« 69 », « 2a », « 1 ») ou de son nom (« rhone »). */
    public static function findDepartement(string $codeOrName): ?string
    {
        $code = strtoupper(trim($codeOrName));
        $code = preg_match('/^\d$/', $code) === 1 ? '0'.$code : $code;

        if (isset(self::DEPARTEMENTS[$code])) {
            return $code;
        }

        $needle = self::normalize($codeOrName);

        foreach (self::DEPARTEMENTS as $departement => [$name]) {
            if (self::normalize($name) === $needle) {
                return (string) $departement;
            }
        }

        return null;
    }

    /** Code d'une région à partir de son code INSEE (« 84 ») ou de son nom (« auvergne rhone alpes »). */
    public static function findRegion(string $codeOrName): ?string
    {
        $code = trim($codeOrName);

        if (isset(self::REGIONS[$code])) {
            return $code;
        }

        $needle = self::normalize($codeOrName);

        foreach (self::REGIONS as $region => $name) {
            if (self::normalize($name) === $needle) {
                return (string) $region;
            }
        }

        return null;
    }

    private static function normalize(string $value): string
    {
        return Str::of($value)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->toString();
    }
}

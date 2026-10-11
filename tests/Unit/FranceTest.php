<?php

namespace Tests\Unit;

use App\Support\France;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FranceTest extends TestCase
{
    /** @return array<string, array{0: ?string, 1: ?string}> */
    public static function postalCodes(): array
    {
        return [
            'métropole' => ['69001', '69'],
            'département à zéro' => ['01000', '01'],
            'Corse-du-Sud' => ['20090', '2A'],
            'Haute-Corse' => ['20200', '2B'],
            'outre-mer' => ['97400', '974'],
            'Monaco, hors référentiel' => ['98000', null],
            'format invalide' => ['6900', null],
            'absent' => [null, null],
        ];
    }

    #[DataProvider('postalCodes')]
    public function test_a_postal_code_maps_to_its_departement(?string $postalCode, ?string $departement): void
    {
        $this->assertSame($departement, France::departementForPostalCode($postalCode));
    }

    public function test_departements_are_found_by_code_or_by_name_whatever_the_accents(): void
    {
        $this->assertSame('01', France::findDepartement('1'));
        $this->assertSame('2A', France::findDepartement('2a'));
        $this->assertSame('69', France::findDepartement('Rhône'));
        $this->assertSame('22', France::findDepartement('cotes d armor'));
        $this->assertNull(France::findDepartement('Atlantide'));
    }

    public function test_regions_are_found_by_code_or_by_name_and_list_their_departements(): void
    {
        $this->assertSame('84', France::findRegion('auvergne-rhone-alpes'));
        $this->assertSame('53', France::findRegion('53'));
        $this->assertSame(['2A', '2B'], France::departementsOfRegion('94'));
        $this->assertSame('84', France::regionForDepartement('69'));
        $this->assertNull(France::findRegion('Laponie'));
    }
}

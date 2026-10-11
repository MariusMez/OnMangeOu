<?php

namespace Tests\Unit;

use App\Support\GeoMath;
use PHPUnit\Framework\TestCase;

class GeoMathTest extends TestCase
{
    public function test_it_measures_the_distance_between_two_cities(): void
    {
        $lyonToParis = GeoMath::distanceMeters(45.7640, 4.8357, 48.8566, 2.3522);

        $this->assertEqualsWithDelta(392_000, $lyonToParis, 2_000);
    }

    public function test_a_circle_smaller_than_a_cell_is_searched_in_one_piece(): void
    {
        $this->assertSame(
            [['latitude' => 45.77, 'longitude' => 4.83, 'radius_m' => 800]],
            GeoMath::tileCircle(45.77, 4.83, 800, 1500),
        );
    }

    public function test_the_cells_cover_the_whole_circle_starting_from_its_center(): void
    {
        $cells = GeoMath::tileCircle(45.77, 4.83, 5_000, 1_500);

        $this->assertEqualsWithDelta(0, GeoMath::distanceMeters(45.77, 4.83, $cells[0]['latitude'], $cells[0]['longitude']), 1);

        foreach ([[0, 4_900], [4_900, 0], [-3_400, -3_400], [2_000, -4_500], [-4_950, 300]] as [$east, $north]) {
            $point = GeoMath::offset(45.77, 4.83, $east, $north);
            $nearest = min(array_map(
                fn (array $cell): float => GeoMath::distanceMeters($point['latitude'], $point['longitude'], $cell['latitude'], $cell['longitude']),
                $cells,
            ));

            $this->assertLessThanOrEqual(1_500, $nearest, "Point ({$east}, {$north}) hors des cellules");
        }
    }

    public function test_the_bounding_box_encloses_the_circle(): void
    {
        $box = GeoMath::boundingBox(45.77, 4.83, 1_000);

        $this->assertEqualsWithDelta(1_000, GeoMath::distanceMeters(45.77, 4.83, $box['high']['latitude'], 4.83), 1);
        $this->assertEqualsWithDelta(1_000, GeoMath::distanceMeters(45.77, 4.83, 45.77, $box['low']['longitude']), 1);
    }
}

<?php

namespace Tests\Unit;

use App\Enums\RestaurantType;
use App\Services\Collect\RestaurantTypeClassifier;
use PHPUnit\Framework\TestCase;

class RestaurantTypeClassifierTest extends TestCase
{
    public function test_the_primary_google_type_decides_before_the_secondary_ones(): void
    {
        $classifier = new RestaurantTypeClassifier;

        $this->assertSame(RestaurantType::Pizzeria, $classifier->fromGoogle('pizza_restaurant', ['italian_restaurant', 'restaurant'], 1));
        $this->assertSame(RestaurantType::Asiatique, $classifier->fromGoogle('restaurant', ['restaurant', 'sushi_restaurant'], 2));
        $this->assertSame(RestaurantType::BarAVin, $classifier->fromGoogle('wine_bar', ['french_restaurant'], 2));
    }

    public function test_a_generic_place_is_gastronomic_only_when_very_expensive(): void
    {
        $classifier = new RestaurantTypeClassifier;

        $this->assertSame(RestaurantType::Gastronomique, $classifier->fromGoogle('restaurant', ['restaurant'], 4));
        $this->assertSame(RestaurantType::Autre, $classifier->fromGoogle(null, ['restaurant', 'food'], 2));
    }

    public function test_the_cuisine_read_on_the_menu_only_refines_an_unknown_type(): void
    {
        $classifier = new RestaurantTypeClassifier;

        $this->assertSame(RestaurantType::Creperie, $classifier->refine(RestaurantType::Autre, 'Crêperie bretonne'));
        $this->assertSame(RestaurantType::Bistrot, $classifier->refine(RestaurantType::Bistrot, 'pizzeria'));
        $this->assertSame(RestaurantType::Autre, $classifier->refine(RestaurantType::Autre, null));
    }
}

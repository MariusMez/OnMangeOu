<?php

namespace Tests\Unit;

use App\Support\Price;
use PHPUnit\Framework\TestCase;

class PriceTest extends TestCase
{
    public function test_menu_prices_drop_the_currency_symbol_and_empty_cents(): void
    {
        $this->assertSame('14', Price::menu(14));
        $this->assertSame('14,50', Price::menu(14.5));
        $this->assertSame('', Price::menu(null));
    }

    public function test_interface_prices_carry_the_euro_symbol(): void
    {
        $this->assertSame('14 €', Price::euro(14));
        $this->assertSame('16,50 €', Price::euro('16.5'));
        $this->assertSame('—', Price::euro(null));
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AnalysisStatus;
use App\Models\Analysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceObservatoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_observatory_counts_each_restaurant_and_city_once_regardless_of_case(): void
    {
        $this->analysisWithOneDish('Chez Lucien', 'Lyon');
        $this->analysisWithOneDish('CHEZ LUCIEN', 'lyon');
        $this->analysisWithOneDish('Chez Lucien', 'Paris');
        $this->analysisWithOneDish('Le Bouchon', 'Lyon');

        $this->get(route('stats.index'))
            ->assertOk()
            ->assertSee('4 plats, 3 restaurants, 4 analyses terminées');
    }

    private function analysisWithOneDish(string $restaurantName, string $city): void
    {
        $analysis = Analysis::create([
            'status' => AnalysisStatus::Done,
            'restaurant_name' => $restaurantName,
            'city' => $city,
            'restaurant_type' => 'Bistrot / brasserie',
        ]);

        $analysis->dishes()->create([
            'restaurant_name' => $restaurantName,
            'city' => $city,
            'restaurant_type' => 'Bistrot / brasserie',
            'year' => 2026,
            'section' => 'Plats',
            'name' => 'Steak frites',
            'category' => 'plat',
            'price' => 18,
        ]);
    }
}

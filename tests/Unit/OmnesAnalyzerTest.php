<?php

namespace Tests\Unit;

use App\Enums\DishCategory;
use App\Services\Omnes\OmnesAnalyzer;
use App\Support\MockData;
use PHPUnit\Framework\TestCase;

class OmnesAnalyzerTest extends TestCase
{
    private OmnesAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = new OmnesAnalyzer;
    }

    public function test_a_tight_range_dominated_by_the_middle_zone_passes(): void
    {
        $report = $this->analyzer->analyzeCategory(DishCategory::Plat, [
            $this->dish('A', 14),
            $this->dish('B', 16.5),
            $this->dish('C', 17, highlighted: true),
            $this->dish('D', 17.5),
            $this->dish('E', 18),
            $this->dish('F', 21),
        ]);

        $statuses = $this->statusesByKey($report);

        $this->assertSame(1.5, $report['opening_ratio']);
        $this->assertSame(2.5, $report['opening_threshold']);
        $this->assertSame('ok', $statuses['ouverture']);
        $this->assertSame('ok', $statuses['dispersion']);
        $this->assertSame('ok', $statuses['promotion']);
        $this->assertSame('info', $statuses['rapport']);
    }

    public function test_a_wide_range_and_a_promotion_outside_the_middle_zone_are_flagged(): void
    {
        $report = $this->analyzer->analyzeCategory(DishCategory::Plat, [
            $this->dish('Pâtes', 12),
            $this->dish('Burger', 15),
            $this->dish('Côte de bœuf', 58, highlighted: true),
        ]);

        $principles = array_column($report['principles'], null, 'key');

        $this->assertSame('warning', $principles['ouverture']['status']);
        $this->assertSame(4.83, $report['opening_ratio']);
        $this->assertSame('warning', $principles['promotion']['status']);
        $this->assertStringContainsString('Côte de bœuf', $principles['promotion']['detail']);
        $this->assertSame(1, $report['zones']['high']['count']);
    }

    public function test_the_opening_threshold_rises_to_three_beyond_nine_dishes(): void
    {
        $dishes = [];
        for ($i = 0; $i < 10; $i++) {
            $dishes[] = $this->dish("P{$i}", 10 + $i * 2);
        }

        $report = $this->analyzer->analyzeCategory(DishCategory::Plat, $dishes);

        $this->assertSame(3, $report['opening_threshold']);
        $this->assertSame('ok', $report['principles'][0]['status']);
    }

    public function test_small_categories_and_unpriced_dishes_are_excluded(): void
    {
        $menu = MockData::menu();
        $menu['sections'][] = [
            'name' => 'Boissons',
            'items' => [
                ['name' => 'Café', 'description' => null, 'price' => 2, 'price_text' => '2€', 'category' => 'boisson', 'tags' => [], 'is_highlighted' => false],
                ['name' => 'Thé', 'description' => null, 'price' => null, 'price_text' => null, 'category' => 'boisson', 'tags' => [], 'is_highlighted' => false],
            ],
        ];

        $report = $this->analyzer->analyze($menu);

        $this->assertSame(['entree', 'plat', 'dessert'], array_column($report['categories'], 'category'));
        $this->assertSame('boisson', $report['skipped'][0]['category']);
        $this->assertNotEmpty(array_filter($report['notes'], fn (string $note) => str_contains($note, 'sans prix')));
        $this->assertNotEmpty(array_filter($report['notes'], fn (string $note) => str_contains($note, 'plus de 7 plats')));
    }

    public function test_the_demo_menu_gets_a_score_between_zero_and_one_hundred(): void
    {
        $report = $this->analyzer->analyze(MockData::menu());

        $this->assertGreaterThanOrEqual(0, $report['score']);
        $this->assertLessThanOrEqual(100, $report['score']);

        // Dans la carte de démo, le foie gras à 19 € casse l'ouverture de gamme des entrées.
        $entrees = array_column($report['categories'], null, 'category')['entree'];
        $this->assertSame('warning', $entrees['principles'][0]['status']);
    }

    /** @return array{name: string, price: float, highlighted: bool} */
    private function dish(string $name, float $price, bool $highlighted = false): array
    {
        return ['name' => $name, 'price' => $price, 'highlighted' => $highlighted];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, string>
     */
    private function statusesByKey(array $report): array
    {
        return array_column($report['principles'], 'status', 'key');
    }
}

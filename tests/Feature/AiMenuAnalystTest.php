<?php

namespace Tests\Feature;

use App\Ai\Agents\MenuOptimizer;
use App\Ai\Agents\MenuReader;
use App\Exceptions\MenuAnalysisException;
use App\Models\Analysis;
use App\Services\Ai\AiMenuAnalyst;
use App\Services\Omnes\OmnesAnalyzer;
use App\Support\MockData;
use Tests\TestCase;

class AiMenuAnalystTest extends TestCase
{
    public function test_it_reads_a_menu_from_photos_through_the_reader_agent(): void
    {
        MenuReader::fake([MockData::menu()]);

        $menu = app(AiMenuAnalyst::class)->extract(['fake-jpeg-binary']);

        $this->assertSame('Chez Lucien', $menu['restaurant_name']);
        $this->assertCount(3, $menu['sections']);
        MenuReader::assertPrompted(fn ($prompt) => count($prompt->attachments) === 1 && $prompt->contains('1 photo(s)'));
    }

    public function test_it_optimises_a_menu_with_the_restaurant_context(): void
    {
        MenuOptimizer::fake([MockData::optimization()]);
        $analysis = new Analysis([
            'restaurant_name' => 'Chez Lucien',
            'city' => 'Lyon',
            'restaurant_type' => 'Bistrot / brasserie',
            'goal' => 'Augmenter le ticket moyen',
        ]);

        $optimization = app(AiMenuAnalyst::class)->optimize(
            MockData::menu(),
            (new OmnesAnalyzer)->analyze(MockData::menu()),
            $analysis,
        );

        $this->assertSame(58, $optimization['overall_score']);
        $this->assertArrayHasKey('optimized_menu', $optimization);
        MenuOptimizer::assertPrompted(fn ($prompt) => $prompt->contains('Objectif du restaurateur : Augmenter le ticket moyen')
            && $prompt->contains('Analyse Omnes'));
    }

    public function test_it_rejects_an_empty_photo_list(): void
    {
        MenuReader::fake([MockData::menu()]);

        $this->expectException(MenuAnalysisException::class);

        app(AiMenuAnalyst::class)->extract([]);
    }
}

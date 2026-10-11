<?php

namespace Tests\Feature;

use App\Ai\Agents\MenuOptimizer;
use App\Ai\Agents\MenuReader;
use App\Exceptions\MenuAnalysisException;
use App\Models\Analysis;
use App\Services\Ai\AiMenuAnalyst;
use App\Services\Omnes\OmnesAnalyzer;
use App\Support\MockData;
use Illuminate\Support\Facades\Log;
use Mockery;
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

    public function test_an_unusable_optimisation_response_is_logged_before_failing(): void
    {
        MenuOptimizer::fake(['{"overall_score": 58, "optimized_menu": {"title": "Chez Lu']);
        Log::spy();

        try {
            app(AiMenuAnalyst::class)->optimize(
                MockData::menu(),
                (new OmnesAnalyzer)->analyze(MockData::menu()),
                new Analysis(['restaurant_name' => 'Chez Lucien', 'city' => 'Lyon', 'restaurant_type' => 'Bistrot / brasserie']),
            );
            $this->fail('Une réponse tronquée doit faire échouer l’optimisation.');
        } catch (MenuAnalysisException $exception) {
            $this->assertSame("Réponse du modèle inexploitable pendant l'optimisation.", $exception->getMessage());
        }

        Log::shouldHaveReceived('warning')->with('Unusable model response', Mockery::on(
            fn (array $context): bool => $context['step'] === "l'optimisation"
                && $context['finish_reason'] === 'stop'
                && $context['text_length'] === 58
                && $context['text_tail'] === '{"overall_score": 58, "optimized_menu": {"title": "Chez Lu',
        ))->once();
    }

    public function test_it_rejects_an_empty_photo_list(): void
    {
        MenuReader::fake([MockData::menu()]);

        $this->expectException(MenuAnalysisException::class);

        app(AiMenuAnalyst::class)->extract([]);
    }
}

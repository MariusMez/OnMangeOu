<?php

namespace App\Jobs;

use App\Contracts\MenuAnalyst;
use App\Enums\AnalysisStatus;
use App\Models\Analysis;
use App\Services\Claude\ClaudeErrorMessage;
use App\Services\DishImporter;
use App\Services\ImageStore;
use App\Services\Omnes\OmnesAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pipeline d'une analyse : photos → extraction LLM → principes d'Omnes →
 * base de plats → optimisation LLM. Le statut de l'analyse est mis à jour
 * à chaque étape pour que la page de résultat suive la progression.
 */
class RunMenuAnalysis implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public Analysis $analysis) {}

    public function handle(
        MenuAnalyst $analyst,
        OmnesAnalyzer $omnes,
        ImageStore $images,
        DishImporter $dishes,
    ): void {
        $this->analysis->update(['status' => AnalysisStatus::Extracting]);

        $menu = $analyst->extract($images->all($this->analysis));
        $report = $omnes->analyze($menu);
        $this->analysis->update([
            'menu' => $menu,
            'omnes' => $report,
            'status' => AnalysisStatus::Optimizing,
        ]);
        $dishes->import($this->analysis, $menu);

        $optimization = $analyst->optimize($menu, $report, $this->analysis);
        $this->analysis->update([
            'optimization' => $optimization,
            'status' => AnalysisStatus::Done,
            'error' => null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->analysis->markFailed(ClaudeErrorMessage::describe($exception));

        Log::error('Menu analysis failed', [
            'analysis_id' => $this->analysis->id,
            'exception' => $exception,
        ]);
    }
}

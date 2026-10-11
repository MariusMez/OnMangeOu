<?php

namespace App\Actions;

use App\Enums\AnalysisStatus;
use App\Jobs\RunMenuAnalysis;
use App\Models\Analysis;

/**
 * Relance le pipeline d'une analyse échouée avec ses photos (ou son site web) d'origine.
 * Le job reprend la capture du site si elle n'avait pas abouti.
 */
class RetryMenuAnalysis
{
    public function handle(Analysis $analysis): void
    {
        $mock = (bool) config('opticarte.mock');

        $analysis->update([
            'status' => AnalysisStatus::Pending,
            'error' => null,
            'model' => $mock ? 'mock' : config('opticarte.model'),
            'mock' => $mock,
        ]);

        RunMenuAnalysis::dispatch($analysis);
    }
}

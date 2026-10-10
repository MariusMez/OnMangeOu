<?php

namespace App\Services\Ai;

use App\Contracts\MenuAnalyst;
use App\Models\Analysis;
use App\Support\MockData;

/** Mode démo : carte fictive, aucun appel API. */
class MockMenuAnalyst implements MenuAnalyst
{
    public function extract(array $images): array
    {
        usleep(300_000);

        return MockData::menu();
    }

    public function optimize(array $menu, array $omnes, Analysis $analysis): array
    {
        usleep(500_000);

        return MockData::optimization();
    }
}

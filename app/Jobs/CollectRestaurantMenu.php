<?php

namespace App\Jobs;

use App\Models\Restaurant;
use App\Services\Collect\MenuCollector;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Collecte d'un restaurant en file (Horizon) : photos, tri par l'IA, lecture des cartes, base de plats. */
class CollectRestaurantMenu implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 2;

    public int $backoff = 120;

    public int $uniqueFor = 3600;

    public function __construct(public Restaurant $restaurant, public int $maxPhotos) {}

    public function handle(MenuCollector $collector): void
    {
        $result = $collector->collect($this->restaurant, $this->maxPhotos);

        Log::info('Restaurant collected', ['restaurant_id' => $this->restaurant->id, ...$result]);
    }

    public function uniqueId(): string
    {
        return (string) $this->restaurant->id;
    }

    /** @return list<string> */
    public function tags(): array
    {
        return ['collect', 'restaurant:'.$this->restaurant->id];
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Restaurant collection failed', [
            'restaurant_id' => $this->restaurant->id,
            'exception' => $exception,
        ]);
    }
}

<?php

namespace App\Events;

use App\Models\Analysis;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Diffusé (Reverb) à chaque changement de statut pour rafraîchir la page de résultat. */
class AnalysisUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Analysis $analysis) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new Channel('analyses.'.$this->analysis->id)];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->analysis->id,
            'status' => $this->analysis->status->value,
            'label' => $this->analysis->status->label(),
            'running' => $this->analysis->isRunning(),
        ];
    }
}

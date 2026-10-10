<?php

namespace App\Enums;

enum AnalysisStatus: string
{
    case Pending = 'pending';
    case Capturing = 'capturing';
    case Extracting = 'extracting';
    case Optimizing = 'optimizing';
    case Done = 'done';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Capturing => 'Capture du site web…',
            self::Extracting => 'Lecture de la carte…',
            self::Optimizing => 'Optimisation…',
            self::Done => 'Terminée',
            self::Error => 'Erreur',
        };
    }

    /** Ton de la puce de statut (voir resources/views/components/chip.blade.php). */
    public function chipTone(): string
    {
        return match ($this) {
            self::Pending => 'neutral',
            self::Capturing, self::Extracting, self::Optimizing => 'secondary',
            self::Done => 'tertiary',
            self::Error => 'error',
        };
    }

    public function isRunning(): bool
    {
        return in_array($this, [self::Pending, self::Capturing, self::Extracting, self::Optimizing], true);
    }
}

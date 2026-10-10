<?php

namespace App\Enums;

enum AnalysisStatus: string
{
    case Pending = 'pending';
    case Extracting = 'extracting';
    case Optimizing = 'optimizing';
    case Done = 'done';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Extracting => 'Lecture de la carte…',
            self::Optimizing => 'Optimisation…',
            self::Done => 'Terminée',
            self::Error => 'Erreur',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-zinc-100 text-zinc-700',
            self::Extracting, self::Optimizing => 'bg-amber-100 text-amber-800',
            self::Done => 'bg-emerald-100 text-emerald-800',
            self::Error => 'bg-red-100 text-red-800',
        };
    }

    public function isRunning(): bool
    {
        return in_array($this, [self::Pending, self::Extracting, self::Optimizing], true);
    }
}

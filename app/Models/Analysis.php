<?php

namespace App\Models;

use App\Enums\AnalysisStatus;
use App\Events\AnalysisUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Analysis extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'status',
        'restaurant_name',
        'city',
        'restaurant_type',
        'goal',
        'website_url',
        'image_count',
        'model',
        'mock',
        'error',
        'menu',
        'omnes',
        'optimization',
    ];

    protected static function booted(): void
    {
        static::creating(function (Analysis $analysis): void {
            $analysis->id ??= bin2hex(random_bytes(4));
        });

        static::updated(function (Analysis $analysis): void {
            if ($analysis->wasChanged('status')) {
                AnalysisUpdated::dispatch($analysis);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => AnalysisStatus::class,
            'mock' => 'boolean',
            'image_count' => 'integer',
            'menu' => 'array',
            'omnes' => 'array',
            'optimization' => 'array',
        ];
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }

    public function fromWebsite(): bool
    {
        return $this->website_url !== null;
    }

    public function isRunning(): bool
    {
        return $this->status->isRunning();
    }

    public function isDone(): bool
    {
        return $this->status === AnalysisStatus::Done && $this->optimization !== null;
    }

    public function markFailed(string $message): void
    {
        $this->forceFill(['status' => AnalysisStatus::Error, 'error' => $message])->save();
    }

    /** @return array<string, mixed>|null */
    public function optimizedMenu(): ?array
    {
        return $this->optimization['optimized_menu'] ?? null;
    }
}

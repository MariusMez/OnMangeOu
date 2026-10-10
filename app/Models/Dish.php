<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dish extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'analysis_id',
        'restaurant_name',
        'city',
        'restaurant_type',
        'year',
        'section',
        'name',
        'description',
        'category',
        'price',
        'currency',
        'is_highlighted',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'price' => 'float',
            'is_highlighted' => 'boolean',
        ];
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }
}

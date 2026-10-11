<?php

namespace App\Models;

use App\Enums\DishSource;
use Database\Factories\DishFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dish extends Model
{
    /** @use HasFactory<DishFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'analysis_id',
        'restaurant_id',
        'source',
        'restaurant_name',
        'city',
        'postal_code',
        'departement_code',
        'region_code',
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
            'source' => DishSource::class,
            'year' => 'integer',
            'price' => 'float',
            'is_highlighted' => 'boolean',
        ];
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}

<?php

namespace App\Models;

use App\Enums\PhotoKind;
use Database\Factories\RestaurantPhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Photo déjà examinée sur la fiche d'un restaurant, reconnue à l'empreinte de sa miniature :
 * une photo n'est triée et lue qu'une fois, l'année où elle apparaît.
 */
class RestaurantPhoto extends Model
{
    /** @use HasFactory<RestaurantPhotoFactory> */
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'thumbnail_hash',
        'kind',
        'has_prices',
        'attribution',
        'path',
    ];

    protected function casts(): array
    {
        return [
            'kind' => PhotoKind::class,
            'has_prices' => 'boolean',
        ];
    }

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}

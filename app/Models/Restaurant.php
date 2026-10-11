<?php

namespace App\Models;

use App\Enums\RestaurantType;
use Database\Factories\RestaurantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Restaurant repéré par la collecte Google Places, avec sa localisation et sa classification. */
class Restaurant extends Model
{
    /** @use HasFactory<RestaurantFactory> */
    use HasFactory;

    public const string CLOSED_PERMANENTLY = 'CLOSED_PERMANENTLY';

    protected $fillable = [
        'google_place_id',
        'name',
        'address',
        'postal_code',
        'city',
        'departement_code',
        'region_code',
        'latitude',
        'longitude',
        'restaurant_type',
        'google_primary_type',
        'google_types',
        'price_level',
        'rating',
        'rating_count',
        'website_url',
        'business_status',
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_type' => RestaurantType::class,
            'google_types' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'price_level' => 'integer',
            'rating' => 'float',
            'rating_count' => 'integer',
            'collected_at' => 'datetime',
        ];
    }

    /** @return HasMany<RestaurantPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(RestaurantPhoto::class);
    }

    /** @return HasMany<Dish, $this> */
    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }

    public function isClosed(): bool
    {
        return $this->business_status === self::CLOSED_PERMANENTLY;
    }

    /** Jamais collecté, ou collecté il y a plus de $refreshDays jours. */
    public function needsCollection(int $refreshDays): bool
    {
        return $this->collected_at === null || $this->collected_at->lt(now()->subDays($refreshDays));
    }
}

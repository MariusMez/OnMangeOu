<?php

namespace App\Services\Collect;

use App\Enums\RestaurantType;
use Illuminate\Support\Str;

/**
 * Classification d'un restaurant dans les types OptiCarte : d'abord d'après ses types Google Places,
 * puis, s'il reste « Autre », d'après le type de cuisine lu sur sa carte.
 */
class RestaurantTypeClassifier
{
    /** Types Google Places (table A) reconnus, du plus spécifique au plus général. */
    private const array GOOGLE_TYPES = [
        'fine_dining_restaurant' => RestaurantType::Gastronomique,
        'creperie' => RestaurantType::Creperie,
        'pizza_restaurant' => RestaurantType::Pizzeria,
        'italian_restaurant' => RestaurantType::Pizzeria,
        'fast_food_restaurant' => RestaurantType::RestaurationRapide,
        'hamburger_restaurant' => RestaurantType::RestaurationRapide,
        'sandwich_shop' => RestaurantType::RestaurationRapide,
        'kebab_shop' => RestaurantType::RestaurationRapide,
        'meal_takeaway' => RestaurantType::RestaurationRapide,
        'food_court' => RestaurantType::RestaurationRapide,
        'chinese_restaurant' => RestaurantType::Asiatique,
        'japanese_restaurant' => RestaurantType::Asiatique,
        'sushi_restaurant' => RestaurantType::Asiatique,
        'ramen_restaurant' => RestaurantType::Asiatique,
        'thai_restaurant' => RestaurantType::Asiatique,
        'vietnamese_restaurant' => RestaurantType::Asiatique,
        'korean_restaurant' => RestaurantType::Asiatique,
        'indonesian_restaurant' => RestaurantType::Asiatique,
        'asian_restaurant' => RestaurantType::Asiatique,
        'wine_bar' => RestaurantType::BarAVin,
        'tapas_restaurant' => RestaurantType::BarAVin,
        'bar' => RestaurantType::BarAVin,
        'french_restaurant' => RestaurantType::Bistrot,
        'bistro' => RestaurantType::Bistrot,
        'brasserie' => RestaurantType::Bistrot,
        'steak_house' => RestaurantType::Bistrot,
        'gastropub' => RestaurantType::Bistrot,
        'indian_restaurant' => RestaurantType::CuisineDuMonde,
        'lebanese_restaurant' => RestaurantType::CuisineDuMonde,
        'middle_eastern_restaurant' => RestaurantType::CuisineDuMonde,
        'mediterranean_restaurant' => RestaurantType::CuisineDuMonde,
        'greek_restaurant' => RestaurantType::CuisineDuMonde,
        'turkish_restaurant' => RestaurantType::CuisineDuMonde,
        'mexican_restaurant' => RestaurantType::CuisineDuMonde,
        'spanish_restaurant' => RestaurantType::CuisineDuMonde,
        'african_restaurant' => RestaurantType::CuisineDuMonde,
        'moroccan_restaurant' => RestaurantType::CuisineDuMonde,
        'brazilian_restaurant' => RestaurantType::CuisineDuMonde,
        'peruvian_restaurant' => RestaurantType::CuisineDuMonde,
        'american_restaurant' => RestaurantType::CuisineDuMonde,
        'afghani_restaurant' => RestaurantType::CuisineDuMonde,
    ];

    /** Mots-clés du type de cuisine lu sur la carte (minuscules, sans accents). */
    private const array CUISINE_KEYWORDS = [
        'gastronom' => RestaurantType::Gastronomique,
        'creper' => RestaurantType::Creperie,
        'galette' => RestaurantType::Creperie,
        'pizz' => RestaurantType::Pizzeria,
        'italien' => RestaurantType::Pizzeria,
        'trattoria' => RestaurantType::Pizzeria,
        'burger' => RestaurantType::RestaurationRapide,
        'snack' => RestaurantType::RestaurationRapide,
        'kebab' => RestaurantType::RestaurationRapide,
        'fast' => RestaurantType::RestaurationRapide,
        'sushi' => RestaurantType::Asiatique,
        'japon' => RestaurantType::Asiatique,
        'chinois' => RestaurantType::Asiatique,
        'thai' => RestaurantType::Asiatique,
        'vietnam' => RestaurantType::Asiatique,
        'asiat' => RestaurantType::Asiatique,
        'tapas' => RestaurantType::BarAVin,
        'bar a vin' => RestaurantType::BarAVin,
        'bistro' => RestaurantType::Bistrot,
        'brasserie' => RestaurantType::Bistrot,
        'bouchon' => RestaurantType::Bistrot,
        'traditionnel' => RestaurantType::Bistrot,
        'francais' => RestaurantType::Bistrot,
        'libanais' => RestaurantType::CuisineDuMonde,
        'indien' => RestaurantType::CuisineDuMonde,
        'mexicain' => RestaurantType::CuisineDuMonde,
        'grec' => RestaurantType::CuisineDuMonde,
        'marocain' => RestaurantType::CuisineDuMonde,
        'oriental' => RestaurantType::CuisineDuMonde,
        'monde' => RestaurantType::CuisineDuMonde,
    ];

    /** @param  list<string>  $types */
    public function fromGoogle(?string $primaryType, array $types, ?int $priceLevel): RestaurantType
    {
        foreach (array_filter([$primaryType, ...$types]) as $type) {
            if (isset(self::GOOGLE_TYPES[$type])) {
                return self::GOOGLE_TYPES[$type];
            }
        }

        return $priceLevel === 4 ? RestaurantType::Gastronomique : RestaurantType::Autre;
    }

    public function fromCuisine(?string $cuisine): ?RestaurantType
    {
        if (blank($cuisine)) {
            return null;
        }

        $normalized = Str::of($cuisine)->ascii()->lower()->toString();

        foreach (self::CUISINE_KEYWORDS as $keyword => $type) {
            if (str_contains($normalized, $keyword)) {
                return $type;
            }
        }

        return null;
    }

    /** Affine un type « Autre » grâce au type de cuisine lu sur la carte. */
    public function refine(RestaurantType $current, ?string $cuisine): RestaurantType
    {
        return $current === RestaurantType::Autre ? ($this->fromCuisine($cuisine) ?? $current) : $current;
    }
}

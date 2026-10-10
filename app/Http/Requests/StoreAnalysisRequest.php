<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnalysisRequest extends FormRequest
{
    public const array RESTAURANT_TYPES = [
        'Bistrot / brasserie',
        'Pizzeria / italien',
        'Gastronomique',
        'Restauration rapide',
        'Asiatique',
        'Crêperie',
        'Bar à vin / tapas',
        'Cuisine du monde',
        'Autre',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'restaurant_name' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'restaurant_type' => ['required', 'string', 'max:80'],
            'goal' => ['nullable', 'string', 'max:500'],
            'images' => ['required', 'array', 'min:1', 'max:'.config('opticarte.max_images')],
            'images.*' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.config('opticarte.max_image_kb')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'restaurant_name.required' => 'Le nom du restaurant est requis.',
            'city.required' => 'La ville est requise.',
            'restaurant_type.required' => 'Le type de restaurant est requis.',
            'images.required' => 'Ajoutez au moins une photo de la carte.',
            'images.min' => 'Ajoutez au moins une photo de la carte.',
            'images.max' => 'Maximum :max photos par analyse.',
            'images.*.image' => 'Chaque fichier doit être une image.',
            'images.*.mimes' => 'Formats acceptés : JPG, PNG, WEBP.',
            'images.*.max' => 'Chaque photo doit faire moins de 25 Mo.',
        ];
    }
}

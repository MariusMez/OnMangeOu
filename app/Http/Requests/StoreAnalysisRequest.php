<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnalysisRequest extends FormRequest
{
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
            'website_url' => ['nullable', 'url:http,https', 'max:500'],
            'images' => ['required_without:website_url', 'array', 'max:'.config('opticarte.max_images')],
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
            'images.required_without' => 'Ajoutez au moins une photo de la carte, ou l’adresse du site du restaurant.',
            'website_url.url' => 'L’adresse du site doit commencer par http:// ou https://.',
            'images.max' => 'Maximum :max photos par analyse.',
            'images.*.image' => 'Chaque fichier doit être une image.',
            'images.*.mimes' => 'Formats acceptés : JPG, PNG, WEBP.',
            'images.*.max' => 'Chaque photo doit faire moins de 25 Mo.',
        ];
    }
}

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OptiCarte
    |--------------------------------------------------------------------------
    |
    | Réglages du pipeline d'analyse de cartes : modèle Claude utilisé,
    | mode démo sans appel API, limites d'upload et disque de stockage
    | des photos normalisées.
    |
    */

    'model' => env('OPTICARTE_MODEL', 'claude-opus-5-5'),

    'mock' => (bool) env('OPTICARTE_MOCK', false),

    'max_images' => 6,

    'max_image_kb' => 25 * 1024,

    'disk' => env('OPTICARTE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Collecte de restaurants (commande opticarte:collect)
    |--------------------------------------------------------------------------
    |
    | Recherche des restaurants d'une zone sur Google Places, tri des photos
    | par IA puis lecture des cartes. La clé Google Places se règle dans
    | config/services.php (GOOGLE_PLACES_API_KEY).
    |
    */

    'collect' => [
        'classifier_model' => env('OPTICARTE_CLASSIFIER_MODEL', 'claude-opus-5-5'),
        'geo_api_url' => env('OPTICARTE_GEO_API_URL', 'https://geo.api.gouv.fr'),
        'cell_radius_m' => 1500,
        'max_photos' => 10,
        'thumbnail_px' => 512,
        'refresh_days' => 180,
        'keep_photos' => (bool) env('OPTICARTE_KEEP_PLACE_PHOTOS', false),
    ],

];

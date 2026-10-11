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

];

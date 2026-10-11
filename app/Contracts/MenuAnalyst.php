<?php

namespace App\Contracts;

use App\Models\Analysis;

/**
 * Frontière avec le modèle de langage : lecture d'une carte à partir de photos,
 * puis production du diagnostic et de la carte optimisée.
 */
interface MenuAnalyst
{
    /**
     * @param  list<string>  $images  Photos JPEG (contenu binaire)
     * @return array<string, mixed> Carte extraite, forme de App\Claude\Schemas\ExtractedMenu
     */
    public function extract(array $images): array;

    /**
     * @param  array<string, mixed>  $menu  Carte extraite
     * @param  array<string, mixed>  $omnes  Rapport des principes d'Omnes
     * @return array<string, mixed> Diagnostic, forme de App\Claude\Schemas\Optimization
     */
    public function optimize(array $menu, array $omnes, Analysis $analysis): array;
}

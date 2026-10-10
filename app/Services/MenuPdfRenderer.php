<?php

namespace App\Services;

use App\Models\Analysis;
use Barryvdh\DomPDF\Facade\Pdf;

/** Carte optimisée en PDF A4, rendue depuis la vue Blade pdf.menu. */
class MenuPdfRenderer
{
    /** @param  array<string, mixed>  $menu */
    public function render(array $menu, Analysis $analysis): string
    {
        return Pdf::loadView('pdf.menu', [
            'menu' => $menu,
            'analysis' => $analysis,
        ])
            ->setPaper('a4')
            ->output();
    }
}

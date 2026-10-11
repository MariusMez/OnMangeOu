<?php

namespace App\Services;

use App\Models\Analysis;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

/** Carte optimisée en PDF A4, rendue par Chromium (laravel-pdf / Browsershot) depuis la vue pdf.menu. */
class MenuPdfRenderer
{
    /** @param  array<string, mixed>  $menu */
    public function build(array $menu, Analysis $analysis): PdfBuilder
    {
        return Pdf::view('pdf.menu', ['menu' => $menu, 'analysis' => $analysis])
            ->format(Format::A4)
            ->margins(14, 16, 14, 16)
            ->name(self::filename($analysis));
    }

    public static function filename(Analysis $analysis): string
    {
        return 'carte-optimisee-'.(Str::slug($analysis->restaurant_name) ?: 'carte').'.pdf';
    }
}

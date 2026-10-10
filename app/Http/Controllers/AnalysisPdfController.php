<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Services\MenuPdfRenderer;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnalysisPdfController extends Controller
{
    public function show(Analysis $analysis, MenuPdfRenderer $renderer): Response
    {
        $menu = $analysis->optimizedMenu();

        if ($menu === null) {
            throw new NotFoundHttpException('Analyse non terminée.');
        }

        $filename = 'carte-optimisee-'.(Str::slug($analysis->restaurant_name) ?: 'carte').'.pdf';

        return response($renderer->render($menu, $analysis), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Services\MenuPdfRenderer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnalysisPdfController extends Controller
{
    public function show(Request $request, Analysis $analysis, MenuPdfRenderer $renderer): Response
    {
        $menu = $analysis->optimizedMenu();

        if ($menu === null) {
            throw new NotFoundHttpException('Analyse non terminée.');
        }

        return $renderer->build($menu, $analysis)
            ->download(MenuPdfRenderer::filename($analysis))
            ->headers(['Cache-Control' => 'no-store'])
            ->toResponse($request);
    }
}

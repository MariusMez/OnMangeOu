<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Services\ImageStore;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnalysisImageController extends Controller
{
    public function show(Analysis $analysis, int $index, ImageStore $images): Response
    {
        $binary = $images->get($analysis, $index);

        if ($binary === null) {
            throw new NotFoundHttpException;
        }

        return response($binary, 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}

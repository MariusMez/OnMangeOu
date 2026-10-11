<?php

namespace App\Http\Controllers;

use App\Actions\RetryMenuAnalysis;
use App\Actions\StartMenuAnalysis;
use App\Enums\AnalysisStatus;
use App\Enums\RestaurantType;
use App\Http\Requests\StoreAnalysisRequest;
use App\Models\Analysis;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnalysisController extends Controller
{
    public function index(): View
    {
        $analyses = Analysis::query()
            ->withCount('dishes')
            ->latest()
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('analyses.index', ['analyses' => $analyses]);
    }

    public function create(): View
    {
        return view('analyses.create', [
            'restaurantTypes' => RestaurantType::values(),
            'recentAnalyses' => Analysis::query()
                ->withCount('dishes')
                ->latest()
                ->orderByDesc('id')
                ->limit(3)
                ->get(),
        ]);
    }

    public function store(StoreAnalysisRequest $request, StartMenuAnalysis $start): RedirectResponse
    {
        $analysis = $start->handle(
            $request->safe()->only(['restaurant_name', 'city', 'restaurant_type', 'goal', 'website_url']),
            $request->file('images', []),
        );

        return redirect()->route('analyses.show', $analysis);
    }

    public function show(Analysis $analysis): View
    {
        return view('analyses.show', ['analysis' => $analysis]);
    }

    public function retry(Analysis $analysis, RetryMenuAnalysis $retry): RedirectResponse
    {
        if ($analysis->status !== AnalysisStatus::Error) {
            throw new NotFoundHttpException('Seule une analyse échouée peut être relancée.');
        }

        $retry->handle($analysis);

        return redirect()->route('analyses.show', $analysis);
    }
}

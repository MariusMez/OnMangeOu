<?php

namespace App\Http\Controllers;

use App\Actions\StartMenuAnalysis;
use App\Http\Requests\StoreAnalysisRequest;
use App\Models\Analysis;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AnalysisController extends Controller
{
    public function index(): View
    {
        $analyses = Analysis::query()
            ->latest()
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('analyses.index', ['analyses' => $analyses]);
    }

    public function create(): View
    {
        return view('analyses.create', [
            'restaurantTypes' => StoreAnalysisRequest::RESTAURANT_TYPES,
        ]);
    }

    public function store(StoreAnalysisRequest $request, StartMenuAnalysis $start): RedirectResponse
    {
        $analysis = $start->handle(
            $request->safe()->only(['restaurant_name', 'city', 'restaurant_type', 'goal']),
            $request->file('images', []),
        );

        return redirect()->route('analyses.show', $analysis);
    }

    public function show(Analysis $analysis): View
    {
        return view('analyses.show', ['analysis' => $analysis]);
    }
}

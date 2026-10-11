<?php

namespace App\Http\Controllers;

use App\Services\PriceObservatory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index(Request $request, PriceObservatory $observatory): View
    {
        $city = $request->string('city')->trim()->toString() ?: null;
        $type = $request->string('type')->trim()->toString() ?: null;

        return view('stats.index', [
            'city' => $city,
            'type' => $type,
            'summary' => $observatory->summary(),
            'filters' => $observatory->filters(),
            'stats' => $observatory->priceStats($city, $type),
            'trend' => $observatory->yearTrend($city, $type),
            'topDishes' => $observatory->topDishes(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\PriceObservatory;
use App\Support\France;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index(Request $request, PriceObservatory $observatory): View
    {
        $city = $request->string('city')->trim()->toString() ?: null;
        $type = $request->string('type')->trim()->toString() ?: null;
        $departement = France::findDepartement($request->string('departement')->trim()->toString() ?: '-');
        $region = France::findRegion($request->string('region')->trim()->toString() ?: '-');
        $postalCode = preg_match('/^\d{5}$/', $request->string('postal_code')->trim()->toString()) === 1
            ? $request->string('postal_code')->trim()->toString()
            : null;

        $filter = array_filter([
            'city' => $city,
            'type' => $type,
            'postal_codes' => $postalCode !== null ? [$postalCode] : null,
            'departement_codes' => $departement !== null ? [$departement] : null,
            'region_code' => $region,
        ]);

        return view('stats.index', [
            'city' => $city,
            'type' => $type,
            'departement' => $departement,
            'region' => $region,
            'postalCode' => $postalCode,
            'summary' => $observatory->summary(),
            'filters' => $observatory->filters(),
            'stats' => $observatory->priceStats($filter),
            'trend' => $observatory->yearTrend($filter),
            'coverage' => $observatory->coverage($filter),
            'priceIndex' => $observatory->priceIndex($filter),
            'restaurants' => $observatory->restaurants($filter),
            'topDishes' => $observatory->topDishes(),
        ]);
    }
}

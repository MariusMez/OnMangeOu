@extends('layouts.app')

@section('title', 'Observatoire des prix')

@php
    use App\Enums\DishCategory;
    use App\Support\Price;
@endphp

@section('content')
    <div class="grid gap-8">
        <header class="grid gap-2">
            <h1 class="text-2xl font-semibold tracking-tight">Observatoire des prix</h1>
            <p class="text-sm text-muted">
                Chaque carte analysée alimente une base de plats datée et localisée :
                {{ $summary['dishes'] }} plats, {{ $summary['restaurants'] }} restaurants, {{ $summary['analyses'] }} analyses terminées.
            </p>
        </header>

        <form method="GET" action="{{ route('stats.index') }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-border bg-card p-4 text-sm">
            <label class="grid gap-1">
                <span class="text-xs uppercase tracking-wide text-muted">Ville</span>
                <select name="city" class="rounded-md border border-border bg-white px-3 py-1.5">
                    <option value="">Toutes</option>
                    @foreach ($filters['cities'] as $option)
                        <option value="{{ $option }}" @selected($option === $city)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1">
                <span class="text-xs uppercase tracking-wide text-muted">Type</span>
                <select name="type" class="rounded-md border border-border bg-white px-3 py-1.5">
                    <option value="">Tous</option>
                    @foreach ($filters['types'] as $option)
                        <option value="{{ $option }}" @selected($option === $type)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>
            <button class="rounded-md bg-accent px-3 py-1.5 text-white">Filtrer</button>
        </form>

        @if ($stats->isEmpty())
            <p class="text-muted">Aucune donnée pour ces filtres. Analysez une carte pour alimenter l’observatoire.</p>
        @else
            <section class="grid gap-3">
                <h2 class="text-lg font-semibold">Évolution du prix moyen par année</h2>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($trend as $category => $rows)
                        <div class="rounded-xl border border-border bg-card p-4 text-sm">
                            <h3 class="font-semibold">{{ DishCategory::labelFor($category) }}</h3>
                            <table class="mt-2 w-full">
                                <tbody>
                                    @foreach ($rows->values() as $index => $row)
                                        @php $previous = $rows->values()->get($index - 1); @endphp
                                        <tr class="border-t border-border">
                                            <td class="py-1">{{ $row->year }}</td>
                                            <td class="py-1 text-right font-medium">{{ Price::euro($row->avg_price) }}</td>
                                            <td class="py-1 text-right text-xs text-muted">
                                                @if ($previous && $previous->avg_price > 0)
                                                    @php $delta = ($row->avg_price - $previous->avg_price) / $previous->avg_price * 100; @endphp
                                                    {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, 1, ',', ' ') }} %
                                                @else
                                                    {{ $row->dishes }} plats
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="grid gap-3">
                <h2 class="text-lg font-semibold">Prix par zone, type de restaurant et catégorie</h2>
                <div class="overflow-x-auto rounded-xl border border-border bg-card">
                    <table class="w-full text-sm">
                        <thead class="bg-background text-left text-xs uppercase tracking-wide text-muted">
                            <tr>
                                <th class="px-3 py-2">Année</th>
                                <th class="px-3 py-2">Ville</th>
                                <th class="px-3 py-2">Type</th>
                                <th class="px-3 py-2">Catégorie</th>
                                <th class="px-3 py-2 text-right">Plats</th>
                                <th class="px-3 py-2 text-right">Restaurants</th>
                                <th class="px-3 py-2 text-right">Moyenne</th>
                                <th class="px-3 py-2 text-right">Min</th>
                                <th class="px-3 py-2 text-right">Max</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stats as $row)
                                <tr class="border-t border-border">
                                    <td class="px-3 py-1.5">{{ $row->year }}</td>
                                    <td class="px-3 py-1.5">{{ $row->city }}</td>
                                    <td class="px-3 py-1.5">{{ $row->restaurant_type }}</td>
                                    <td class="px-3 py-1.5">{{ DishCategory::labelFor($row->category) }}</td>
                                    <td class="px-3 py-1.5 text-right">{{ $row->dishes }}</td>
                                    <td class="px-3 py-1.5 text-right">{{ $row->restaurants }}</td>
                                    <td class="px-3 py-1.5 text-right font-medium">{{ Price::euro($row->avg_price) }}</td>
                                    <td class="px-3 py-1.5 text-right text-muted">{{ Price::euro($row->min_price) }}</td>
                                    <td class="px-3 py-1.5 text-right text-muted">{{ Price::euro($row->max_price) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($topDishes->isNotEmpty())
            <section class="grid gap-3">
                <h2 class="text-lg font-semibold">Plats les plus fréquents</h2>
                <div class="overflow-x-auto rounded-xl border border-border bg-card">
                    <table class="w-full text-sm">
                        <thead class="bg-background text-left text-xs uppercase tracking-wide text-muted">
                            <tr>
                                <th class="px-3 py-2">Plat</th>
                                <th class="px-3 py-2">Catégorie</th>
                                <th class="px-3 py-2 text-right">Cartes</th>
                                <th class="px-3 py-2 text-right">Prix moyen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topDishes as $dish)
                                <tr class="border-t border-border">
                                    <td class="px-3 py-1.5 capitalize">{{ $dish->name }}</td>
                                    <td class="px-3 py-1.5">{{ DishCategory::labelFor($dish->category) }}</td>
                                    <td class="px-3 py-1.5 text-right">{{ $dish->occurrences }}</td>
                                    <td class="px-3 py-1.5 text-right font-medium">{{ Price::euro($dish->avg_price) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endsection

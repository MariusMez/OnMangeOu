@extends('layouts.app')

@section('title', 'Observatoire des prix')

@php
    use App\Enums\DishCategory;
    use App\Support\Price;
@endphp

@section('content')
    <div class="grid gap-12">
        <section class="grid gap-8 lg:grid-cols-12 lg:items-end">
            <div class="grid gap-4 lg:col-span-7">
                <span class="text-xs font-bold uppercase tracking-widest text-primary">Observatoire</span>
                <h1 class="font-serif text-5xl font-bold leading-tight tracking-tight md:text-6xl">Les prix du marché,<br>rubrique par rubrique.</h1>
                <p class="max-w-lg text-lg text-on-surface-variant">
                    Chaque carte analysée alimente une base de plats datée et localisée :
                    {{ $summary['dishes'] }} plats, {{ $summary['restaurants'] }} restaurants, {{ $summary['analyses'] }} analyses terminées.
                </p>
            </div>
            <div class="grid gap-4 lg:col-span-5">
                <div class="rounded-3xl border-l-4 border-primary bg-surface-container-lowest p-8">
                    <span class="mb-1 block text-sm text-on-surface-variant">Plats référencés</span>
                    <div class="flex items-baseline gap-3">
                        <span class="font-serif text-5xl font-bold text-primary">{{ $summary['dishes'] }}</span>
                        <span class="flex items-center gap-1 text-sm font-bold text-tertiary"><x-icon name="trending-up" class="size-4" />{{ $summary['restaurants'] }} restaurants</span>
                    </div>
                    <p class="mt-4 text-xs font-medium italic text-on-surface-variant">Issus de {{ $summary['analyses'] }} analyses terminées, photos et sites web confondus.</p>
                </div>
            </div>
        </section>

        <form method="GET" action="{{ route('stats.index') }}" class="flex flex-wrap items-end gap-4 rounded-3xl bg-surface-container-low p-6 text-sm">
            <label class="grid gap-1.5">
                <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Ville</span>
                <select name="city" class="rounded-xl border border-outline-variant/15 bg-surface-container-lowest px-4 py-2.5 font-medium focus:border-primary focus:outline-none focus:ring-0">
                    <option value="">Toutes</option>
                    @foreach ($filters['cities'] as $option)
                        <option value="{{ $option }}" @selected($option === $city)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1.5">
                <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Type</span>
                <select name="type" class="rounded-xl border border-outline-variant/15 bg-surface-container-lowest px-4 py-2.5 font-medium focus:border-primary focus:outline-none focus:ring-0">
                    <option value="">Tous</option>
                    @foreach ($filters['types'] as $option)
                        <option value="{{ $option }}" @selected($option === $type)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>
            <button class="rounded-xl bg-primary px-5 py-2.5 font-bold text-on-primary hover:opacity-90">Filtrer</button>
        </form>

        @if ($stats->isEmpty())
            <p class="rounded-3xl bg-surface-container-lowest p-8 text-on-surface-variant">Aucune donnée pour ces filtres. Analysez une carte pour alimenter l’observatoire.</p>
        @else
            <section class="grid gap-6">
                <h2 class="font-serif text-3xl font-bold">Prix moyen par année</h2>
                <div class="grid gap-6 md:grid-cols-3">
                    @foreach ($trend as $category => $rows)
                        <div class="flex h-full flex-col justify-between gap-6 rounded-3xl bg-surface-container-low p-8">
                            <div class="grid gap-3">
                                <x-icon name="tag" class="text-primary" />
                                <h3 class="font-bold tracking-tight">{{ DishCategory::labelFor($category) }}</h3>
                            </div>
                            <div class="grid gap-3">
                                @foreach ($rows->values() as $index => $row)
                                    @php $previous = $rows->values()->get($index - 1); @endphp
                                    <div class="flex items-baseline justify-between gap-3">
                                        <span class="text-sm font-bold text-on-surface-variant">{{ $row->year }}</span>
                                        <span class="font-serif text-3xl font-bold">{{ Price::euro($row->avg_price) }}</span>
                                        <span class="text-xs font-medium text-on-surface-variant">
                                            @if ($previous && $previous->avg_price > 0)
                                                @php $delta = ($row->avg_price - $previous->avg_price) / $previous->avg_price * 100; @endphp
                                                <span class="{{ $delta >= 0 ? 'text-primary' : 'text-tertiary' }}">{{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, 1, ',', ' ') }} %</span>
                                            @else
                                                {{ $row->dishes }} plats
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="grid gap-6">
                <h2 class="font-serif text-3xl font-bold">Prix par zone, type et catégorie</h2>
                <div class="overflow-x-auto rounded-3xl bg-surface-container-lowest p-2">
                    <table class="w-full text-sm">
                        <thead class="text-left text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                            <tr>
                                <th class="px-4 py-3">Année</th>
                                <th class="px-4 py-3">Ville</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Catégorie</th>
                                <th class="px-4 py-3 text-right">Plats</th>
                                <th class="px-4 py-3 text-right">Restaurants</th>
                                <th class="px-4 py-3 text-right">Moyenne</th>
                                <th class="px-4 py-3 text-right">Min</th>
                                <th class="px-4 py-3 text-right">Max</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stats as $row)
                                <tr class="{{ $loop->odd ? 'bg-surface-container-low' : '' }}">
                                    <td class="rounded-l-2xl px-4 py-3">{{ $row->year }}</td>
                                    <td class="px-4 py-3">{{ $row->city }}</td>
                                    <td class="px-4 py-3">{{ $row->restaurant_type }}</td>
                                    <td class="px-4 py-3 font-bold">{{ DishCategory::labelFor($row->category) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $row->dishes }}</td>
                                    <td class="px-4 py-3 text-right">{{ $row->restaurants }}</td>
                                    <td class="px-4 py-3 text-right font-serif text-base font-bold text-primary">{{ Price::euro($row->avg_price) }}</td>
                                    <td class="px-4 py-3 text-right text-on-surface-variant">{{ Price::euro($row->min_price) }}</td>
                                    <td class="rounded-r-2xl px-4 py-3 text-right text-on-surface-variant">{{ Price::euro($row->max_price) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($topDishes->isNotEmpty())
            <section class="grid gap-6">
                <h2 class="font-serif text-3xl font-bold">Plats les plus fréquents</h2>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($topDishes as $dish)
                        <div class="flex overflow-hidden rounded-3xl bg-surface-container-lowest transition-transform hover:-translate-y-1">
                            <div class="w-4 shrink-0 bg-primary"></div>
                            <div class="grid flex-1 gap-4 p-6">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="font-serif text-xl font-bold capitalize">{{ $dish->name }}</h3>
                                        <span class="text-xs font-bold uppercase tracking-tighter text-on-surface-variant">{{ DishCategory::labelFor($dish->category) }}</span>
                                    </div>
                                    <x-chip tone="tertiary">{{ $dish->occurrences }} cartes</x-chip>
                                </div>
                                <div class="grid grid-cols-2 gap-4 rounded-2xl bg-surface-container-low p-4">
                                    <div>
                                        <span class="mb-1 block text-[10px] font-bold uppercase text-outline">Prix moyen</span>
                                        <span class="font-serif text-lg font-bold text-primary">{{ Price::euro($dish->avg_price) }}</span>
                                    </div>
                                    <div>
                                        <span class="mb-1 block text-[10px] font-bold uppercase text-outline">Présence</span>
                                        <span class="font-serif text-lg">{{ $dish->occurrences }} cartes</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection

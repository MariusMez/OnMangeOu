@extends('layouts.app')

@section('title', $analysis->restaurant_name)

@section('head')
    @if ($analysis->isRunning())
        <meta http-equiv="refresh" content="3">
    @endif
@endsection

@php
    use App\Enums\DishCategory;
    use App\Enums\MenuEngineeringClass;
    use App\Support\Price;

    $menu = $analysis->menu;
    $omnes = $analysis->omnes;
    $opt = $analysis->optimization;
@endphp

@section('content')
    <div class="grid gap-8">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">{{ $analysis->restaurant_name }}</h1>
                <p class="text-sm text-muted">
                    {{ $analysis->city }} · {{ $analysis->restaurant_type }} · {{ $analysis->created_at->format('d/m/Y H:i') }}
                    @if ($analysis->mock) · démo @endif
                </p>
                @if ($analysis->goal)
                    <p class="mt-1 text-sm">Objectif : {{ $analysis->goal }}</p>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <x-status-badge :status="$analysis->status" />
                @if ($analysis->isDone())
                    <a href="{{ route('analyses.pdf', $analysis) }}"
                       class="rounded-md bg-accent px-4 py-2 text-sm font-medium text-white hover:opacity-90">
                        Télécharger la carte optimisée (PDF)
                    </a>
                @endif
            </div>
        </header>

        @if ($analysis->status === \App\Enums\AnalysisStatus::Error)
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <p class="font-medium">L’analyse a échoué.</p>
                <p>{{ $analysis->error }}</p>
                <a href="{{ route('home') }}" class="mt-2 inline-block underline">Relancer une analyse</a>
            </div>
        @endif

        @if ($analysis->isRunning())
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                @if ($analysis->status === \App\Enums\AnalysisStatus::Optimizing)
                    Carte lue. Le consultant IA rédige le diagnostic et la carte optimisée (1 à 3 minutes)…
                @else
                    Lecture des photos en cours… Cette page se rafraîchit automatiquement.
                @endif
            </div>
        @endif

        @if ($analysis->image_count > 0)
            <section class="grid gap-2">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted">Photos analysées</h2>
                <div class="flex flex-wrap gap-3">
                    @for ($i = 1; $i <= $analysis->image_count; $i++)
                        <a href="{{ route('analyses.image', [$analysis, $i]) }}" target="_blank" rel="noreferrer">
                            <img src="{{ route('analyses.image', [$analysis, $i]) }}" alt="Photo {{ $i }} de la carte"
                                 class="h-28 rounded-md border border-border object-cover">
                        </a>
                    @endfor
                </div>
            </section>
        @endif

        @if ($opt)
            <section class="grid gap-4 rounded-xl border border-border bg-card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">Diagnostic</h2>
                    @php $score = (int) round($opt['overall_score']); @endphp
                    <div class="text-right">
                        <div class="text-xs uppercase tracking-wide text-muted">Note de la carte actuelle</div>
                        <div class="text-2xl font-semibold {{ $score >= 75 ? 'text-emerald-700' : ($score >= 50 ? 'text-amber-700' : 'text-red-700') }}">
                            {{ $score }} / 100
                        </div>
                    </div>
                </div>
                <p class="leading-relaxed">{{ $opt['summary'] }}</p>
                @if (! empty($opt['strengths']))
                    <div>
                        <h3 class="mb-1 text-sm font-semibold text-muted">Points forts</h3>
                        <ul class="list-disc pl-5 text-sm">
                            @foreach ($opt['strengths'] as $strength)
                                <li>{{ $strength }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>
        @endif

        @if ($omnes)
            @include('analyses.partials.omnes', ['report' => $omnes])
        @endif

        @if ($opt)
            <div class="grid gap-8 lg:grid-cols-2">
                <section class="grid gap-3">
                    <h2 class="text-lg font-semibold">Mise en page & structure</h2>
                    <ul class="grid gap-2">
                        @foreach ($opt['layout_recommendations'] as $recommendation)
                            <li class="rounded-lg border border-border bg-card p-3 text-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-medium">{{ $recommendation['title'] }}</span>
                                    <x-priority-badge :priority="$recommendation['priority']" />
                                </div>
                                <p class="mt-1 text-muted">{{ $recommendation['detail'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="grid gap-3">
                    <h2 class="text-lg font-semibold">Prix recommandés</h2>
                    @if (empty($opt['pricing_recommendations']))
                        <p class="text-sm text-muted">Aucun ajustement de prix recommandé.</p>
                    @else
                        <table class="w-full rounded-lg border border-border bg-card text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-muted">
                                <tr>
                                    <th class="px-3 py-2">Plat</th>
                                    <th class="px-3 py-2">Actuel</th>
                                    <th class="px-3 py-2">Proposé</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($opt['pricing_recommendations'] as $pricing)
                                    <tr class="border-t border-border align-top">
                                        <td class="px-3 py-2">
                                            <div class="font-medium">{{ $pricing['item'] }}</div>
                                            <div class="text-xs text-muted">{{ $pricing['rationale'] }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-2">{{ Price::euro($pricing['current_price']) }}</td>
                                        <td class="whitespace-nowrap px-3 py-2 font-medium text-accent">{{ Price::euro($pricing['suggested_price']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>
            </div>

            @if (! empty($opt['menu_engineering']))
                <section class="grid gap-3">
                    <h2 class="text-lg font-semibold">
                        Matrice d’ingénierie de menu <span class="text-sm font-normal text-muted">(estimée sans données de ventes)</span>
                    </h2>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach (MenuEngineeringClass::cases() as $class)
                            @php $entries = array_filter($opt['menu_engineering'], fn (array $entry) => $entry['classification'] === $class->value); @endphp
                            <div class="rounded-lg border border-border bg-card p-3 text-sm">
                                <h3 class="mb-2 font-semibold">{{ $class->label() }}</h3>
                                @if ($entries === [])
                                    <p class="text-xs text-muted">—</p>
                                @else
                                    <ul class="grid gap-2">
                                        @foreach ($entries as $entry)
                                            <li>
                                                <div class="font-medium">{{ $entry['item'] }}</div>
                                                <div class="text-xs text-muted">marge {{ $entry['estimated_margin'] }} · popularité {{ $entry['estimated_popularity'] }}</div>
                                                <div class="text-xs">{{ $entry['action'] }}</div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="grid gap-8 lg:grid-cols-2">
                <section class="grid gap-3">
                    <h2 class="text-lg font-semibold">Corrections</h2>
                    @if (empty($opt['corrections']))
                        <p class="text-sm text-muted">Aucune faute relevée.</p>
                    @else
                        <ul class="grid gap-2 text-sm">
                            @foreach ($opt['corrections'] as $correction)
                                <li class="rounded-lg border border-border bg-card p-3">
                                    <span class="text-muted line-through">{{ $correction['original'] }}</span> →
                                    <span class="font-medium">{{ $correction['corrected'] }}</span>
                                    <div class="text-xs text-muted">{{ $correction['reason'] }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
                <section class="grid gap-3">
                    <h2 class="text-lg font-semibold">Descriptions réécrites</h2>
                    <ul class="grid gap-2 text-sm">
                        @foreach ($opt['description_rewrites'] as $rewrite)
                            <li class="rounded-lg border border-border bg-card p-3">
                                <div class="text-xs uppercase tracking-wide text-muted">{{ $rewrite['section'] }} · {{ $rewrite['original_name'] }}</div>
                                <div class="font-medium">{{ $rewrite['new_name'] }}</div>
                                <div class="italic">{{ $rewrite['new_description'] }}</div>
                                <div class="mt-1 text-xs text-muted">{{ $rewrite['rationale'] }}</div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>

            @php $optimized = $opt['optimized_menu']; @endphp
            <section class="grid gap-3">
                <h2 class="text-lg font-semibold">Aperçu de la carte optimisée</h2>
                <div class="mx-auto w-full max-w-2xl rounded-xl border border-border bg-white p-8 shadow-sm">
                    <h3 class="text-center text-2xl font-semibold">{{ $optimized['title'] }}</h3>
                    @if ($optimized['tagline'])
                        <p class="text-center text-sm italic text-muted">{{ $optimized['tagline'] }}</p>
                    @endif
                    @foreach ($optimized['sections'] as $section)
                        <div class="mt-6">
                            <h4 class="text-center text-sm font-semibold uppercase tracking-widest text-accent">{{ $section['name'] }}</h4>
                            @if ($section['intro'])
                                <p class="text-center text-xs italic text-muted">{{ $section['intro'] }}</p>
                            @endif
                            <ul class="mt-3 grid gap-2.5">
                                @foreach ($section['items'] as $item)
                                    <li class="{{ $item['highlight'] ? 'border-l-2 border-accent pl-3' : 'pl-3.5' }}">
                                        <div class="flex items-baseline justify-between gap-4">
                                            <span class="font-medium">{{ $item['name'] }}</span>
                                            <span class="text-sm">{{ Price::menu($item['price']) }}</span>
                                        </div>
                                        @if ($item['description'])
                                            <div class="text-sm text-muted">{{ $item['description'] }}</div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                    @if ($optimized['footer_note'])
                        <p class="mt-8 border-t border-border pt-3 text-center text-xs text-muted">{{ $optimized['footer_note'] }}</p>
                    @endif
                </div>
            </section>
        @endif

        @if ($menu)
            @php $dishCount = array_sum(array_map(fn (array $section) => count($section['items']), $menu['sections'])); @endphp
            <details class="rounded-xl border border-border bg-card p-4">
                <summary class="cursor-pointer text-sm font-semibold">
                    Carte transcrite ({{ $dishCount }} plats)
                    @if ($menu['reading_notes'])
                        <span class="ml-2 font-normal text-muted">— {{ $menu['reading_notes'] }}</span>
                    @endif
                </summary>
                <div class="mt-4 grid gap-4 text-sm">
                    @foreach ($menu['sections'] as $section)
                        <div>
                            <h4 class="font-semibold">{{ $section['name'] }}</h4>
                            <ul class="mt-1 grid gap-1">
                                @foreach ($section['items'] as $item)
                                    <li class="flex items-baseline justify-between gap-3">
                                        <span>
                                            @if ($item['is_highlighted'])<span class="mr-1 text-accent">★</span>@endif
                                            {{ $item['name'] }}
                                            @if ($item['description'])<span class="text-muted"> — {{ $item['description'] }}</span>@endif
                                            <span class="ml-2 rounded bg-accent-soft px-1.5 text-[10px] uppercase text-accent">{{ DishCategory::labelFor($item['category']) }}</span>
                                        </span>
                                        <span class="whitespace-nowrap text-muted">{{ $item['price_text'] ?? Price::euro($item['price']) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif
    </div>
@endsection

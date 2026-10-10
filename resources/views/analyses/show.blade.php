@extends('layouts.app')

@section('title', $analysis->restaurant_name)

@section('head')
    @if ($analysis->isRunning())
        {{-- Filet de sécurité si le WebSocket (Reverb) n'est pas disponible : voir resources/js/app.js --}}
        <meta http-equiv="refresh" content="20">
    @endif
@endsection

@php
    use App\Enums\AnalysisStatus;
    use App\Enums\DishCategory;
    use App\Enums\MenuEngineeringClass;
    use App\Support\Price;

    $menu = $analysis->menu;
    $omnes = $analysis->omnes;
    $opt = $analysis->optimization;
    $corrected = collect($opt['corrections'] ?? [])->pluck('corrected', 'original');
    $dishCount = $menu ? array_sum(array_map(fn (array $section) => count($section['items']), $menu['sections'])) : 0;
    $score = $opt ? (int) round($opt['overall_score']) : null;
    $engineeringTone = ['etoile' => 'tertiary', 'vache_a_lait' => 'secondary', 'enigme' => 'primary-soft', 'poids_mort' => 'error'];
    $fieldClass = 'rounded-xl bg-surface-container-highest px-4 py-3';
@endphp

@section('content')
    <div class="grid gap-12" data-analysis-id="{{ $analysis->id }}" data-analysis-running="{{ $analysis->isRunning() ? '1' : '0' }}">
        {{-- En-tête éditorial --}}
        <section class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
            <div class="grid max-w-2xl gap-3">
                <span class="text-xs font-bold uppercase tracking-widest text-primary">Rapport d’intelligence</span>
                <h1 class="font-serif text-4xl font-bold leading-tight tracking-tight md:text-5xl">{{ $analysis->restaurant_name }}</h1>
                <p class="text-sm font-medium text-on-surface-variant">
                    {{ $analysis->city }} · {{ $analysis->restaurant_type }} · {{ $analysis->created_at->isoFormat('D MMMM YYYY, HH:mm') }}
                    @if ($analysis->mock) · démo @endif
                </p>
                @if ($analysis->goal)
                    <p class="text-sm"><span class="font-bold">Objectif :</span> {{ $analysis->goal }}</p>
                @endif
                @if ($analysis->fromWebsite())
                    <p class="text-sm"><span class="font-bold">Source :</span> <a href="{{ $analysis->website_url }}" target="_blank" rel="noreferrer" class="text-primary underline">{{ $analysis->website_url }}</a></p>
                @endif
                @if ($opt)
                    <p class="mt-2 leading-relaxed text-on-surface-variant">{{ $opt['summary'] }}</p>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-status-badge :status="$analysis->status" class="px-3 py-1.5 text-xs" />
                @if ($omnes)
                    <x-chip tone="tertiary" icon="trending-up" class="px-3 py-1.5 text-xs">Omnes {{ $omnes['score'] }} %</x-chip>
                @endif
                @if ($analysis->isDone())
                    <a href="{{ route('analyses.pdf', $analysis) }}" class="flex items-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-on-primary hover:opacity-90">
                        <x-icon name="download" class="size-4" />
                        Télécharger la carte optimisée (PDF)
                    </a>
                @endif
            </div>
        </section>

        @if ($analysis->status === AnalysisStatus::Error)
            <div class="flex items-start gap-4 rounded-3xl bg-error-container/15 p-6">
                <span class="rounded-xl bg-error-container p-2 text-on-error-container"><x-icon name="warning" /></span>
                <div class="grid gap-1 text-sm">
                    <p class="font-bold">L’analyse a échoué.</p>
                    <p class="text-on-surface-variant">{{ $analysis->error }}</p>
                    <a href="{{ route('home') }}" class="font-bold text-primary hover:underline">Relancer une analyse</a>
                </div>
            </div>
        @endif

        @if ($analysis->isRunning())
            <div class="relative overflow-hidden rounded-3xl bg-surface-container-lowest p-8">
                <div class="absolute -right-12 -top-12 size-40 rounded-full bg-primary-container/20 blur-3xl"></div>
                <div class="relative flex items-center gap-5">
                    <span class="flex size-14 items-center justify-center rounded-2xl bg-secondary-container text-on-secondary-container">
                        <x-icon name="sparkle" class="size-7 animate-pulse" />
                    </span>
                    <div class="grid gap-1">
                        <p class="font-serif text-xl font-bold">{{ $analysis->status->label() }}</p>
                        <p class="text-sm text-on-surface-variant">
                            @if ($analysis->status === AnalysisStatus::Optimizing)
                                Carte lue. Le consultant IA rédige le diagnostic et la carte optimisée (1 à 3 minutes). Cette page se met à jour automatiquement.
                            @elseif ($analysis->status === AnalysisStatus::Capturing)
                                Navigation sur le site du restaurant et capture de la carte. Cette page se met à jour automatiquement.
                            @else
                                Transcription des photos par le modèle de vision. Cette page se met à jour automatiquement.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if ($opt)
            {{-- Vue comparative : carte actuelle / carte recommandée --}}
            <div class="grid gap-6 lg:grid-cols-12">
                <div class="grid gap-6 lg:col-span-5">
                    <div class="relative overflow-hidden rounded-3xl bg-surface-container-low p-6">
                        <span class="absolute right-5 top-5 text-[10px] font-bold uppercase tracking-tighter text-outline">Source originale</span>
                        <h2 class="mb-6 font-serif text-xl italic opacity-70">Carte actuelle</h2>
                        <div class="grid gap-6 opacity-70">
                            @foreach ($menu['sections'] as $section)
                                <div class="grid gap-3">
                                    <h3 class="text-[11px] font-bold uppercase tracking-widest text-on-surface-variant">{{ $section['name'] }}</h3>
                                    @foreach ($section['items'] as $item)
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="font-bold {{ $corrected->has($item['name']) ? 'line-through decoration-error/50' : '' }}">
                                                    @if ($item['is_highlighted'])<x-icon name="star" class="mr-1 size-3.5 text-primary" />@endif{{ $item['name'] }}
                                                </p>
                                                @if ($item['description'])
                                                    <p class="text-sm">{{ $item['description'] }}</p>
                                                @endif
                                            </div>
                                            <span class="whitespace-nowrap font-bold">{{ $item['price_text'] ?? Price::euro($item['price']) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-start gap-4 rounded-3xl bg-error-container/10 p-6">
                        <span class="rounded-xl bg-error-container p-2 text-on-error-container"><x-icon name="spellcheck" /></span>
                        <div>
                            <h3 class="font-bold">Orthographe & clarté</h3>
                            <p class="text-sm text-on-surface-variant">
                                {{ count($opt['corrections']) }} correction(s) et {{ count($opt['description_rewrites']) }} description(s) réécrite(s) pour renforcer la confiance et l’appétit.
                            </p>
                        </div>
                    </div>

                    @if ($analysis->image_count > 0)
                        <div class="grid gap-3 rounded-3xl bg-surface-container-lowest p-6">
                            <h3 class="text-[11px] font-bold uppercase tracking-widest text-on-surface-variant">{{ $analysis->fromWebsite() ? 'Captures du site analysées' : 'Photos analysées' }}</h3>
                            <div class="flex flex-wrap gap-3">
                                @for ($i = 1; $i <= $analysis->image_count; $i++)
                                    <a href="{{ route('analyses.image', [$analysis, $i]) }}" target="_blank" rel="noreferrer">
                                        <img src="{{ route('analyses.image', [$analysis, $i]) }}" alt="Photo {{ $i }} de la carte" class="h-24 rounded-xl bg-surface-container object-cover ring-1 ring-outline-variant/40">
                                    </a>
                                @endfor
                            </div>
                        </div>
                    @endif
                </div>

                <div class="grid gap-6 lg:col-span-7">
                    @php $optimized = $opt['optimized_menu']; @endphp
                    <div class="relative overflow-hidden rounded-[2rem] border-l-4 border-primary bg-surface-container-lowest p-8 shadow-2xl shadow-primary/5">
                        <div class="mb-8 flex items-center justify-between gap-4">
                            <div>
                                <h2 class="font-serif text-2xl font-bold text-primary">{{ $optimized['title'] }}</h2>
                                @if ($optimized['tagline'])
                                    <p class="text-sm italic text-on-surface-variant">{{ $optimized['tagline'] }}</p>
                                @endif
                            </div>
                            <x-chip tone="primary" class="rounded-lg">Optimisée</x-chip>
                        </div>
                        <div class="grid gap-8">
                            @foreach ($optimized['sections'] as $section)
                                <div class="grid gap-4">
                                    <div class="text-center">
                                        <h3 class="text-xs font-bold uppercase tracking-widest text-primary">{{ $section['name'] }}</h3>
                                        @if ($section['intro'])
                                            <p class="text-xs italic text-on-surface-variant">{{ $section['intro'] }}</p>
                                        @endif
                                    </div>
                                    @foreach ($section['items'] as $item)
                                        <div class="{{ $item['highlight'] ? 'rounded-2xl bg-surface-container-low p-4' : '' }}">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h4 class="font-serif text-lg font-bold">{{ $item['name'] }}</h4>
                                                    @if ($item['highlight'])
                                                        <x-chip tone="tertiary" icon="star">Mis en avant</x-chip>
                                                    @endif
                                                </div>
                                                <span class="whitespace-nowrap font-serif text-lg font-bold text-primary">{{ Price::menu($item['price']) }}</span>
                                            </div>
                                            @if ($item['description'])
                                                <p class="max-w-md text-sm leading-relaxed text-on-surface-variant">{{ $item['description'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                            @if ($optimized['footer_note'])
                                <p class="text-center text-xs text-outline">{{ $optimized['footer_note'] }}</p>
                            @endif
                        </div>
                        <div class="absolute -bottom-12 -right-12 size-32 rounded-full bg-primary-container/20 blur-3xl"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <a href="{{ route('home') }}" class="grid gap-1 rounded-3xl bg-surface-container p-6 transition-colors hover:bg-surface-container-high">
                            <x-icon name="refresh" class="mb-2 text-primary" />
                            <span class="text-sm font-bold">Nouvelle analyse</span>
                            <span class="text-xs text-on-surface-variant">Comparez après avoir appliqué les recommandations.</span>
                        </a>
                        <a href="{{ route('analyses.pdf', $analysis) }}" class="flex flex-col justify-between rounded-3xl bg-on-surface p-6 text-surface-container-lowest transition-opacity hover:opacity-90">
                            <span class="text-sm font-bold">Télécharger le PDF</span>
                            <x-icon name="download" class="self-end" />
                        </a>
                    </div>
                </div>
            </div>

            {{-- Indicateurs --}}
            <section class="grid gap-6 md:grid-cols-3">
                <div class="flex flex-col items-center rounded-[2rem] bg-surface-container-low p-8 text-center">
                    <span class="mb-2 font-serif text-5xl font-bold text-primary">{{ $score }}<span class="text-2xl text-outline-variant">/100</span></span>
                    <span class="text-sm font-bold">Note de la carte actuelle</span>
                    <p class="mt-2 text-xs text-on-surface-variant">Lisibilité, descriptions, structure et cohérence des prix.</p>
                </div>
                <div class="flex flex-col items-center rounded-[2rem] border-t-4 border-tertiary-container bg-surface-container-low p-8 text-center">
                    <span class="mb-2 font-serif text-5xl font-bold text-tertiary">{{ $omnes['score'] }}<span class="text-2xl text-outline-variant">%</span></span>
                    <span class="text-sm font-bold">Conformité aux principes d’Omnes</span>
                    <p class="mt-2 text-xs text-on-surface-variant">Ouverture de gamme, dispersion des prix et promotions, par rubrique.</p>
                </div>
                <div class="flex flex-col items-center rounded-[2rem] bg-surface-container-low p-8 text-center">
                    <span class="mb-2 font-serif text-5xl font-bold">{{ $dishCount }}</span>
                    <span class="text-sm font-bold">Plats transcrits</span>
                    <p class="mt-2 text-xs text-on-surface-variant">{{ count($opt['pricing_recommendations']) }} prix ajustés, {{ count($opt['layout_recommendations']) }} recommandations de mise en page.</p>
                </div>
            </section>

            @if (! empty($opt['strengths']))
                <section class="grid gap-4 rounded-3xl bg-surface-container-lowest p-8">
                    <h2 class="font-serif text-2xl font-bold">Points forts à conserver</h2>
                    <ul class="grid gap-2 md:grid-cols-3">
                        @foreach ($opt['strengths'] as $strength)
                            <li class="flex items-start gap-3 rounded-2xl bg-surface-container-low p-4 text-sm"><x-icon name="check" class="mt-0.5 size-4 text-primary" />{{ $strength }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endif

        @if ($omnes)
            @include('analyses.partials.omnes', ['report' => $omnes])
        @endif

        @if ($opt)
            <div class="grid gap-8 lg:grid-cols-2">
                <section class="grid gap-4">
                    <h2 class="font-serif text-2xl font-bold">Mise en page & structure</h2>
                    <ul class="grid gap-3">
                        @foreach ($opt['layout_recommendations'] as $recommendation)
                            <li class="flex items-start gap-4 rounded-3xl bg-surface-container-lowest p-5 text-sm">
                                <span class="rounded-xl bg-primary/10 p-2 text-primary"><x-icon name="layout" /></span>
                                <div class="grid gap-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-bold">{{ $recommendation['title'] }}</span>
                                        <x-priority-badge :priority="$recommendation['priority']" />
                                    </div>
                                    <p class="text-on-surface-variant">{{ $recommendation['detail'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="grid gap-4">
                    <h2 class="font-serif text-2xl font-bold">Prix recommandés</h2>
                    @if (empty($opt['pricing_recommendations']))
                        <p class="rounded-3xl bg-surface-container-lowest p-5 text-sm text-on-surface-variant">Aucun ajustement de prix recommandé.</p>
                    @else
                        <ul class="grid gap-3">
                            @foreach ($opt['pricing_recommendations'] as $pricing)
                                <li class="grid gap-2 rounded-3xl bg-surface-container-lowest p-5 text-sm">
                                    <div class="flex items-baseline justify-between gap-4">
                                        <span class="font-serif text-lg font-bold">{{ $pricing['item'] }}</span>
                                        <span class="whitespace-nowrap">
                                            <span class="text-on-surface-variant line-through">{{ Price::euro($pricing['current_price']) }}</span>
                                            <span class="ml-2 font-serif text-lg font-bold text-primary">{{ Price::euro($pricing['suggested_price']) }}</span>
                                        </span>
                                    </div>
                                    <p class="text-on-surface-variant">{{ $pricing['rationale'] }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>

            @if (! empty($opt['menu_engineering']))
                <section class="grid gap-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 class="font-serif text-2xl font-bold">Matrice d’ingénierie de menu</h2>
                        <span class="text-sm text-on-surface-variant">estimée sans données de ventes</span>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach (MenuEngineeringClass::cases() as $class)
                            @php $entries = array_filter($opt['menu_engineering'], fn (array $entry) => $entry['classification'] === $class->value); @endphp
                            <div class="grid content-start gap-3 rounded-3xl bg-surface-container-low p-5 text-sm">
                                <x-chip :tone="$engineeringTone[$class->value]" class="justify-self-start">{{ $class->label() }}</x-chip>
                                @if ($entries === [])
                                    <p class="text-xs text-on-surface-variant">Aucun plat dans cette catégorie.</p>
                                @else
                                    @foreach ($entries as $entry)
                                        <div class="grid gap-1 rounded-2xl bg-surface-container-lowest p-4">
                                            <span class="font-serif font-bold">{{ $entry['item'] }}</span>
                                            <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">marge {{ $entry['estimated_margin'] }} · popularité {{ $entry['estimated_popularity'] }}</span>
                                            <span class="text-xs">{{ $entry['action'] }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="grid gap-8 lg:grid-cols-2">
                <section class="grid gap-4">
                    <h2 class="font-serif text-2xl font-bold">Corrections</h2>
                    @if (empty($opt['corrections']))
                        <p class="rounded-3xl bg-surface-container-lowest p-5 text-sm text-on-surface-variant">Aucune faute relevée.</p>
                    @else
                        <ul class="grid gap-3 text-sm">
                            @foreach ($opt['corrections'] as $correction)
                                <li class="rounded-3xl bg-surface-container-lowest p-5">
                                    <span class="text-on-surface-variant line-through decoration-error/50">{{ $correction['original'] }}</span>
                                    <x-icon name="arrow-right" class="mx-1 size-4 text-primary" />
                                    <span class="font-bold">{{ $correction['corrected'] }}</span>
                                    <p class="mt-1 text-xs text-on-surface-variant">{{ $correction['reason'] }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
                <section class="grid gap-4">
                    <h2 class="font-serif text-2xl font-bold">Descriptions réécrites</h2>
                    <ul class="grid gap-3 text-sm">
                        @foreach ($opt['description_rewrites'] as $rewrite)
                            <li class="grid gap-1 rounded-3xl bg-surface-container-lowest p-5">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ $rewrite['section'] }} · {{ $rewrite['original_name'] }}</span>
                                <span class="font-serif text-lg font-bold">{{ $rewrite['new_name'] }}</span>
                                <span class="italic text-on-surface-variant">{{ $rewrite['new_description'] }}</span>
                                <span class="mt-1 flex items-center gap-1 text-xs font-bold text-primary-dim"><x-icon name="sparkle" class="size-3.5" />{{ $rewrite['rationale'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>
        @endif

        @if ($menu && ! $opt)
            <details class="rounded-3xl bg-surface-container-lowest p-6">
                <summary class="cursor-pointer font-serif text-lg font-bold">
                    Carte transcrite ({{ $dishCount }} plats)
                    @if ($menu['reading_notes'])
                        <span class="ml-2 text-sm font-normal text-on-surface-variant">— {{ $menu['reading_notes'] }}</span>
                    @endif
                </summary>
                <div class="mt-4 grid gap-4 text-sm">
                    @foreach ($menu['sections'] as $section)
                        <div>
                            <h4 class="font-bold">{{ $section['name'] }}</h4>
                            <ul class="mt-1 grid gap-1">
                                @foreach ($section['items'] as $item)
                                    <li class="flex items-baseline justify-between gap-3">
                                        <span>{{ $item['name'] }} <x-chip class="ml-1">{{ DishCategory::labelFor($item['category']) }}</x-chip></span>
                                        <span class="whitespace-nowrap text-on-surface-variant">{{ $item['price_text'] ?? Price::euro($item['price']) }}</span>
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

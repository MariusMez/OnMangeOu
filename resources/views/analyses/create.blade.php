@extends('layouts.app')

@section('title', 'Scanner la carte')

@section('content')
    <div class="grid gap-10 lg:grid-cols-12 lg:items-start">
        <div class="grid gap-8 lg:col-span-8">
            <section class="grid gap-3 text-center md:text-left">
                <span class="text-xs font-bold uppercase tracking-widest text-primary">Nouvelle analyse</span>
                <h1 class="font-serif text-4xl font-bold tracking-tight text-primary md:text-5xl">Scanner la carte</h1>
                <p class="max-w-2xl font-medium text-on-surface-variant">
                    Photographiez votre carte ou indiquez le site de votre restaurant. OptiCarte la transcrit, applique les principes
                    d’Omnes et l’ingénierie de menu, puis génère une carte optimisée prête à imprimer.
                </p>
            </section>

            <form method="POST" action="{{ route('analyses.store') }}" enctype="multipart/form-data" class="grid gap-6">
                @csrf

                {{-- Viseur : zone de dépôt des photos --}}
                <label class="group relative block aspect-[4/3] cursor-pointer overflow-hidden rounded-3xl bg-surface-container-highest md:aspect-[16/9]">
                    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple data-file-input
                           class="absolute inset-0 z-10 size-full cursor-pointer opacity-0">
                    <div class="absolute inset-0 bg-gradient-to-br from-primary-container/15 via-transparent to-primary/10"></div>
                    <div class="absolute left-8 top-8 size-10 rounded-tl-xl border-l-4 border-t-4 border-primary"></div>
                    <div class="absolute right-8 top-8 size-10 rounded-tr-xl border-r-4 border-t-4 border-primary"></div>
                    <div class="absolute bottom-8 left-8 size-10 rounded-bl-xl border-b-4 border-l-4 border-primary"></div>
                    <div class="absolute bottom-8 right-8 size-10 rounded-br-xl border-b-4 border-r-4 border-primary"></div>
                    <div class="scan-line absolute inset-x-20 top-1/2 h-px bg-primary/50 shadow-[0_0_16px_rgba(154,64,0,0.8)]"></div>
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-4 text-center">
                        <span class="flex size-20 items-center justify-center rounded-full border-4 border-surface-container-lowest bg-primary text-on-primary shadow-2xl transition-transform group-hover:scale-105">
                            <x-icon name="camera" class="size-9" />
                        </span>
                        <span class="px-6 text-sm font-bold text-on-surface">Déposez 1 à {{ config('opticarte.max_images') }} photos de la carte, ou cliquez</span>
                        <span class="text-xs font-medium uppercase tracking-wider text-on-surface-variant">JPG · PNG · WEBP</span>
                        <span data-file-list hidden class="max-w-md px-6 text-xs font-semibold text-primary"></span>
                    </div>
                </label>

                <div class="grid gap-4 md:grid-cols-2">
                    <button type="button" data-pick-photos class="flex items-center justify-center gap-3 rounded-2xl bg-secondary-container px-6 py-5 font-bold text-on-secondary-container transition-opacity hover:opacity-90">
                        <x-icon name="upload" />
                        Importer des photos
                    </button>
                    <button type="button" data-focus-website class="flex items-center justify-center gap-3 rounded-2xl bg-surface-container-low px-6 py-5 font-bold text-on-surface transition-colors hover:bg-surface-container">
                        <x-icon name="link" />
                        Depuis le site web
                    </button>
                </div>

                <div class="grid gap-2 rounded-3xl bg-surface-container-low p-6">
                    <label for="website_url" class="text-sm font-bold">Site web du restaurant <span class="font-medium text-on-surface-variant">(à la place des photos)</span></label>
                    <input id="website_url" type="url" name="website_url" value="{{ old('website_url') }}" maxlength="500" placeholder="https://www.chez-lucien.fr" data-website-input
                           class="w-full rounded-xl border border-outline-variant/15 bg-surface-container-lowest px-4 py-3 font-medium focus:border-primary focus:outline-none focus:ring-0">
                    <p class="text-xs text-on-surface-variant">OptiCarte navigue sur le site, repère la page « carte » et la capture pour l’analyse.</p>
                </div>

                <div class="grid gap-5 rounded-3xl bg-surface-container-lowest p-6 md:p-8">
                    <h2 class="font-serif text-xl font-bold">Votre établissement</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1.5 text-sm">
                            <span class="font-bold">Nom du restaurant</span>
                            <input name="restaurant_name" value="{{ old('restaurant_name') }}" required maxlength="120" placeholder="Chez Lucien"
                                   class="rounded-xl border border-outline-variant/15 bg-surface-container-highest px-4 py-3 font-medium focus:border-primary focus:outline-none focus:ring-0">
                        </label>
                        <label class="grid gap-1.5 text-sm">
                            <span class="font-bold">Ville</span>
                            <input name="city" value="{{ old('city') }}" required maxlength="120" placeholder="Lyon"
                                   class="rounded-xl border border-outline-variant/15 bg-surface-container-highest px-4 py-3 font-medium focus:border-primary focus:outline-none focus:ring-0">
                        </label>
                        <label class="grid gap-1.5 text-sm">
                            <span class="font-bold">Type de restaurant</span>
                            <select name="restaurant_type" required class="rounded-xl border border-outline-variant/15 bg-surface-container-highest px-4 py-3 font-medium focus:border-primary focus:outline-none focus:ring-0">
                                @foreach ($restaurantTypes as $type)
                                    <option value="{{ $type }}" @selected(old('restaurant_type') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-1.5 text-sm">
                            <span class="font-bold">Objectif <span class="font-medium text-on-surface-variant">(facultatif)</span></span>
                            <input name="goal" value="{{ old('goal') }}" maxlength="500" placeholder="Augmenter le ticket moyen le midi"
                                   class="rounded-xl border border-outline-variant/15 bg-surface-container-highest px-4 py-3 font-medium focus:border-primary focus:outline-none focus:ring-0">
                        </label>
                    </div>

                    @if ($errors->any())
                        <ul class="grid gap-1 rounded-2xl bg-error-container/15 px-4 py-3 text-sm font-medium text-error">
                            @foreach ($errors->all() as $error)
                                <li class="flex items-start gap-2"><x-icon name="warning" class="mt-0.5 size-4" />{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <button type="submit" class="flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-4 font-bold text-on-primary transition-opacity hover:opacity-90">
                        <x-icon name="sparkle" />
                        Analyser ma carte
                    </button>
                </div>
            </form>
        </div>

        <aside class="grid gap-6 lg:col-span-4">
            <div class="flex items-baseline justify-between px-2">
                <h2 class="font-serif text-xl font-bold">Analyses récentes</h2>
                <a href="{{ route('analyses.index') }}" class="text-sm font-bold text-primary hover:underline">Tout voir</a>
            </div>

            @forelse ($recentAnalyses as $analysis)
                <a href="{{ route('analyses.show', $analysis) }}" class="group flex items-center gap-4 rounded-2xl bg-surface-container-lowest p-4 transition-colors hover:bg-surface-container-low">
                    <span class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-surface-container text-outline ring-1 ring-outline-variant/40">
                        @if ($analysis->image_count > 0)
                            <img src="{{ route('analyses.image', [$analysis, 1]) }}" alt="" class="size-full object-cover">
                        @else
                            <x-icon name="globe" />
                        @endif
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-bold">{{ $analysis->restaurant_name }}</span>
                        <span class="block text-xs font-medium uppercase tracking-wider text-on-surface-variant">
                            {{ $analysis->dishes_count > 0 ? $analysis->dishes_count.' plats détectés' : $analysis->status->label() }}
                        </span>
                        <span class="mt-1 block text-[10px] italic text-outline">{{ $analysis->created_at->isoFormat('D MMM, HH:mm') }}</span>
                    </span>
                    <x-icon name="chevron-right" class="text-outline transition-colors group-hover:text-primary" />
                </a>
            @empty
                <p class="rounded-2xl bg-surface-container-lowest p-4 text-sm text-on-surface-variant">Aucune analyse pour l’instant : votre première carte apparaîtra ici.</p>
            @endforelse

            <div class="grid gap-2 rounded-3xl border-2 border-dashed border-primary/20 bg-primary/5 p-8 text-center">
                <x-icon name="sparkle" class="mx-auto size-9 text-primary" />
                <p class="text-sm font-bold text-primary">Conseil</p>
                <p class="text-xs leading-relaxed text-on-surface-variant">
                    Photographiez la carte à plat, en lumière naturelle et sans reflet : la lecture des prix gagne nettement en fiabilité.
                </p>
            </div>
        </aside>
    </div>
@endsection

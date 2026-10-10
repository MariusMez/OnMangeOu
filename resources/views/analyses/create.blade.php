@extends('layouts.app')

@section('title', 'Analyser une carte')

@section('content')
    <div class="grid gap-8">
        <section class="grid gap-3">
            <h1 class="text-3xl font-semibold tracking-tight">Votre carte vend-elle vraiment vos meilleurs plats ?</h1>
            <p class="max-w-2xl text-muted">
                Photographiez votre carte ou indiquez le site de votre restaurant. OptiCarte la transcrit, applique les principes
                d’Omnes et l’ingénierie de menu, corrige les fautes, réécrit les descriptions et génère une carte optimisée prête à imprimer.
            </p>
        </section>

        <form method="POST" action="{{ route('analyses.store') }}" enctype="multipart/form-data"
              class="grid gap-5 rounded-xl border border-border bg-card p-6 shadow-sm">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Nom du restaurant</span>
                    <input name="restaurant_name" value="{{ old('restaurant_name') }}" required maxlength="120"
                           class="rounded-md border border-border px-3 py-2" placeholder="Chez Lucien">
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Ville</span>
                    <input name="city" value="{{ old('city') }}" required maxlength="120"
                           class="rounded-md border border-border px-3 py-2" placeholder="Lyon">
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Type de restaurant</span>
                    <select name="restaurant_type" required class="rounded-md border border-border bg-white px-3 py-2">
                        @foreach ($restaurantTypes as $type)
                            <option value="{{ $type }}" @selected(old('restaurant_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Objectif (facultatif)</span>
                    <input name="goal" value="{{ old('goal') }}" maxlength="500"
                           class="rounded-md border border-border px-3 py-2" placeholder="Augmenter le ticket moyen le midi">
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-[1fr_auto_1fr] md:items-center">
                <label class="grid gap-2 text-sm">
                    <span class="font-medium">Photos de la carte (1 à {{ config('opticarte.max_images') }}, JPG / PNG / WEBP)</span>
                    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple
                           class="rounded-md border border-dashed border-border bg-background px-3 py-6 file:mr-4 file:rounded-md file:border-0 file:bg-accent file:px-3 file:py-1.5 file:text-white">
                </label>
                <div class="text-center text-xs font-semibold uppercase tracking-wide text-muted">ou</div>
                <label class="grid gap-2 text-sm">
                    <span class="font-medium">Site web du restaurant</span>
                    <input type="url" name="website_url" value="{{ old('website_url') }}" maxlength="500" placeholder="https://www.chez-lucien.fr"
                           class="rounded-md border border-border px-3 py-2">
                    <span class="text-xs text-muted">OptiCarte navigue sur le site, repère la page « carte » et la capture pour l’analyse.</span>
                </label>
            </div>

            @if ($errors->any())
                <ul class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <button type="submit" class="rounded-md bg-accent px-4 py-2.5 font-medium text-white hover:opacity-90">
                Analyser ma carte
            </button>
        </form>

        <section class="grid gap-3 text-sm text-muted sm:grid-cols-3">
            @foreach ([
                ['Lecture', 'Transcription fidèle des plats, prix et rubriques par le modèle de vision.'],
                ['Diagnostic', 'Ouverture de gamme, dispersion des prix, promotion, matrice étoiles / vaches à lait / énigmes / poids morts.'],
                ['Carte optimisée', 'Nouvelles descriptions, ordre et prix recommandés, export PDF.'],
            ] as $index => [$title, $text])
                <div class="rounded-lg border border-border bg-card p-4">
                    <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-accent">Étape {{ $index + 1 }}</div>
                    <div class="font-medium text-foreground">{{ $title }}</div>
                    <p class="mt-1">{{ $text }}</p>
                </div>
            @endforeach
        </section>
    </div>
@endsection

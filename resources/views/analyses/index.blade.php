@extends('layouts.app')

@section('title', 'Analyses')

@section('content')
    <div class="grid gap-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="grid gap-2">
                <span class="text-xs font-bold uppercase tracking-widest text-primary">Historique</span>
                <h1 class="font-serif text-4xl font-bold tracking-tight">Vos analyses</h1>
            </div>
            <a href="{{ route('home') }}" class="flex items-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-on-primary hover:opacity-90">
                <x-icon name="scan" class="size-4" />
                Nouvelle analyse
            </a>
        </header>

        @if ($analyses->isEmpty())
            <p class="rounded-3xl bg-surface-container-lowest p-8 text-on-surface-variant">
                Aucune analyse pour l’instant. <a href="{{ route('home') }}" class="font-bold text-primary hover:underline">Scanner une carte</a>.
            </p>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($analyses as $analysis)
                    <a href="{{ route('analyses.show', $analysis) }}" class="group flex items-center gap-4 rounded-2xl bg-surface-container-lowest p-4 transition-colors hover:bg-surface-container-low">
                        <span class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-surface-container text-outline ring-1 ring-outline-variant/40">
                            @if ($analysis->image_count > 0)
                                <img src="{{ route('analyses.image', [$analysis, 1]) }}" alt="" class="size-full object-cover">
                            @else
                                <x-icon name="globe" />
                            @endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span class="truncate font-bold">{{ $analysis->restaurant_name }}</span>
                                <x-status-badge :status="$analysis->status" />
                            </span>
                            <span class="block text-xs font-medium uppercase tracking-wider text-on-surface-variant">
                                {{ $analysis->city }} · {{ $analysis->restaurant_type }} · {{ $analysis->fromWebsite() ? 'Site web' : $analysis->image_count.' photo'.($analysis->image_count > 1 ? 's' : '') }}
                            </span>
                            <span class="mt-1 flex items-center gap-3 text-[11px] text-outline">
                                <span class="italic">{{ $analysis->created_at->isoFormat('D MMM YYYY, HH:mm') }}</span>
                                @if ($analysis->omnes)
                                    <span class="font-bold not-italic text-primary">Omnes {{ $analysis->omnes['score'] }} / 100</span>
                                @endif
                                @if ($analysis->dishes_count > 0)
                                    <span>{{ $analysis->dishes_count }} plats</span>
                                @endif
                            </span>
                        </span>
                        <x-icon name="chevron-right" class="text-outline transition-colors group-hover:text-primary" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection

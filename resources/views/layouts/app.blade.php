<!DOCTYPE html>
<html lang="fr" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('head')
</head>
@php
    $links = [
        ['route' => 'home', 'icon' => 'scan', 'label' => 'Scanner', 'active' => request()->routeIs('home')],
        ['route' => 'analyses.index', 'icon' => 'analytics', 'label' => 'Analyses', 'active' => request()->routeIs('analyses.*')],
        ['route' => 'stats.index', 'icon' => 'chart', 'label' => 'Observatoire', 'active' => request()->routeIs('stats.*')],
    ];
@endphp
<body class="min-h-full flex flex-col bg-background font-sans text-on-surface pb-28 md:pb-0">
    <header class="sticky top-0 z-40 bg-background/80 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-full bg-secondary-container text-on-secondary-container">
                    <x-icon name="utensils" />
                </span>
                <span class="font-serif text-xl font-bold text-primary">OptiCarte</span>
            </a>
            <div class="flex items-center gap-6">
                <nav class="hidden items-center gap-8 md:flex">
                    @foreach ($links as $link)
                        <x-nav-link :href="route($link['route'])" :icon="$link['icon']" :active="$link['active']">{{ $link['label'] }}</x-nav-link>
                    @endforeach
                </nav>
                @if (config('opticarte.mock'))
                    <x-chip tone="tertiary" icon="sparkle" title="Les analyses utilisent une carte fictive, aucune requête à l’API Claude.">Mode démo</x-chip>
                @endif
                @if (app()->environment('local'))
                    <a href="/horizon" title="Supervision des analyses (Horizon)" class="flex size-10 items-center justify-center rounded-full text-on-surface transition-colors hover:bg-surface-container">
                        <x-icon name="settings" />
                    </a>
                @endif
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl flex-1 px-6 py-8 md:py-12">
        @yield('content')
    </main>

    <footer class="hidden py-8 text-center text-xs text-outline md:block">
        OptiCarte — analyse propulsée par Claude. Les estimations de marge et de popularité sont indicatives.
    </footer>

    <nav class="fixed bottom-0 left-0 z-40 flex w-full items-center justify-around rounded-t-3xl bg-surface-container-lowest/80 px-4 pb-6 pt-3 shadow-[0_-12px_32px_-16px_rgba(47,47,46,0.25)] backdrop-blur-xl md:hidden">
        @foreach ($links as $link)
            <x-nav-link :href="route($link['route'])" :icon="$link['icon']" :active="$link['active']" mobile>{{ $link['label'] }}</x-nav-link>
        @endforeach
    </nav>
</body>
</html>

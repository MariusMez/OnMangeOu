<!DOCTYPE html>
<html lang="fr" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('head')
</head>
<body class="min-h-full flex flex-col font-sans">
    <header class="border-b border-border bg-card">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight">
                <span class="text-accent">Opti</span>Carte
            </a>
            <nav class="flex gap-5 text-sm">
                <a href="{{ route('home') }}" class="hover:text-accent">Analyser</a>
                <a href="{{ route('analyses.index') }}" class="hover:text-accent">Historique</a>
                <a href="{{ route('stats.index') }}" class="hover:text-accent">Observatoire des prix</a>
            </nav>
        </div>
        @if (config('opticarte.mock'))
            <div class="bg-amber-100 py-1 text-center text-xs text-amber-900">
                Mode démo : les analyses utilisent une carte fictive, aucune requête à l’API Claude.
            </div>
        @endif
    </header>

    <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-8">
        @yield('content')
    </main>

    <footer class="border-t border-border py-4 text-center text-xs text-muted">
        OptiCarte MVP — analyse propulsée par Claude. Les estimations de marge et de popularité sont indicatives.
    </footer>
</body>
</html>

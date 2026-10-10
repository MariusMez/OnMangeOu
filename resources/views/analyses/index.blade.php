@extends('layouts.app')

@section('title', 'Historique des analyses')

@section('content')
    <div class="grid gap-6">
        <h1 class="text-2xl font-semibold tracking-tight">Historique des analyses</h1>

        @if ($analyses->isEmpty())
            <p class="text-muted">
                Aucune analyse pour l’instant. <a href="{{ route('home') }}" class="text-accent underline">Analyser une carte</a>.
            </p>
        @else
            <div class="overflow-x-auto rounded-xl border border-border bg-card">
                <table class="w-full text-sm">
                    <thead class="bg-background text-left text-xs uppercase tracking-wide text-muted">
                        <tr>
                            <th class="px-4 py-2">Restaurant</th>
                            <th class="px-4 py-2">Ville</th>
                            <th class="px-4 py-2">Type</th>
                            <th class="px-4 py-2">Source</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Score Omnes</th>
                            <th class="px-4 py-2">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($analyses as $analysis)
                            <tr class="border-t border-border hover:bg-background">
                                <td class="px-4 py-2 font-medium">
                                    <a href="{{ route('analyses.show', $analysis) }}" class="hover:text-accent">{{ $analysis->restaurant_name }}</a>
                                </td>
                                <td class="px-4 py-2">{{ $analysis->city }}</td>
                                <td class="px-4 py-2">{{ $analysis->restaurant_type }}</td>
                                <td class="px-4 py-2 text-muted">{{ $analysis->fromWebsite() ? 'Site web' : $analysis->image_count.' photo(s)' }}</td>
                                <td class="px-4 py-2 text-muted">{{ $analysis->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2">{{ $analysis->omnes ? $analysis->omnes['score'].' / 100' : '—' }}</td>
                                <td class="px-4 py-2"><x-status-badge :status="$analysis->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

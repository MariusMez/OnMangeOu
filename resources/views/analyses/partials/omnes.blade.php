@php
    use App\Support\Price;

    $statusStyle = ['ok' => 'bg-emerald-100 text-emerald-800', 'warning' => 'bg-red-100 text-red-800', 'info' => 'bg-zinc-100 text-zinc-700'];
    $statusLabel = ['ok' => 'OK', 'warning' => 'À corriger', 'info' => 'Info'];
@endphp
<section class="grid gap-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold">
            Principes d’Omnes <span class="text-sm font-normal text-muted">(calculés sur les prix lus)</span>
        </h2>
        <div class="text-sm">Conformité : <span class="font-semibold">{{ $report['score'] }} %</span></div>
    </div>

    @if (! empty($report['notes']))
        <ul class="list-disc pl-5 text-sm text-muted">
            @foreach ($report['notes'] as $note)
                <li>{{ $note }}</li>
            @endforeach
        </ul>
    @endif

    @if (empty($report['categories']))
        <p class="text-sm text-muted">Pas assez de plats avec prix lisible pour appliquer les principes.</p>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($report['categories'] as $category)
            <div class="rounded-xl border border-border bg-card p-4 text-sm">
                <div class="flex items-baseline justify-between">
                    <h3 class="font-semibold">{{ $category['label'] }}</h3>
                    <span class="text-xs text-muted">
                        {{ $category['priced_count'] }} plats · {{ Price::euro($category['min']) }} → {{ Price::euro($category['max']) }} · moyenne {{ Price::euro($category['mean']) }}
                    </span>
                </div>
                <div class="mt-3 grid grid-cols-3 gap-1 text-center text-xs">
                    @foreach (['low', 'mid', 'high'] as $zone)
                        <div class="rounded px-1 py-1.5 {{ $zone === 'mid' ? 'bg-accent-soft' : 'bg-background' }}">
                            <div class="font-semibold">{{ $category['zones'][$zone]['count'] }}</div>
                            <div class="text-muted">{{ Price::euro($category['zones'][$zone]['from']) }} – {{ Price::euro($category['zones'][$zone]['to']) }}</div>
                        </div>
                    @endforeach
                </div>
                <ul class="mt-3 grid gap-2">
                    @foreach ($category['principles'] as $principle)
                        <li class="grid gap-0.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-medium">{{ $principle['title'] }}</span>
                                <span class="rounded-full px-2 py-0.5 text-xs {{ $statusStyle[$principle['status']] }}">{{ $statusLabel[$principle['status']] }}</span>
                            </div>
                            <div class="text-xs text-muted">{{ $principle['value'] }}</div>
                            <div class="text-xs">{{ $principle['detail'] }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    @if (! empty($report['skipped']))
        <p class="text-xs text-muted">
            Non analysé : {{ implode(' · ', array_map(fn (array $skipped) => $skipped['label'].' ('.$skipped['reason'].')', $report['skipped'])) }}
        </p>
    @endif
</section>

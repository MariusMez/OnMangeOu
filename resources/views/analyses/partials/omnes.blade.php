@php
    use App\Support\Price;

    $statusTone = ['ok' => 'tertiary', 'warning' => 'error', 'info' => 'neutral'];
    $statusLabel = ['ok' => 'OK', 'warning' => 'À corriger', 'info' => 'Info'];
@endphp
<section class="grid gap-6">
    <div class="flex flex-wrap items-baseline justify-between gap-3">
        <div>
            <h2 class="font-serif text-2xl font-bold">Principes d’Omnes</h2>
            <p class="text-sm text-on-surface-variant">Calculés sur les prix lus, rubrique par rubrique.</p>
        </div>
        <x-chip tone="tertiary" icon="trending-up" class="px-3 py-1.5 text-xs">Conformité {{ $report['score'] }} %</x-chip>
    </div>

    @if (! empty($report['notes']))
        <ul class="grid gap-2">
            @foreach ($report['notes'] as $note)
                <li class="flex items-start gap-3 rounded-2xl bg-secondary-container/40 p-4 text-sm"><x-icon name="info" class="mt-0.5 size-4 text-secondary" />{{ $note }}</li>
            @endforeach
        </ul>
    @endif

    @if (empty($report['categories']))
        <p class="rounded-3xl bg-surface-container-lowest p-6 text-sm text-on-surface-variant">Pas assez de plats avec prix lisible pour appliquer les principes.</p>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ($report['categories'] as $category)
            <div class="grid gap-5 rounded-3xl bg-surface-container-low p-6 text-sm">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 class="font-serif text-xl font-bold">{{ $category['label'] }}</h3>
                    <span class="text-xs font-medium text-on-surface-variant">
                        {{ $category['priced_count'] }} plats · {{ Price::euro($category['min']) }} → {{ Price::euro($category['max']) }} · moyenne {{ Price::euro($category['mean']) }}
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                    @foreach (['low' => 'Zone basse', 'mid' => 'Zone médiane', 'high' => 'Zone haute'] as $zone => $label)
                        <div class="grid gap-0.5 rounded-2xl px-2 py-3 {{ $zone === 'mid' ? 'bg-secondary-container/60' : 'bg-surface-container-lowest' }}">
                            <span class="font-serif text-2xl font-bold">{{ $category['zones'][$zone]['count'] }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">{{ $label }}</span>
                            <span class="text-on-surface-variant">{{ Price::euro($category['zones'][$zone]['from']) }} – {{ Price::euro($category['zones'][$zone]['to']) }}</span>
                        </div>
                    @endforeach
                </div>
                <ul class="grid gap-3">
                    @foreach ($category['principles'] as $principle)
                        <li class="grid gap-1 rounded-2xl bg-surface-container-lowest p-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold">{{ $principle['title'] }}</span>
                                <x-chip :tone="$statusTone[$principle['status']]">{{ $statusLabel[$principle['status']] }}</x-chip>
                            </div>
                            <span class="text-xs font-semibold text-primary-dim">{{ $principle['value'] }}</span>
                            <span class="text-xs text-on-surface-variant">{{ $principle['detail'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    @if (! empty($report['skipped']))
        <p class="text-xs text-on-surface-variant">
            Non analysé : {{ implode(' · ', array_map(fn (array $skipped) => $skipped['label'].' ('.$skipped['reason'].')', $report['skipped'])) }}
        </p>
    @endif
</section>

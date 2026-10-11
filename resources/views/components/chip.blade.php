@props(['tone' => 'neutral', 'icon' => null])
@php
    $classes = match ($tone) {
        'primary' => 'bg-primary text-on-primary',
        'primary-soft' => 'bg-primary/10 text-primary',
        'secondary' => 'bg-secondary-container text-on-secondary-container',
        'tertiary' => 'bg-tertiary-container text-on-tertiary-container',
        'error' => 'bg-error-container text-on-error-container',
        'error-soft' => 'bg-error-container/15 text-error',
        'dark' => 'bg-on-surface text-surface-container-lowest',
        default => 'bg-surface-container-high text-on-surface-variant',
    };
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide '.$classes]) }}>
    @if ($icon)
        <x-icon :name="$icon" class="size-3.5" />
    @endif
    {{ $slot }}
</span>

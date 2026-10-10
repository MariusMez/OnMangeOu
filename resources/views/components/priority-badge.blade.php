@props(['priority'])
@php
    $class = match ($priority) {
        'haute' => 'bg-red-100 text-red-800',
        'moyenne' => 'bg-amber-100 text-amber-800',
        default => 'bg-zinc-100 text-zinc-700',
    };
@endphp
<span {{ $attributes->merge(['class' => 'rounded-full px-2 py-0.5 text-xs '.$class]) }}>{{ $priority }}</span>

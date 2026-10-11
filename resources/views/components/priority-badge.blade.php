@props(['priority'])
@php
    $tone = match ($priority) {
        'haute' => 'error',
        'moyenne' => 'tertiary',
        default => 'neutral',
    };
@endphp
<x-chip :tone="$tone" {{ $attributes }}>{{ $priority }}</x-chip>

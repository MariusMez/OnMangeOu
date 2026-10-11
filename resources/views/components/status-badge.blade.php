@props(['status'])
<x-chip :tone="$status->chipTone()" :icon="$status->isRunning() ? 'refresh' : null" {{ $attributes }}>{{ $status->label() }}</x-chip>

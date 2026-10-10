@props(['status'])
<span {{ $attributes->merge(['class' => 'rounded-full px-2 py-0.5 text-xs font-medium '.$status->badgeClass()]) }}>{{ $status->label() }}</span>

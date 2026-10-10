@props(['href', 'icon', 'active' => false, 'mobile' => false])
@if ($mobile)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-0.5 rounded-2xl px-4 py-1 transition-opacity hover:opacity-80 '.($active ? 'bg-primary-container/10 text-primary' : 'text-outline-variant')]) }}>
        <x-icon :name="$icon" class="size-6" />
        <span class="text-[11px] font-semibold tracking-tight">{{ $slot }}</span>
    </a>
@else
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'font-medium transition-colors '.($active ? 'border-b-2 border-primary pb-1 font-bold text-primary' : 'text-outline hover:text-primary')]) }}>{{ $slot }}</a>
@endif

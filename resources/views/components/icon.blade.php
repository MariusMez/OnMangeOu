@props(['name'])
<svg {{ $attributes->merge(['class' => 'inline-block size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
    @case('scan')
        <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10"/>
        @break
    @case('analytics')
        <path d="M3 3v18h18M8 17v-6M13 17V7M18 17v-3"/>
        @break
    @case('history')
        <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
        @break
    @case('chart')
        <path d="M3 3v18h18M7 15l4-5 4 3 5-7"/>
        @break
    @case('trending-up')
        <path d="M3 17l6-6 4 4 8-8M14 7h7v7"/>
        @break
    @case('settings')
        <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>
        @break
    @case('upload')
        <path d="M12 16V4m0 0-4 4m4-4 4 4M4 20h16"/>
        @break
    @case('download')
        <path d="M12 4v12m0 0-4-4m4 4 4-4M4 20h16"/>
        @break
    @case('link')
        <path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/>
        @break
    @case('chevron-right')
        <path d="m9 6 6 6-6 6"/>
        @break
    @case('arrow-right')
        <path d="M5 12h14m-6-6 6 6-6 6"/>
        @break
    @case('spellcheck')
        <path d="m6 16 4-12 4 12M7.5 12h5M13 19l2 2 5-5"/>
        @break
    @case('sparkle')
        <path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8zM19 16l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7z"/>
        @break
    @case('payments')
        <rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>
        @break
    @case('camera')
        <path d="M4 8h3l2-3h6l2 3h3a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="3.5"/>
        @break
    @case('check')
        <path d="m5 12 4 4L19 6"/>
        @break
    @case('warning')
        <path d="m12 4 9 16H3zM12 10v4m0 3h.01"/>
        @break
    @case('info')
        <circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/>
        @break
    @case('utensils')
        <path d="M7 3v8M5 3v5a2 2 0 0 0 4 0V3M7 11v10M17 3c-2 2-3 4-3 7h3v11"/>
        @break
    @case('globe')
        <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>
        @break
    @case('star')
        <path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>
        @break
    @case('image')
        <rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-8 8"/>
        @break
    @case('external')
        <path d="M14 4h6v6M20 4l-9 9M19 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/>
        @break
    @case('layout')
        <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>
        @break
    @case('tag')
        <path d="m3 12 9 9 9-9-9-9H3zM8 8h.01"/>
        @break
    @case('list')
        <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
        @break
    @case('refresh')
        <path d="M21 12a9 9 0 1 1-3-6.7M21 3v6h-6"/>
        @break
@endswitch
</svg>

@php use App\Support\Price; @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $menu['title'] }}</title>
    <style>
        @page { size: A4; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1f1a17; font-size: 10.5pt; line-height: 1.3; }
        header { text-align: center; padding-bottom: 10pt; border-bottom: 0.8pt solid #8b2f1d; }
        h1 { font-size: 26pt; margin: 0 0 3pt; letter-spacing: 0.5pt; font-weight: 700; }
        .tagline { font-style: italic; color: #6b625c; font-size: 9.5pt; margin: 0; }
        .city { color: #6b625c; font-size: 7.5pt; letter-spacing: 2.5pt; margin: 3pt 0 0; }
        section { margin-top: 16pt; break-inside: auto; }
        h2 { font-size: 11.5pt; text-align: center; color: #8b2f1d; letter-spacing: 2pt; margin: 0 0 3pt; text-transform: uppercase; font-weight: 700; }
        .intro { text-align: center; font-style: italic; color: #6b625c; font-size: 8.5pt; margin: 0 0 8pt; }
        .items { display: grid; gap: 6pt; margin-top: 8pt; }
        .item { break-inside: avoid; padding-left: 8pt; border-left: 2.5pt solid transparent; }
        .item.highlight { border-left-color: #8b2f1d; }
        .line { display: flex; justify-content: space-between; align-items: baseline; gap: 12pt; }
        .name { font-weight: 700; font-size: 10.5pt; }
        .price { font-size: 10pt; white-space: nowrap; }
        .description { color: #6b625c; font-size: 8.5pt; margin-top: 1pt; }
        footer { margin-top: 18pt; border-top: 0.8pt solid #8b2f1d; padding-top: 6pt; text-align: center; color: #6b625c; font-size: 7.5pt; }
    </style>
</head>
<body>
    <header>
        <h1>{{ $menu['title'] }}</h1>
        @if (! empty($menu['tagline']))
            <p class="tagline">{{ $menu['tagline'] }}</p>
        @endif
        <p class="city">{{ mb_strtoupper($analysis->city) }}</p>
    </header>

    @foreach ($menu['sections'] as $section)
        <section>
            <h2>{{ $section['name'] }}</h2>
            @if (! empty($section['intro']))
                <p class="intro">{{ $section['intro'] }}</p>
            @endif
            <div class="items">
                @foreach ($section['items'] as $item)
                    <div class="item {{ $item['highlight'] ? 'highlight' : '' }}">
                        <div class="line">
                            <span class="name">{{ $item['name'] }}</span>
                            <span class="price">{{ Price::menu($item['price']) }}</span>
                        </div>
                        @if (! empty($item['description']))
                            <div class="description">{{ $item['description'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    @if (! empty($menu['footer_note']))
        <footer>{{ $menu['footer_note'] }}</footer>
    @endif
</body>
</html>

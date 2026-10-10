@php use App\Support\Price; @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $menu['title'] }}</title>
    <style>
        @page { margin: 18mm 20mm; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f1a17; font-size: 10.5pt; }
        h1 { font-size: 24pt; text-align: center; margin: 0 0 4pt; letter-spacing: 0.5pt; }
        .tagline { text-align: center; font-style: italic; color: #6b625c; font-size: 9.5pt; margin: 0; }
        .city { text-align: center; color: #6b625c; font-size: 7.5pt; letter-spacing: 2pt; margin: 2pt 0 0; }
        hr { border: 0; border-top: 0.6pt solid #8b2f1d; margin: 10pt 0 4pt; }
        h2 { font-size: 12pt; text-align: center; color: #8b2f1d; letter-spacing: 1.5pt; margin: 16pt 0 2pt; text-transform: uppercase; }
        .intro { text-align: center; font-style: italic; color: #6b625c; font-size: 8.5pt; margin: 0 0 6pt; }
        table.item { width: 100%; border-collapse: collapse; margin: 0 0 7pt; page-break-inside: avoid; }
        table.item td { vertical-align: top; padding: 0; }
        td.name { font-weight: bold; font-size: 10.5pt; }
        td.price { width: 42pt; text-align: right; font-size: 10pt; white-space: nowrap; }
        .description { color: #6b625c; font-size: 8.5pt; padding-top: 1pt; }
        .highlight td.name, .highlight .description { border-left: 2.5pt solid #8b2f1d; padding-left: 7pt; }
        .footer { margin-top: 18pt; border-top: 0.6pt solid #8b2f1d; padding-top: 6pt; text-align: center; color: #6b625c; font-size: 7.5pt; }
    </style>
</head>
<body>
    <h1>{{ $menu['title'] }}</h1>
    @if (! empty($menu['tagline']))
        <p class="tagline">{{ $menu['tagline'] }}</p>
    @endif
    <p class="city">{{ mb_strtoupper($analysis->city) }}</p>
    <hr>

    @foreach ($menu['sections'] as $section)
        <h2>{{ $section['name'] }}</h2>
        @if (! empty($section['intro']))
            <p class="intro">{{ $section['intro'] }}</p>
        @else
            <div style="height: 6pt"></div>
        @endif

        @foreach ($section['items'] as $item)
            <table class="item {{ $item['highlight'] ? 'highlight' : '' }}">
                <tr>
                    <td class="name">{{ $item['name'] }}</td>
                    <td class="price">{{ Price::menu($item['price']) }}</td>
                </tr>
                @if (! empty($item['description']))
                    <tr>
                        <td class="description" colspan="2">{{ $item['description'] }}</td>
                    </tr>
                @endif
            </table>
        @endforeach
    @endforeach

    @if (! empty($menu['footer_note']))
        <p class="footer">{{ $menu['footer_note'] }}</p>
    @endif
</body>
</html>

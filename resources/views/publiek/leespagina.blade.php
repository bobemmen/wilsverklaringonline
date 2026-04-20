<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wilsverklaring &ndash; {{ $declaration->user->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    @include('partials.styles')
</head>
<body>
    <div class="public-header">
        <div style="font-family: 'EB Garamond', serif; font-size: 26px; color: var(--wv-purple-dark);">WilsVerklaring</div>
        <div class="small muted" style="margin-top: 6px;">Dit is een officiële wilsverklaring.</div>
    </div>

    <div class="public-wrap">
        <div class="card" style="text-align: center;">
            <div class="card-title" style="font-size: 20px;">{{ $declaration->user->name }}</div>
            <div class="small muted">
                Versie {{ $versionNumber }} &middot; {{ $version->created_at->format('d-m-Y') }}
            </div>
            @if(!empty($content['datum']))
                <div class="small muted">Ondertekend op {{ $content['datum'] }}</div>
            @endif
        </div>

        @include('publiek.partials.body')

        <div class="small muted" style="text-align: center; margin-top: 40px;">
            Raadpleeg de meest recente versie altijd via deze link.
        </div>
    </div>
</body>
</html>

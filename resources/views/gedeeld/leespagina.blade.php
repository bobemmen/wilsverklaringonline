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
        <div class="small muted" style="margin-top: 6px;">Gedeeld met {{ $share->name ?: $share->email }} als {{ ucfirst($share->role) }}.</div>
    </div>

    <div class="public-wrap">
        <div class="card" style="text-align: center;">
            <div class="card-title" style="font-size: 20px;">{{ $declaration->user->name }}</div>
            <div class="small muted">Versie van {{ $version->created_at->format('d-m-Y') }}</div>
        </div>

        @include('publiek.partials.body')

        <div class="small muted" style="text-align: center; margin-top: 40px;">
            U ziet altijd de meest recente versie van deze verklaring.
        </div>
    </div>
</body>
</html>

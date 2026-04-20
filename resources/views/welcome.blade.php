<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WilsVerklaring</title>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    @include('partials.styles')
</head>
<body>
    <div style="max-width: 640px; margin: 0 auto; padding: 90px 24px 40px;">
        <div style="font-family: 'EB Garamond', serif; font-size: 36px; color: var(--wv-purple-dark); text-align: center; margin-bottom: 8px;">WilsVerklaring</div>
        <div class="small muted" style="text-align: center; margin-bottom: 40px;">Uw stem, voor wanneer u zelf niet meer kunt spreken.</div>

        <div class="card">
            <h2>Een persoonlijke wilsverklaring, online en toegankelijk</h2>
            <p class="muted">WilsVerklaring helpt u stap voor stap uw wensen voor het einde van uw leven vast te leggen. U schrijft in uw eigen woorden. U deelt met uw huisarts en naasten. U houdt de regie.</p>

            <div style="margin-top: 24px; display: flex; gap: 8px;">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Naar mijn dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary">Begin nu</a>
                    <a href="{{ route('login') }}" class="btn btn-ghost">Inloggen</a>
                @endauth
            </div>
        </div>

        <div class="card">
            <h3>Hoe werkt het</h3>
            <ol style="color: var(--wv-text-muted); padding-left: 18px;">
                <li>Maak een account aan en stel tweestapsverificatie in.</li>
                <li>Doorloop de wizard. In vijf hoofdstukken vertelt u wie u bent, wat u wilt en waar uw grens ligt.</li>
                <li>Publiceer uw verklaring. Uw huisarts kan via een persoonlijke link altijd de meest actuele versie raadplegen.</li>
            </ol>
        </div>
    </div>
</body>
</html>

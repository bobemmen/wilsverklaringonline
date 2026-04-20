<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Wachten op bevestiging</title>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400&family=Jost:wght@300;400&display=swap" rel="stylesheet">
    @include('partials.styles')
</head>
<body>
    <div class="public-wrap" style="padding-top: 90px;">
        <div class="card" style="text-align: center;">
            <h1>Bevestiging per e-mail verstuurd</h1>
            <p class="muted small">Er is een bevestigingslink gestuurd naar <strong>{{ $email }}</strong>. Open die e-mail om verder te gaan naar de wilsverklaring.</p>
        </div>
    </div>
</body>
</html>

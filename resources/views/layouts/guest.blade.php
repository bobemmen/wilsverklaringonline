<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'WilsVerklaring') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    @include('partials.styles')
</head>
<body class="guest-body">
    <div class="guest-wrap">
        <div class="guest-brand">WilsVerklaring</div>
        <div class="guest-card">
            @if(session('status'))
                <div class="flash-banner">{{ session('status') }}</div>
            @endif
            @yield('content')
            {{ $slot ?? '' }}
        </div>
    </div>
</body>
</html>

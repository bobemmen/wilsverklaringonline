@extends('layouts.guest')

@section('content')
    <h1 style="font-size: 22px; margin-bottom: 4px;">Inloggen</h1>
    <p class="small muted" style="margin-bottom: 18px;">Log in om uw wilsverklaring te openen of bij te werken.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="field">
            <label class="label" for="email">E-mailadres</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label class="label" for="password">Wachtwoord</label>
            <input type="password" id="password" name="password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Inloggen</button>
    </form>

    <div class="guest-links">
        Nog geen account? <a href="{{ route('register') }}">Registreer</a>
    </div>
@endsection

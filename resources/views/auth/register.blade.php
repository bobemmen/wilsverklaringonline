@extends('layouts.guest')

@section('content')
    <h1 style="font-size: 22px; margin-bottom: 4px;">Account aanmaken</h1>
    <p class="small muted" style="margin-bottom: 18px;">Uw verklaring blijft versleuteld opgeslagen. Wij vragen alleen om wat nodig is.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="field">
            <label class="label" for="name">Naam</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label class="label" for="email">E-mailadres</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label class="label" for="password">Wachtwoord</label>
            <input type="password" id="password" name="password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label class="label" for="password_confirmation">Wachtwoord herhalen</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Account aanmaken</button>
    </form>

    <div class="guest-links">
        Al een account? <a href="{{ route('login') }}">Inloggen</a>
    </div>
@endsection

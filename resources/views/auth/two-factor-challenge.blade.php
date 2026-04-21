@extends('layouts.guest')

@section('content')
    <h1 style="font-size: 22px;">Verificatie</h1>
    <p class="small muted" style="margin-bottom: 18px;">Voer de actuele code uit uw authenticator-app in.</p>

    <form method="POST" action="{{ route('2fa.verify') }}">
        @csrf
        <div class="field">
            <label class="label" for="code">Code</label>
            <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus>
            @error('code') <div class="error">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Bevestigen</button>
    </form>
@endsection

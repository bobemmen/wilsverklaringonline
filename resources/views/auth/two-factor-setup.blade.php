@extends('layouts.guest')

@section('content')
    <h1 style="font-size: 22px;">Tweestapsverificatie instellen</h1>
    <p class="small muted" style="margin-bottom: 18px;">Uw verklaring is alleen bereikbaar na tweestapsverificatie. Scan de QR met een authenticator-app (bijv. Google Authenticator, 1Password).</p>

    @if($confirmed)
        <div class="info-block">Tweestapsverificatie is actief.</div>
    @endif

    <div class="field">
        <div class="label">Geheime sleutel</div>
        <div class="token-display">{{ $secret }}</div>
    </div>

    <div class="field">
        <div class="label">otpauth-URL</div>
        <div class="small muted" style="word-break: break-all;">{{ $otpauth }}</div>
    </div>

    <form method="POST" action="{{ route('2fa.confirm') }}">
        @csrf
        <div class="field">
            <label class="label" for="code">Code uit uw app</label>
            <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus>
            @error('code') <div class="error">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Bevestigen</button>
    </form>
@endsection

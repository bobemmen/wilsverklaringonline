@extends('layouts.app')

@section('content')
    <h1>Arts-toegang</h1>
    <p class="muted small" style="margin-bottom: 18px;">Uw huisarts hoeft geen account aan te maken. U geeft hem of haar een unieke link en daarmee is de meest actuele versie van uw verklaring steeds beschikbaar.</p>

    @if($plaintextToken)
        <div class="card" style="border-color: var(--wv-purple-light);">
            <div class="card-title">Uw nieuwe toegangscode</div>
            <p class="small muted">Kopieer deze link nu. Wij bewaren de code alleen versleuteld &mdash; na deze pagina kunt u hem niet meer inzien.</p>
            <div class="token-display">{{ url('/v/' . $plaintextToken) }}</div>
        </div>
    @endif

    <div class="card">
        <div class="card-title">Status</div>
        <div class="status-row" style="margin: 10px 0 14px;">
            @if($declaration->tokenIsActive())
                <span class="pill pill-green">Actief</span>
            @elseif($declaration->access_token)
                <span class="pill pill-red">Verlopen</span>
            @else
                <span class="pill pill-muted">Nog geen code aangemaakt</span>
            @endif
            <span class="small muted">{{ $viewCount }} raadpleging{{ $viewCount === 1 ? '' : 'en' }}</span>
        </div>

        <div style="display: flex; gap: 8px; margin-top: 10px;">
            <form method="POST" action="{{ route('token.regenerate') }}">
                @csrf
                <button type="submit" class="btn btn-primary">{{ $declaration->access_token ? 'Nieuwe code aanmaken' : 'Code aanmaken' }}</button>
            </form>
            @if($declaration->access_token)
                <form method="POST" action="{{ route('token.revoke') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Weet u zeker dat u de toegang wilt intrekken?')">Intrekken</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-title">Instellingen</div>
        <form method="POST" action="{{ route('token.settings') }}">
            @csrf
            <div class="field">
                <label class="label" for="notification_email">E-mailadres arts (optioneel)</label>
                <input type="email" id="notification_email" name="notification_email" value="{{ old('notification_email', $declaration->notification_email) }}" placeholder="praktijk@huisarts.nl">
                @error('notification_email') <div class="error">{{ $message }}</div> @enderror
                <div class="small muted" style="margin-top: 6px;">Wanneer ingevuld: bij raadpleging ontvangt uw arts een bevestigingslink per e-mail. Zonder dit adres is de toegang direct.</div>
            </div>
            <div class="field">
                <label class="label" for="access_token_expires_at">Vervaldatum (optioneel)</label>
                <input type="date" id="access_token_expires_at" name="access_token_expires_at" value="{{ old('access_token_expires_at', $declaration->access_token_expires_at?->format('Y-m-d')) }}">
                @error('access_token_expires_at') <div class="error">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-primary">Opslaan</button>
        </form>
    </div>
@endsection

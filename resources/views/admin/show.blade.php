@extends('layouts.app')

@section('content')
    @php
        $declaration = $user->declarations->first();
    @endphp

    <div class="breadcrumb small muted" style="margin-bottom: 10px;">
        <a href="{{ route('admin.index') }}" class="muted">Beheerdersdashboard</a>
        <span class="sep">/</span>
        <span>{{ $user->name }}</span>
    </div>

    <h1>{{ $user->name }}</h1>
    <p class="muted small" style="margin-bottom: 18px;">{{ $user->email }} &middot; account aangemaakt {{ $user->created_at->format('d-m-Y') }}</p>

    <div class="card-grid">
        <div class="card">
            <div class="card-title">Verklaring</div>
            <div class="status-row" style="margin: 10px 0 14px;">
                @if($declaration && $declaration->is_published)
                    <span class="pill pill-green">Definitief</span>
                @elseif($declaration)
                    <span class="pill pill-muted">Concept</span>
                @else
                    <span class="pill pill-muted">Geen verklaring</span>
                @endif
            </div>
            <div class="small muted">
                @if($declaration?->currentVersion)
                    Laatste update {{ $declaration->currentVersion->created_at->format('d-m-Y H:i') }}
                @endif
            </div>
            <div class="small muted" style="margin-top: 6px;">{{ $declaration?->versions->count() ?? 0 }} versies</div>
        </div>

        <div class="card">
            <div class="card-title">Arts-toegang</div>
            <div class="status-row" style="margin: 10px 0 14px;">
                @if($declaration?->tokenIsActive())
                    <span class="pill pill-green">Actief</span>
                @elseif($declaration?->access_token)
                    <span class="pill pill-red">Verlopen</span>
                @else
                    <span class="pill pill-muted">Geen code</span>
                @endif
            </div>
            <div class="small muted">{{ $declaration?->accessLogs->count() ?? 0 }} recente raadplegingen</div>
        </div>

        <div class="card">
            <div class="card-title">Naasten</div>
            <div class="status-row" style="margin: 10px 0 14px;">
                <span class="pill pill-muted">{{ $declaration?->shares->where('revoked_at', null)->count() ?? 0 }} actief</span>
            </div>
            <div class="small muted">
                {{ $declaration?->shares->count() ?? 0 }} totaal uitgenodigd
            </div>
        </div>
    </div>

    @if($declaration && $declaration->accessLogs->isNotEmpty())
        <div class="card">
            <div class="card-title">Laatste raadplegingen</div>
            <table>
                <thead>
                    <tr>
                        <th>Wanneer</th>
                        <th>Type</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($declaration->accessLogs as $log)
                        <tr>
                            <td class="small muted">{{ $log->accessed_at->format('d-m-Y H:i') }}</td>
                            <td style="text-transform: capitalize;">{{ $log->accessor_type }}</td>
                            <td class="small muted">{{ $log->ip_address }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="card" style="border-color: #D5A2A2;">
        <div class="card-title" style="color: #9B4A4A;">Account verwijderen</div>
        <p class="small muted">Gebruik dit alleen bij overlijden of op expliciet verzoek van de gebruiker. Deze handeling is onomkeerbaar en verwijdert alle verklaringen, versies, naasten en logs.</p>

        <form method="POST" action="{{ route('admin.destroy', $user) }}" style="margin-top: 14px;">
            @csrf
            @method('DELETE')

            <div class="field">
                <label class="label" for="reason">Reden (wordt gelogd)</label>
                <input type="text" id="reason" name="reason" value="{{ old('reason') }}" placeholder="Overleden op &hellip;" required>
                @error('reason') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label class="label" for="confirm_email">Typ ter bevestiging het e-mailadres: <strong>{{ $user->email }}</strong></label>
                <input type="text" id="confirm_email" name="confirm_email" value="{{ old('confirm_email') }}" required>
                @error('confirm_email') <div class="error">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-danger" onclick="return confirm('Definitief verwijderen? Dit kan niet ongedaan gemaakt worden.')">
                Verwijder account en verklaring
            </button>
        </form>
    </div>
@endsection

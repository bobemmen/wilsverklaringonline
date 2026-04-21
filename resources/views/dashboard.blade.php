@extends('layouts.app')

@section('content')
    <h1>Dashboard</h1>
    <p class="muted small" style="margin-bottom: 20px;">Een overzicht van uw verklaring, arts-toegang en naasten.</p>

    <div class="card-grid">
        <div class="card">
            <div class="card-title">Status wilsverklaring</div>
            <div class="status-row" style="margin: 10px 0 14px;">
                @if($declaration->is_published)
                    <span class="pill pill-green">Definitief</span>
                @else
                    <span class="pill pill-muted">Concept</span>
                @endif
                <span class="small muted">v{{ $versionCount ?: 0 }}</span>
            </div>
            <div class="small muted">
                @if($declaration->currentVersion)
                    Laatste wijziging {{ $declaration->currentVersion->created_at->format('d-m-Y') }}
                @else
                    Nog niet gepubliceerd.
                @endif
            </div>
            <div style="margin-top: 14px;">
                <a href="{{ route('wizard.show') }}" class="btn btn-primary">Openen in wizard</a>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Arts-toegang</div>
            <div class="status-row" style="margin: 10px 0 14px;">
                @if($declaration->tokenIsActive())
                    <span class="pill pill-green">Actief</span>
                @elseif($declaration->access_token)
                    <span class="pill pill-red">Verlopen</span>
                @else
                    <span class="pill pill-muted">Geen code</span>
                @endif
                <span class="small muted">{{ $viewCount }} raadpleging{{ $viewCount === 1 ? '' : 'en' }}</span>
            </div>
            <div class="small muted">
                @if($declaration->access_token_expires_at)
                    Vervalt {{ $declaration->access_token_expires_at->format('d-m-Y') }}
                @else
                    Geen vervaldatum ingesteld.
                @endif
            </div>
            <div style="margin-top: 14px;">
                <a href="{{ route('token.index') }}" class="btn btn-ghost">Beheer toegang</a>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Naasten</div>
            <div class="status-row" style="margin: 10px 0 14px;">
                <span class="pill pill-muted">{{ $activeShares }} actief</span>
            </div>
            <div class="small muted">
                Nodig een naaste of gemachtigde uit om uw verklaring in te zien.
            </div>
            <div style="margin-top: 14px;">
                <a href="{{ route('naasten.index') }}" class="btn btn-ghost">Naasten beheren</a>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top: 18px;">
        <div class="card-title">Downloaden en bewaren</div>
        <p class="small muted">U kunt uw verklaring als PDF afdrukken en ondertekenen om op papier te bewaren. De online versie blijft altijd de leidende.</p>
        <a href="{{ route('wizard.pdf') }}" class="btn btn-ghost">Download PDF</a>
    </div>
@endsection

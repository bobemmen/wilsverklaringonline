@extends('layouts.app')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: baseline;">
        <div>
            <h1>Beheerdersdashboard</h1>
            <p class="muted small" style="margin-bottom: 18px;">Overzicht van alle gebruikers met een wilsverklaring. De inhoud van de verklaringen is versleuteld en ook voor u niet inzichtelijk.</p>
        </div>
        <div class="small muted">{{ $users->total() }} gebruiker{{ $users->total() === 1 ? '' : 's' }}</div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('admin.index') }}" style="margin-bottom: 14px;">
            <div class="field" style="margin-bottom: 0;">
                <label class="label" for="q">Zoeken op naam of e-mail</label>
                <input type="text" id="q" name="q" value="{{ $search }}" placeholder="naam of e-mail &hellip;">
            </div>
        </form>

        @if($users->isEmpty())
            <p class="muted small">Geen gebruikers gevonden.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Naam</th>
                        <th>E-mail</th>
                        <th>Status</th>
                        <th>Laatste update</th>
                        <th>Versies</th>
                        <th>Naasten</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        @php
                            $declaration = $user->declarations->first();
                            $currentVersion = $declaration?->currentVersion;
                        @endphp
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td class="small muted">{{ $user->email }}</td>
                            <td>
                                @if($declaration && $declaration->is_published)
                                    <span class="pill pill-green">Definitief</span>
                                @elseif($declaration)
                                    <span class="pill pill-muted">Concept</span>
                                @else
                                    <span class="pill pill-muted">Geen</span>
                                @endif
                            </td>
                            <td class="small muted">
                                @if($currentVersion)
                                    {{ $currentVersion->created_at->format('d-m-Y') }}
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td class="small muted">{{ $declaration?->versions_count ?? 0 }}</td>
                            <td class="small muted">{{ $declaration?->shares_count ?? 0 }}</td>
                            <td style="text-align: right;">
                                <a href="{{ route('admin.show', $user) }}" class="btn btn-ghost">Openen</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($users->hasPages())
            <div style="margin-top: 16px;">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection

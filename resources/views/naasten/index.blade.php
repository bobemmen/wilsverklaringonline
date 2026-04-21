@extends('layouts.app')

@section('content')
    <h1>Naasten en gemachtigden</h1>
    <p class="muted small" style="margin-bottom: 18px;">Iemand in wie u vertrouwen heeft kan uw verklaring inzien via een persoonlijke link. Voor een gemachtigde kunt u later extra bevoegdheden instellen.</p>

    @if(session('share_plaintext_token'))
        <div class="card" style="border-color: var(--wv-purple-light);">
            <div class="card-title">Persoonlijke link aangemaakt</div>
            <p class="small muted">Deel deze link alleen met de beoogde ontvanger. Wij bewaren de code alleen versleuteld.</p>
            <div class="token-display">{{ url('/gedeeld/' . session('share_plaintext_token')) }}</div>
        </div>
    @endif

    <div class="card">
        <div class="card-title">Nieuwe uitnodiging</div>
        <form method="POST" action="{{ route('naasten.invite') }}">
            @csrf
            <div class="field">
                <label class="label" for="name">Naam</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}">
            </div>
            <div class="field">
                <label class="label" for="email">E-mailadres</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                @error('email') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label class="label">Rol</label>
                <div class="radio-group">
                    <label class="radio-option {{ old('role', 'naaste') === 'naaste' ? 'selected' : '' }}">
                        <input type="radio" name="role" value="naaste" {{ old('role', 'naaste') === 'naaste' ? 'checked' : '' }} style="display:none;" onchange="this.closest('.radio-group').querySelectorAll('.radio-option').forEach(o=>o.classList.remove('selected')); this.closest('.radio-option').classList.add('selected')">
                        <span class="radio-dot"></span>
                        <span>
                            <span class="radio-label">Naaste</span>
                            <div class="radio-help">Mag meelezen.</div>
                        </span>
                    </label>
                    <label class="radio-option {{ old('role') === 'gemachtigde' ? 'selected' : '' }}">
                        <input type="radio" name="role" value="gemachtigde" {{ old('role') === 'gemachtigde' ? 'checked' : '' }} style="display:none;" onchange="this.closest('.radio-group').querySelectorAll('.radio-option').forEach(o=>o.classList.remove('selected')); this.closest('.radio-option').classList.add('selected')">
                        <span class="radio-dot"></span>
                        <span>
                            <span class="radio-label">Gemachtigde</span>
                            <div class="radio-help">Mag namens u spreken als u dat zelf niet meer kunt.</div>
                        </span>
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Uitnodiging aanmaken</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Overzicht</div>
        @if($shares->isEmpty())
            <p class="muted small">Nog geen naasten of gemachtigden uitgenodigd.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Naam</th>
                        <th>E-mail</th>
                        <th>Rol</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shares as $share)
                        <tr>
                            <td>{{ $share->name ?: '—' }}</td>
                            <td>{{ $share->email }}</td>
                            <td style="text-transform: capitalize;">{{ $share->role }}</td>
                            <td>
                                @if($share->revoked_at)
                                    <span class="pill pill-red">Ingetrokken</span>
                                @elseif($share->accepted_at)
                                    <span class="pill pill-green">Bekeken</span>
                                @else
                                    <span class="pill pill-muted">Uitgenodigd</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                @if(!$share->revoked_at)
                                    <form method="POST" action="{{ route('naasten.revoke', $share) }}" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost" onclick="return confirm('Toegang intrekken?')">Intrekken</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection

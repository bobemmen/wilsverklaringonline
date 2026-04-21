@extends('layouts.app')

@section('content')
    <h1>Versies van uw verklaring</h1>
    <p class="muted small" style="margin-bottom: 18px;">Elke publicatie is een nieuwe versie. Eerdere versies blijven bewaard zodat u kunt terugzien wat wanneer gold. Uw arts ziet altijd de meest recente versie.</p>

    <div class="card">
        @if($versions->isEmpty())
            <p class="muted">Er zijn nog geen gepubliceerde versies.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Versie</th>
                        <th>Gepubliceerd op</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($versions as $i => $version)
                        <tr>
                            <td>v{{ $versions->count() - $i }}</td>
                            <td>{{ $version->created_at->format('d-m-Y H:i') }}</td>
                            <td>
                                @if($version->id === $declaration->current_version_id)
                                    <span class="pill pill-green">Huidig</span>
                                @else
                                    <span class="pill pill-muted">Archief</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection

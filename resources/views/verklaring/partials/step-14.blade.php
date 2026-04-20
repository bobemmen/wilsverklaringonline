<div class="info-block">
    Lees uw verklaring nog eens rustig door. Klopt alles? Dan kunt u hieronder publiceren. Uw arts zal altijd de meest recente versie te zien krijgen.
</div>

@php
    use App\Support\DeclarationContent;
    $d = DeclarationContent::normalize($data);
    $blocks = [
        'Introductie' => [
            'Wie ik ben' => $d['intro'],
            'Wat mijn leven waarde geeft' => $d['waarde'],
        ],
        'Grens en ervaring' => [
            'Mijn grens' => $d['grens'],
            'Vormende ervaringen' => $d['ervaring'],
        ],
        'Behandelverbod' => [
            'Reanimatie' => $d['reanimatie'] . ($d['reanimatie_toelichting'] ? ' &mdash; ' . $d['reanimatie_toelichting'] : ''),
            'Beademing' => $d['beademing'],
            'Intensive care' => $d['ic'],
            'Antibiotica' => $d['antibiotica'],
            'Sondevoeding' => $d['sonde'],
            'Toelichting' => $d['beademing_toelichting'] . ' ' . $d['behandel_toelichting'],
        ],
        'Palliatieve sedatie' => [
            'Wens' => $d['palliatief_wens'],
            'Fysiek lijden' => $d['palliatief_fysiek'],
            'Geestelijk lijden' => $d['palliatief_geestelijk'],
            'Diepte' => $d['palliatief_diepte'],
        ],
    ];
    if ($d['euthanasie_opgenomen'] === 'ja' || $d['euthanasie_opgenomen'] === true) {
        $blocks['Euthanasieverzoek'] = [
            'Ondraaglijk lijden' => $d['euthanasie_lijden'],
            'Concrete situatie' => $d['euthanasie_situatie'],
            'Bij wilsonbekwaamheid' => $d['euthanasie_wilsonbekwaam'],
            'Toelichting wilsonbekwaamheid' => $d['euthanasie_wilsonbekwaam_toelichting'],
            'Brief aan de arts' => $d['euthanasie_brief'],
        ];
    }
    $blocks['Na het overlijden'] = [
        'Orgaandonatie' => $d['orgaandonatie'],
        'Uitvaart' => $d['uitvaart'],
        'Overig' => $d['overig'],
    ];
    $blocks['Afsluiting'] = [
        'Arts' => $d['arts'],
        'Besproken met arts' => $d['besproken'],
        'Datum' => $d['datum'],
    ];
@endphp

@foreach($blocks as $title => $items)
    <div style="margin-bottom: 18px;">
        <div class="card-title" style="margin-bottom: 8px;">{{ $title }}</div>
        @foreach($items as $label => $value)
            <div class="summary-block">
                <div class="summary-label">{{ $label }}</div>
                <div class="summary-value">
                    @if(trim((string) $value) === '')
                        <em>Niet ingevuld</em>
                    @else
                        {!! nl2br(e(trim((string) $value))) !!}
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endforeach

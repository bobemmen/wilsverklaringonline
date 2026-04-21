<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Wilsverklaring {{ $user->name }}</title>
    <style>
        @page { margin: 2cm; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #2C2C2A;
            line-height: 1.5;
        }
        h1, h2, h3 { color: #26215C; font-family: 'DejaVu Serif', serif; font-weight: normal; margin: 0; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        h2 { font-size: 15px; margin-top: 18px; border-bottom: 1px solid #D3D1C7; padding-bottom: 4px; }
        h3 { font-size: 12px; margin-top: 8px; }
        .header { text-align: center; padding-bottom: 12px; border-bottom: 1px solid #D3D1C7; }
        .subtle { color: #888780; font-size: 10px; }
        .badge {
            display: inline-block;
            background: #EEEDFE;
            color: #26215C;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .quote {
            border-left: 2px solid #AFA9EC;
            padding: 2px 10px;
            color: #444;
            font-style: italic;
            margin: 8px 0;
        }
        .field { margin: 8px 0; }
        .label { color: #888780; font-size: 9px; text-transform: uppercase; letter-spacing: 0.1em; }
        .sig {
            margin-top: 40px;
            padding-top: 12px;
            border-top: 1px solid #D3D1C7;
        }
        .token-box {
            margin-top: 14px;
            padding: 10px;
            background: #F1EFE8;
            border: 1px solid #D3D1C7;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Wilsverklaring</h1>
        <div class="subtle">{{ $user->name }}</div>
        <div class="subtle">
            @if($version)
                Versie van {{ $version->created_at->format('d-m-Y') }}
            @else
                Concept
            @endif
        </div>
    </div>

    @if(!empty($content['intro']) || !empty($content['waarde']))
        <h2>Over mij</h2>
        @if(!empty($content['intro']))
            <div class="field"><div class="label">Wie ik ben</div>{{ $content['intro'] }}</div>
        @endif
        @if(!empty($content['waarde']))
            <div class="field"><div class="label">Wat mijn leven waarde geeft</div>{{ $content['waarde'] }}</div>
        @endif
    @endif

    @if(!empty($content['grens']) || !empty($content['ervaring']))
        <h2>Mijn grens</h2>
        @if(!empty($content['grens']))
            <div class="field"><div class="label">Grens</div>{{ $content['grens'] }}</div>
        @endif
        @if(!empty($content['ervaring']))
            <div class="field"><div class="label">Ervaringen</div>{{ $content['ervaring'] }}</div>
        @endif
    @endif

    <h2>Behandelverbod</h2>
    @foreach(['reanimatie' => 'Reanimatie', 'beademing' => 'Beademing', 'ic' => 'Intensive care', 'antibiotica' => 'Antibiotica', 'sonde' => 'Sondevoeding'] as $k => $l)
        @if(!empty($content[$k]))
            <div class="field"><span class="label">{{ $l }}:</span> <span class="badge">{{ ucfirst($content[$k]) }}</span></div>
        @endif
    @endforeach
    @foreach(['reanimatie_toelichting', 'beademing_toelichting', 'behandel_toelichting'] as $k)
        @if(!empty($content[$k]))
            <div class="quote">{{ $content[$k] }}</div>
        @endif
    @endforeach

    @if(!empty($content['palliatief_wens']))
        <h2>Palliatieve sedatie</h2>
        <div class="field"><span class="label">Wens:</span> <span class="badge">{{ ucfirst($content['palliatief_wens']) }}</span></div>
        @if(!empty($content['palliatief_fysiek']))
            <div class="field"><div class="label">Fysiek lijden</div>{{ $content['palliatief_fysiek'] }}</div>
        @endif
        @if(!empty($content['palliatief_geestelijk']))
            <div class="field"><div class="label">Geestelijk lijden</div>{{ $content['palliatief_geestelijk'] }}</div>
        @endif
        @if(!empty($content['palliatief_diepte']))
            <div class="field"><span class="label">Diepte:</span> <span class="badge">{{ ucfirst($content['palliatief_diepte']) }}</span></div>
        @endif
    @endif

    @if(!empty($content['euthanasie_opgenomen']) && ($content['euthanasie_opgenomen'] === 'ja' || $content['euthanasie_opgenomen'] === true))
        <h2>Euthanasieverzoek</h2>
        @if(!empty($content['euthanasie_lijden']))
            <div class="field"><div class="label">Ondraaglijk lijden</div>{{ $content['euthanasie_lijden'] }}</div>
        @endif
        @if(!empty($content['euthanasie_situatie']))
            <div class="field"><div class="label">Situatie</div>{{ $content['euthanasie_situatie'] }}</div>
        @endif
        @if(!empty($content['euthanasie_wilsonbekwaam']))
            <div class="field"><span class="label">Bij wilsonbekwaamheid:</span> <span class="badge">{{ ucfirst($content['euthanasie_wilsonbekwaam']) }}</span></div>
        @endif
        @if(!empty($content['euthanasie_wilsonbekwaam_toelichting']))
            <div class="quote">{{ $content['euthanasie_wilsonbekwaam_toelichting'] }}</div>
        @endif
        @if(!empty($content['euthanasie_brief']))
            <h3>Brief aan de arts</h3>
            <div class="quote">{{ $content['euthanasie_brief'] }}</div>
        @endif
    @endif

    @if(!empty($content['orgaandonatie']) || !empty($content['uitvaart']) || !empty($content['overig']))
        <h2>Na het overlijden</h2>
        @if(!empty($content['orgaandonatie']))
            <div class="field"><span class="label">Orgaandonatie:</span> <span class="badge">{{ ucfirst($content['orgaandonatie']) }}</span></div>
        @endif
        @if(!empty($content['uitvaart']))
            <div class="field"><div class="label">Uitvaart</div>{{ $content['uitvaart'] }}</div>
        @endif
        @if(!empty($content['overig']))
            <div class="field"><div class="label">Overig</div>{{ $content['overig'] }}</div>
        @endif
    @endif

    <div class="sig">
        <table style="width:100%; font-size: 10px;">
            <tr>
                <td>
                    <div class="label">Ondertekend door</div>
                    <div>{{ $user->name }}</div>
                </td>
                <td style="text-align:right;">
                    <div class="label">Datum</div>
                    <div>{{ $content['datum'] ?? '' }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding-top: 30px; border-top: 1px solid #D3D1C7; width: 60%;">Handtekening</td>
                <td style="padding-top: 30px; border-top: 1px solid #D3D1C7;">Plaats</td>
            </tr>
        </table>
    </div>

    @if($publicUrl)
        <div class="token-box">
            <div class="label">Online raadplegen door arts</div>
            <div>Vraag de patiënt om de persoonlijke link. Actuele versie is altijd online beschikbaar.</div>
        </div>
    @endif
</body>
</html>

@if(trim((string) ($content['intro'] ?? '')) !== '' || trim((string) ($content['waarde'] ?? '')) !== '')
    <div class="block-header">Over mij</div>
    <div class="block-section">
        @if(!empty($content['intro']))
            <div class="summary-label">Wie ik ben</div>
            <div class="summary-value" style="margin-bottom: 12px;">{!! nl2br(e($content['intro'])) !!}</div>
        @endif
        @if(!empty($content['waarde']))
            <div class="summary-label">Wat mijn leven waarde geeft</div>
            <div class="summary-value">{!! nl2br(e($content['waarde'])) !!}</div>
        @endif
    </div>
@endif

@if(trim((string) ($content['grens'] ?? '')) !== '' || trim((string) ($content['ervaring'] ?? '')) !== '')
    <div class="block-header">Mijn grens</div>
    <div class="block-section">
        @if(!empty($content['grens']))
            <div class="summary-label">Grens</div>
            <div class="summary-value" style="margin-bottom: 12px;">{!! nl2br(e($content['grens'])) !!}</div>
        @endif
        @if(!empty($content['ervaring']))
            <div class="summary-label">Ervaringen</div>
            <div class="summary-value">{!! nl2br(e($content['ervaring'])) !!}</div>
        @endif
    </div>
@endif

<div class="block-header">Behandelverbod</div>
<div class="block-section">
    @foreach([
        'reanimatie' => 'Reanimatie',
        'beademing' => 'Beademing',
        'ic' => 'Intensive care',
        'antibiotica' => 'Antibiotica',
        'sonde' => 'Sondevoeding',
    ] as $key => $label)
        @if(!empty($content[$key]))
            <div style="margin-bottom: 8px;">
                <span class="summary-label" style="display: inline-block; min-width: 150px;">{{ $label }}</span>
                <span class="pill">{{ ucfirst($content[$key]) }}</span>
            </div>
        @endif
    @endforeach
    @foreach(['reanimatie_toelichting', 'beademing_toelichting', 'behandel_toelichting'] as $field)
        @if(!empty($content[$field]))
            <div class="summary-value" style="margin-top: 10px; font-style: italic; color: var(--wv-text-muted); border-left: 2px solid var(--wv-purple-light); padding-left: 10px;">
                {!! nl2br(e($content[$field])) !!}
            </div>
        @endif
    @endforeach
</div>

@if(!empty($content['palliatief_wens']))
    <div class="block-header">Palliatieve sedatie</div>
    <div class="block-section">
        <div style="margin-bottom: 8px;">
            <span class="summary-label" style="display: inline-block; min-width: 150px;">Wens</span>
            <span class="pill">{{ ucfirst($content['palliatief_wens']) }}</span>
        </div>
        @foreach(['palliatief_fysiek' => 'Fysiek lijden', 'palliatief_geestelijk' => 'Geestelijk lijden'] as $k => $l)
            @if(!empty($content[$k]))
                <div style="margin-top: 10px;">
                    <div class="summary-label">{{ $l }}</div>
                    <div class="summary-value">{!! nl2br(e($content[$k])) !!}</div>
                </div>
            @endif
        @endforeach
        @if(!empty($content['palliatief_diepte']))
            <div style="margin-top: 10px;">
                <span class="summary-label" style="display: inline-block; min-width: 150px;">Diepte</span>
                <span class="pill">{{ ucfirst($content['palliatief_diepte']) }}</span>
            </div>
        @endif
    </div>
@endif

@if(!empty($content['euthanasie_opgenomen']) && ($content['euthanasie_opgenomen'] === 'ja' || $content['euthanasie_opgenomen'] === true))
    <div class="block-header">Euthanasieverzoek</div>
    <div class="block-section">
        @foreach(['euthanasie_lijden' => 'Wat ik ondraaglijk vind', 'euthanasie_situatie' => 'Situatie'] as $k => $l)
            @if(!empty($content[$k]))
                <div style="margin-bottom: 12px;">
                    <div class="summary-label">{{ $l }}</div>
                    <div class="summary-value">{!! nl2br(e($content[$k])) !!}</div>
                </div>
            @endif
        @endforeach
        @if(!empty($content['euthanasie_wilsonbekwaam']))
            <div style="margin-bottom: 12px;">
                <span class="summary-label" style="display: inline-block; min-width: 150px;">Bij wilsonbekwaamheid</span>
                <span class="pill">{{ ucfirst($content['euthanasie_wilsonbekwaam']) }}</span>
            </div>
        @endif
        @if(!empty($content['euthanasie_wilsonbekwaam_toelichting']))
            <div style="margin-bottom: 12px;">
                <div class="summary-label">Toelichting</div>
                <div class="summary-value">{!! nl2br(e($content['euthanasie_wilsonbekwaam_toelichting'])) !!}</div>
            </div>
        @endif
        @if(!empty($content['euthanasie_brief']))
            <div style="margin-top: 14px;">
                <div class="summary-label">Brief aan de arts</div>
                <div class="summary-value" style="font-style: italic; color: var(--wv-text-muted); border-left: 2px solid var(--wv-purple-light); padding-left: 10px; margin-top: 6px;">
                    {!! nl2br(e($content['euthanasie_brief'])) !!}
                </div>
            </div>
        @endif
    </div>
@endif

@if(!empty($content['orgaandonatie']) || !empty($content['uitvaart']) || !empty($content['overig']))
    <div class="block-header">Na het overlijden</div>
    <div class="block-section">
        @if(!empty($content['orgaandonatie']))
            <div style="margin-bottom: 10px;">
                <span class="summary-label" style="display: inline-block; min-width: 150px;">Orgaandonatie</span>
                <span class="pill">{{ ucfirst($content['orgaandonatie']) }}</span>
            </div>
        @endif
        @foreach(['uitvaart' => 'Uitvaart', 'overig' => 'Overig'] as $k => $l)
            @if(!empty($content[$k]))
                <div style="margin-bottom: 10px;">
                    <div class="summary-label">{{ $l }}</div>
                    <div class="summary-value">{!! nl2br(e($content[$k])) !!}</div>
                </div>
            @endif
        @endforeach
    </div>
@endif

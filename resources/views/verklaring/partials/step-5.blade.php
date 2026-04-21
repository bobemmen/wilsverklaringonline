<div class="info-block">
    Antibiotica bestrijden infecties; sondevoeding brengt vloeistof en voeding binnen wanneer eten en drinken niet meer lukt. Soms verlengen deze behandelingen een leven dat al aan het eindigen is &mdash; u bepaalt of u dat wilt.
</div>

<div class="field">
    <label class="label">Antibiotica bij een ernstige infectie</label>
    <div class="radio-group">
        @include('partials.radio', ['name' => 'antibiotica', 'value' => 'ja', 'label' => 'Ja, altijd toedienen', 'model' => $data['antibiotica']])
        @include('partials.radio', ['name' => 'antibiotica', 'value' => 'nee', 'label' => 'Nee, als ik al ernstig ziek ben', 'model' => $data['antibiotica']])
        @include('partials.radio', ['name' => 'antibiotica', 'value' => 'afwegen', 'label' => 'Alleen na afweging met de arts en mijn naasten', 'model' => $data['antibiotica']])
    </div>
</div>

<div class="field">
    <label class="label">Sondevoeding of kunstmatige voeding</label>
    <div class="radio-group">
        @include('partials.radio', ['name' => 'sonde', 'value' => 'ja', 'label' => 'Ja, zolang dat zinvol is', 'model' => $data['sonde']])
        @include('partials.radio', ['name' => 'sonde', 'value' => 'nee', 'label' => 'Nee, ik wil geen sondevoeding', 'model' => $data['sonde']])
        @include('partials.radio', ['name' => 'sonde', 'value' => 'kort', 'label' => 'Alleen als tijdelijke overbrugging', 'model' => $data['sonde']])
    </div>
</div>

<div class="field">
    <label class="label" for="behandel_toelichting">Toelichting (optioneel)</label>
    <textarea id="behandel_toelichting" wire:model="data.behandel_toelichting" placeholder="Mijn overwegingen &hellip;"></textarea>
</div>

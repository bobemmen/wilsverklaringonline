<div class="info-block">
    Antibiotica bestrijden infecties; sondevoeding brengt vloeistof en voeding binnen wanneer eten en drinken niet meer lukt. Soms verlengen deze behandelingen een leven dat al aan het eindigen is &mdash; u bepaalt of u dat wilt.
</div>

<div class="field">
    <label class="label">Antibiotica bij een ernstige infectie</label>
    <div class="radio-group">
        <?= $this->radio('antibiotica', 'ja', 'Ja, altijd toedienen', null, $data['antibiotica']) ?>
        <?= $this->radio('antibiotica', 'nee', 'Nee, als ik al ernstig ziek ben', null, $data['antibiotica']) ?>
        <?= $this->radio('antibiotica', 'afwegen', 'Alleen na afweging met de arts en mijn naasten', null, $data['antibiotica']) ?>
    </div>
</div>

<div class="field">
    <label class="label">Sondevoeding of kunstmatige voeding</label>
    <div class="radio-group">
        <?= $this->radio('sonde', 'ja', 'Ja, zolang dat zinvol is', null, $data['sonde']) ?>
        <?= $this->radio('sonde', 'nee', 'Nee, ik wil geen sondevoeding', null, $data['sonde']) ?>
        <?= $this->radio('sonde', 'kort', 'Alleen als tijdelijke overbrugging', null, $data['sonde']) ?>
    </div>
</div>

<div class="field">
    <label class="label" for="behandel_toelichting">Toelichting (optioneel)</label>
    <?= $this->textarea('behandel_toelichting', $data['behandel_toelichting'], 'Mijn overwegingen &hellip;') ?>
</div>

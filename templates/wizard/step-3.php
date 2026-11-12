<div class="info-block">
    Reanimatie kan levens redden, maar het komt ook met risico's. Geeft uw hart het op, dan wordt met druk op de borstkas en soms met stroomstoten geprobeerd u terug te halen. Wat wilt u?
</div>

<div class="field">
    <label class="label">Reanimatie</label>
    <div class="radio-group">
        <?= $this->radio('reanimatie', 'ja', 'Ja, ik wil gereanimeerd worden', null, $data['reanimatie']) ?>
        <?= $this->radio('reanimatie', 'nee', 'Nee, ik wil niet gereanimeerd worden', 'Ook niet als de kans op herstel groot is.', $data['reanimatie']) ?>
        <?= $this->radio('reanimatie', 'voorwaarden', 'Alleen onder voorwaarden', 'Omschrijf hieronder wanneer wel en wanneer niet.', $data['reanimatie']) ?>
    </div>
</div>

<div class="field">
    <label class="label" for="reanimatie_toelichting">Toelichting (optioneel)</label>
    <?= $this->textarea('reanimatie_toelichting', $data['reanimatie_toelichting'], 'Ik wil niet gereanimeerd worden omdat &hellip;') ?>
</div>

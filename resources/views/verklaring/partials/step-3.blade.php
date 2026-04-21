<div class="info-block">
    Reanimatie kan levens redden, maar het komt ook met risico's. Geeft uw hart het op, dan wordt met druk op de borstkas en soms met stroomstoten geprobeerd u terug te halen. Wat wilt u?
</div>

<div class="field">
    <label class="label">Reanimatie</label>
    <div class="radio-group">
        @include('partials.radio', ['name' => 'reanimatie', 'value' => 'ja', 'label' => 'Ja, ik wil gereanimeerd worden', 'model' => $data['reanimatie']])
        @include('partials.radio', ['name' => 'reanimatie', 'value' => 'nee', 'label' => 'Nee, ik wil niet gereanimeerd worden', 'help' => 'Ook niet als de kans op herstel groot is.', 'model' => $data['reanimatie']])
        @include('partials.radio', ['name' => 'reanimatie', 'value' => 'voorwaarden', 'label' => 'Alleen onder voorwaarden', 'help' => 'Omschrijf hieronder wanneer wel en wanneer niet.', 'model' => $data['reanimatie']])
    </div>
</div>

<div class="field">
    <label class="label" for="reanimatie_toelichting">Toelichting (optioneel)</label>
    <textarea id="reanimatie_toelichting" wire:model="data.reanimatie_toelichting" placeholder="Ik wil niet gereanimeerd worden omdat &hellip;"></textarea>
</div>

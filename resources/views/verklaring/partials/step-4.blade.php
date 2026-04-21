<div class="info-block">
    Bij ernstig ademhalingsfalen kan kunstmatige beademing uw ademhaling overnemen. Soms gebeurt dat op de intensive care, soms tijdelijk met een kapje. Denk na over wat u wel en niet wilt.
</div>

<div class="field">
    <label class="label">Kunstmatige beademing</label>
    <div class="radio-group">
        @include('partials.radio', ['name' => 'beademing', 'value' => 'ja', 'label' => 'Ja, ik wil beademd worden', 'model' => $data['beademing']])
        @include('partials.radio', ['name' => 'beademing', 'value' => 'nee', 'label' => 'Nee, ik wil niet beademd worden', 'model' => $data['beademing']])
        @include('partials.radio', ['name' => 'beademing', 'value' => 'kort', 'label' => 'Alleen kortdurend, als herstel waarschijnlijk is', 'model' => $data['beademing']])
    </div>
</div>

<div class="field">
    <label class="label">Opname op de intensive care</label>
    <div class="radio-group">
        @include('partials.radio', ['name' => 'ic', 'value' => 'ja', 'label' => 'Ja, als het kan helpen', 'model' => $data['ic']])
        @include('partials.radio', ['name' => 'ic', 'value' => 'nee', 'label' => 'Nee, ik wil niet naar de IC', 'model' => $data['ic']])
        @include('partials.radio', ['name' => 'ic', 'value' => 'twijfel', 'label' => 'Alleen als de kans op herstel in redelijke kwaliteit groot is', 'model' => $data['ic']])
    </div>
</div>

<div class="field">
    <label class="label" for="beademing_toelichting">Toelichting (optioneel)</label>
    <textarea id="beademing_toelichting" wire:model="data.beademing_toelichting" placeholder="Waarom kies ik hiervoor &hellip;"></textarea>
</div>

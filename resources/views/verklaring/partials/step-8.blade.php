<div class="info-block">
    Een euthanasieverzoek is een persoonlijke keuze die niet bij iedereen past. U kunt dit hoofdstuk overslaan als u er (nog) niet over wilt schrijven. U kunt later altijd terugkomen.
</div>

<div class="field">
    <label class="label">Wilt u een euthanasieverzoek opnemen?</label>
    <div class="radio-group">
        @include('partials.radio', ['name' => 'euthanasie_opgenomen', 'value' => 'ja', 'label' => 'Ja, ik wil dit hoofdstuk invullen', 'model' => $data['euthanasie_opgenomen']])
        @include('partials.radio', ['name' => 'euthanasie_opgenomen', 'value' => 'nee', 'label' => 'Nee, ik sla dit hoofdstuk over', 'model' => $data['euthanasie_opgenomen']])
    </div>
</div>

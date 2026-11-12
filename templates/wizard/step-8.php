<div class="callout"><p>Een euthanasieverzoek is een persoonlijke keuze die niet bij iedereen past. U kunt dit hoofdstuk overslaan als u er (nog) niet over wilt schrijven. U kunt later altijd terugkomen.</p></div>

<fieldset class="field">
    <legend>Wilt u een euthanasieverzoek opnemen?</legend>
    <?= $this->radio('euthanasie_opgenomen', 'ja', 'Ja, ik wil dit hoofdstuk invullen', null, $data['euthanasie_opgenomen']) ?>
    <?= $this->radio('euthanasie_opgenomen', 'nee', 'Nee, ik sla dit hoofdstuk over', null, $data['euthanasie_opgenomen']) ?>
</fieldset>

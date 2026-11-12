<div class="callout"><p>Soms kan iemand later zelf niet meer om euthanasie vragen, bijvoorbeeld door dementie of een herseninfarct. U kunt op voorhand aangeven of u ook dan wilt dat uw eerdere wens wordt uitgevoerd.</p></div>

<fieldset class="field">
    <legend>Bij wilsonbekwaamheid</legend>
    <?= $this->radio('euthanasie_wilsonbekwaam', 'ja', 'Ja, ik wil dat mijn verzoek dan nog steeds geldt', null, $data['euthanasie_wilsonbekwaam']) ?>
    <?= $this->radio('euthanasie_wilsonbekwaam', 'nee', 'Nee, alleen zolang ik nog wilsbekwaam ben', null, $data['euthanasie_wilsonbekwaam']) ?>
    <?= $this->radio('euthanasie_wilsonbekwaam', 'voorwaarden', 'Alleen onder voorwaarden', 'Beschrijf hieronder welke voorwaarden voor u gelden.', $data['euthanasie_wilsonbekwaam']) ?>
</fieldset>

<div class="field">
    <label for="euthanasie_wilsonbekwaam_toelichting">Toelichting</label>
    <?= $this->textarea('euthanasie_wilsonbekwaam_toelichting', $data['euthanasie_wilsonbekwaam_toelichting'], 'Als ik dement ben en …') ?>
</div>

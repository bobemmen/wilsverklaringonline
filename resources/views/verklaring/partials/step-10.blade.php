<div class="info-block">
    Soms kan iemand later zelf niet meer om euthanasie vragen, bijvoorbeeld door dementie of een herseninfarct. U kunt op voorhand aangeven of u ook dan wilt dat uw eerdere wens wordt uitgevoerd.
</div>

<div class="field">
    <label class="label">Bij wilsonbekwaamheid</label>
    <div class="radio-group">
        @include('partials.radio', ['name' => 'euthanasie_wilsonbekwaam', 'value' => 'ja', 'label' => 'Ja, ik wil dat mijn verzoek dan nog steeds geldt', 'model' => $data['euthanasie_wilsonbekwaam']])
        @include('partials.radio', ['name' => 'euthanasie_wilsonbekwaam', 'value' => 'nee', 'label' => 'Nee, alleen zolang ik nog wilsbekwaam ben', 'model' => $data['euthanasie_wilsonbekwaam']])
        @include('partials.radio', ['name' => 'euthanasie_wilsonbekwaam', 'value' => 'voorwaarden', 'label' => 'Alleen onder voorwaarden', 'help' => 'Beschrijf hieronder welke voorwaarden voor u gelden.', 'model' => $data['euthanasie_wilsonbekwaam']])
    </div>
</div>

<div class="field">
    <label class="label" for="euthanasie_wilsonbekwaam_toelichting">Toelichting</label>
    <textarea id="euthanasie_wilsonbekwaam_toelichting" wire:model="data.euthanasie_wilsonbekwaam_toelichting" placeholder="Als ik dement ben en &hellip;"></textarea>
</div>

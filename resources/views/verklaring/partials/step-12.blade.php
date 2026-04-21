<div class="info-block">
    Tot slot een aantal keuzes voor na uw overlijden. Niet verplicht, wel fijn om er iets over te zeggen zodat uw naasten weten wat u had gewild.
</div>

<div class="field">
    <label class="label">Orgaandonatie</label>
    <div class="radio-group">
        @include('partials.radio', ['name' => 'orgaandonatie', 'value' => 'ja', 'label' => 'Ja, ik stel mijn organen beschikbaar', 'model' => $data['orgaandonatie']])
        @include('partials.radio', ['name' => 'orgaandonatie', 'value' => 'nee', 'label' => 'Nee, ik wil geen donor zijn', 'model' => $data['orgaandonatie']])
        @include('partials.radio', ['name' => 'orgaandonatie', 'value' => 'register', 'label' => 'Zie het Donorregister', 'model' => $data['orgaandonatie']])
    </div>
</div>

<div class="field">
    <label class="label" for="uitvaart">Wensen voor mijn uitvaart</label>
    <textarea id="uitvaart" wire:model="data.uitvaart" placeholder="Ik kies voor begraven / cremeren. Muziek, ceremonie &hellip;"></textarea>
</div>

<div class="field">
    <label class="label" for="overig">Overig</label>
    <textarea id="overig" wire:model="data.overig" placeholder="Alles wat verder van belang is voor mijn naasten of behandelaars &hellip;"></textarea>
</div>

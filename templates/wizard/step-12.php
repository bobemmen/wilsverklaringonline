<div class="info-block">
    Tot slot een aantal keuzes voor na uw overlijden. Niet verplicht, wel fijn om er iets over te zeggen zodat uw naasten weten wat u had gewild.
</div>

<div class="field">
    <label class="label">Orgaandonatie</label>
    <div class="radio-group">
        <?= $this->radio('orgaandonatie', 'ja', 'Ja, ik stel mijn organen beschikbaar', null, $data['orgaandonatie']) ?>
        <?= $this->radio('orgaandonatie', 'nee', 'Nee, ik wil geen donor zijn', null, $data['orgaandonatie']) ?>
        <?= $this->radio('orgaandonatie', 'register', 'Zie het Donorregister', null, $data['orgaandonatie']) ?>
    </div>
</div>

<div class="field">
    <label class="label" for="uitvaart">Wensen voor mijn uitvaart</label>
    <?= $this->textarea('uitvaart', $data['uitvaart'], 'Ik kies voor begraven / cremeren. Muziek, ceremonie &hellip;') ?>
</div>

<div class="field">
    <label class="label" for="overig">Overig</label>
    <?= $this->textarea('overig', $data['overig'], 'Alles wat verder van belang is voor mijn naasten of behandelaars &hellip;') ?>
</div>

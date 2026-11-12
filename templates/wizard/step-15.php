<div class="callout"><p>Tot slot een aantal keuzes voor na uw overlijden. Niet verplicht, wel fijn om er iets over te zeggen zodat uw naasten weten wat u had gewild.</p></div>

<fieldset class="field">
    <legend>Orgaandonatie</legend>
    <?= $this->radio('orgaandonatie', 'ja', 'Ja, ik stel mijn organen beschikbaar', null, $data['orgaandonatie']) ?>
    <?= $this->radio('orgaandonatie', 'nee', 'Nee, ik wil geen donor zijn', null, $data['orgaandonatie']) ?>
    <?= $this->radio('orgaandonatie', 'register', 'Zie het Donorregister', null, $data['orgaandonatie']) ?>
</fieldset>

<div class="field">
    <label for="uitvaart">Wensen voor mijn uitvaart</label>
    <?= $this->textarea('uitvaart', $data['uitvaart'], 'Ik kies voor begraven / cremeren. Muziek, ceremonie …') ?>
</div>

<div class="field">
    <label for="overig">Overig</label>
    <?= $this->textarea('overig', $data['overig'], 'Alles wat verder van belang is voor mijn naasten of behandelaars …') ?>
</div>

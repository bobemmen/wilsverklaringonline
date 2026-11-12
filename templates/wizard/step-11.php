<div class="callout"><p>Schrijf een persoonlijke brief aan de arts die later de zorg voor u op zich neemt. Vertel over uw leven, uw waarden en wat u hoopt dat deze arts weet van u als mens. Dit is geen juridisch stuk — uw eigen woorden wegen het zwaarst.</p></div>

<div class="field">
    <label for="euthanasie_brief">Brief aan de arts (uw persoonlijke aanvulling)</label>
    <p class="hint" id="brief-hint">Dit is uw persoonlijke aanvulling op het euthanasieverzoek. Schrijf in uw eigen woorden op wat voor u ondraaglijk lijden is; een eigen tekst weegt zwaarder dan een formulier. Als u zelf niet meer kunt praten, kijkt uw arts hier als eerste naar.</p>
    <?= $this->textarea('euthanasie_brief', $data['euthanasie_brief'], 'Beste dokter, ik wil u iets over mezelf vertellen …', 'tall', 'brief-hint') ?>
</div>

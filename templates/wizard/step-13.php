<div class="info-block">
    Uw arts kennen helpt de behandelende arts. Heeft u dit besproken met uw huisarts? Als dat niet zo is, bespreek het dan zo snel mogelijk &mdash; uw verklaring wint aan waarde als uw arts weet wat er in staat.
</div>

<div class="field">
    <label class="label" for="arts">Naam en praktijk van mijn huisarts</label>
    <?= $this->textInput('arts', $data['arts'], 'Dr. &hellip; &mdash; Huisartsenpraktijk &hellip;') ?>
</div>

<div class="field">
    <label class="label">Heeft u deze verklaring met uw arts besproken?</label>
    <div class="radio-group">
        <?= $this->radio('besproken', 'ja', 'Ja', null, $data['besproken']) ?>
        <?= $this->radio('besproken', 'binnenkort', 'Nog niet &mdash; ik plan een afspraak', null, $data['besproken']) ?>
        <?= $this->radio('besproken', 'nee', 'Nee', null, $data['besproken']) ?>
    </div>
</div>

<div class="field">
    <label class="label" for="datum">Datum van ondertekening</label>
    <?= $this->textInput('datum', $data['datum'], '', 'date') ?>
</div>

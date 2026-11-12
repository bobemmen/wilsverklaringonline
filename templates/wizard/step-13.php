<div class="callout"><p>Uw arts kennen helpt de behandelende arts. Heeft u dit besproken met uw huisarts? Als dat niet zo is, bespreek het dan zo snel mogelijk — uw verklaring wint aan waarde als uw arts weet wat er in staat.</p></div>

<div class="field">
    <label for="arts">Naam en praktijk van mijn huisarts</label>
    <?= $this->textInput('arts', $data['arts'], 'Dr. … — Huisartsenpraktijk …') ?>
</div>

<fieldset class="field">
    <legend>Heeft u deze verklaring met uw arts besproken?</legend>
    <?= $this->radio('besproken', 'ja', 'Ja', null, $data['besproken']) ?>
    <?= $this->radio('besproken', 'binnenkort', 'Nog niet — ik plan een afspraak', null, $data['besproken']) ?>
    <?= $this->radio('besproken', 'nee', 'Nee', null, $data['besproken']) ?>
</fieldset>

<div class="field">
    <label for="datum">Datum van ondertekening</label>
    <?= $this->textInput('datum', $data['datum'], '', 'date') ?>
</div>

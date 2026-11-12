<div class="callout"><p>Uw arts kennen helpt de behandelende arts. Heeft u dit besproken met uw huisarts? Als dat niet zo is, bespreek het dan zo snel mogelijk — uw verklaring wint aan waarde als uw arts weet wat er in staat.</p></div>

<details class="more">
    <summary>Praat erover, en laat het vastleggen</summary>
    <div class="more-body">
<ul>
    <li>Bespreek uw verklaring altijd met uw arts en laat hem opnemen in uw <strong>medisch dossier</strong>.</li>
    <li>Praat er regelmatig over met uw naasten, en laat uw arts weten dat u er nog steeds achter staat, of wat u heeft veranderd.</li>
    <li>Met een datum en uw handtekening is de verklaring rechtsgeldig. U hoeft niet naar de notaris.</li>
    <li>Een <strong>medische volmacht</strong> legt vast wie namens u beslissingen over uw medische behandeling mag nemen als u dat zelf niet meer kunt. Vul hieronder de naam in van iemand die u vertrouwt, en bespreek het met die persoon.</li>
</ul>
    </div>
</details>


<div class="field">
    <label for="volmacht">Wie mag medische beslissingen namens mij nemen? (naam en relatie, niet verplicht)</label>
    <?= $this->textInput('volmacht', $data['volmacht'], 'Bijvoorbeeld: Piet de Vries, mijn zoon') ?>
</div>

<div class="field">
    <label for="volmacht_contact">Telefoonnummer of e-mailadres van deze persoon</label>
    <?= $this->textInput('volmacht_contact', $data['volmacht_contact'], '06 …') ?>
</div>

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

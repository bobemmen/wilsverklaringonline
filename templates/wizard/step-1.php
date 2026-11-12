<div class="info-block">
    Begin met uzelf voor te stellen. Wie bent u? Wat vindt u belangrijk in het leven? Deze woorden vormen de toon van uw verklaring &mdash; schrijf ze in uw eigen taal, niet die van de dokter.
</div>

<div class="field">
    <label class="label" for="intro">Korte introductie</label>
    <?= $this->textarea('intro', $data['intro'], 'Ik ben &hellip; Ik woon in &hellip; Wat voor mij belangrijk is &hellip;') ?>
</div>

<div class="field">
    <label class="label" for="waarde">Wat geeft uw leven waarde</label>
    <?= $this->textarea('waarde', $data['waarde'], 'Ik voel mij mens als ik &hellip; Zolang ik &hellip; kan, heeft mijn leven voor mij waarde.') ?>
</div>

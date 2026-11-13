<div class="callout"><p>Begin met uzelf voor te stellen. Wie bent u? Wat vindt u belangrijk in het leven? Deze woorden vormen de toon van uw verklaring — schrijf ze in uw eigen taal, niet die van de dokter.</p></div>

<details class="more">
    <summary>Waarom een schriftelijke wilsverklaring?</summary>
    <div class="more-body">
<p>Zolang u zelf kunt beslissen, heeft u invloed op uw ziekte en uw sterven. Dat verandert als u niet meer kunt zeggen wat u wilt, bijvoorbeeld door dementie, een coma, een herseninfarct of een ongeluk. Dan spreekt uw verklaring voor u.</p>
<ul>
    <li>Het helpt u om na te denken over wat u wilt.</li>
    <li>Het is een goede aanleiding om met naasten en uw arts te praten.</li>
    <li>Het geeft u een stem als u die zelf niet meer kunt laten horen.</li>
    <li>Het helpt uw arts te begrijpen wat u wilt.</li>
</ul>
<h2>Goed om te weten</h2>
<ul>
    <li>U hoeft niet naar de notaris. Een verklaring met datum en uw handtekening is rechtsgeldig.</li>
    <li>Volgens de wet blijft een ondertekende verklaring geldig, ook als u hem lang geleden schreef.</li>
    <li>Controleer uw verklaring af en toe. Bent u het nog eens? Zet er dan een nieuwe datum en handtekening onder.</li>
</ul>
<h2>Drie onderdelen</h2>
<p>Een compleet pakket bestaat meestal uit een <strong>behandelverbod</strong> (wat u niet wilt), een <strong>medische volmacht</strong> (wie namens u beslist) en een <strong>euthanasieverzoek</strong> (wat u wel wilt als u ondraaglijk lijdt). In deze vragenlijst komen ze alle drie aan bod: het behandelverbod in hoofdstuk 2 en 3, het euthanasieverzoek in hoofdstuk 4 (algemeen) en 5 (dementie) en de volmacht in stap 16.</p>
    </div>
</details>


<h2 class="sub">Uw gegevens</h2>
<p class="muted">Met deze gegevens is uw verklaring eenduidig van u. Voor een euthanasieverzoek zijn ze verplicht.</p>
<div class="field">
    <label for="voornamen">Voornamen (voluit)</label>
    <?= $this->textInput('voornamen', $data['voornamen'], 'Anna Maria') ?>
</div>
<div class="field">
    <label for="achternaam">Achternaam</label>
    <?= $this->textInput('achternaam', $data['achternaam'], 'de Vries') ?>
</div>
<div class="field">
    <label for="geboortenaam">Geboortenaam (als die anders is dan uw achternaam)</label>
    <?= $this->textInput('geboortenaam', $data['geboortenaam'], 'Jansen') ?>
</div>
<div class="field">
    <label for="geboortedatum">Geboortedatum</label>
    <?= $this->textInput('geboortedatum', $data['geboortedatum'], '', 'date') ?>
</div>
<div class="field">
    <label for="geboorteplaats">Geboorteplaats</label>
    <?= $this->textInput('geboorteplaats', $data['geboorteplaats'], 'Utrecht') ?>
</div>

<h2 class="sub">Over u</h2>
<div class="field">
    <label for="intro">Korte introductie</label>
    <?= $this->textarea('intro', $data['intro'], 'Ik ben … Ik woon in … Wat voor mij belangrijk is …') ?>
</div>

<div class="field">
    <label for="waarde">Wat geeft uw leven waarde</label>
    <?= $this->textarea('waarde', $data['waarde'], 'Ik voel mij mens als ik … Zolang ik … kan, heeft mijn leven voor mij waarde.') ?>
</div>

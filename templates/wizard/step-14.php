<div class="callout"><p>Bij dementie is uw omgeving belangrijk. Uw naasten zien als eerste dat het slechter gaat, en uw arts betrekt hen bij de beoordeling. Zij moeten dus weten wat u wilt.</p></div>

<details class="more">
    <summary>Wat kunt u nu doen?</summary>
    <div class="more-body">
        <ul>
            <li>Vraag uw naasten u te waarschuwen als ze denken dat u meer vergeet dan normaal, en als het moment dichtbij komt dat u niet meer kunt beslissen.</li>
            <li>Vraag uw huisarts of die openstaat voor euthanasie bij dementie, en tot welk moment die bereid is euthanasie te verlenen.</li>
            <li>Praat regelmatig met uw naasten en huisarts over uw wens, en werk uw verklaring bij als dat nodig is.</li>
        </ul>
    </div>
</details>

<div class="field">
    <label for="dementie_naasten">Wat moeten mijn naasten weten of doen?</label>
    <p class="hint" id="dementie_naasten-hint">Bijvoorbeeld: wie mij waarschuwt als het slechter gaat, welke tekenen ik belangrijk vind, en wie met mijn huisarts praat.</p>
    <?= $this->textarea('dementie_naasten', $data['dementie_naasten'], 'Mijn naasten weten dat …', '', 'dementie_naasten-hint') ?>
</div>

<fieldset class="field">
    <legend>Staat mijn huisarts open voor euthanasie bij dementie?</legend>
    <?= $this->radio('dementie_huisarts', 'ja', 'Ja, dat heb ik besproken', null, $data['dementie_huisarts']) ?>
    <?= $this->radio('dementie_huisarts', 'nee', 'Nee, mijn huisarts staat daar niet voor open', 'Vraag uw huisarts dan hoe u dit anders kunt regelen, bijvoorbeeld via het Expertisecentrum Euthanasie.', $data['dementie_huisarts']) ?>
    <?= $this->radio('dementie_huisarts', 'onbekend', 'Dat weet ik nog niet', 'Bespreek het zo snel mogelijk met uw huisarts.', $data['dementie_huisarts']) ?>
</fieldset>

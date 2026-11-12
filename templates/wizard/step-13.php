<div class="callout"><p>Voor uw arts is het heel belangrijk te weten <em>wanneer</em> u bij dementie ondraaglijk lijdt en euthanasie wilt. Beschrijf dat hier zo concreet mogelijk, in uw eigen woorden. Dit is uw persoonlijke aanvulling voor het dementie-verzoek.</p></div>

<div class="field">
    <label for="dementie_lijden">In welke situatie wil ik mijn leven niet meer verlengen?</label>
    <p class="hint" id="dementie_lijden-hint">Bijvoorbeeld: als ik mijn naasten niet meer herken, of als ik 24 uur per dag zorg nodig heb. Wat voor u ondraaglijk is, is voor een ander misschien anders.</p>
    <?= $this->textarea('dementie_lijden', $data['dementie_lijden'], 'Ik wil niet verder leven als …', 'tall', 'dementie_lijden-hint') ?>
</div>

<fieldset class="field">
    <legend>Vanaf welk moment wil ik dat dit verzoek geldt?</legend>
    <?= $this->radio('dementie_moment', 'vroeg', 'Zodra ik duidelijk ondraaglijk lijd', 'Ook als ik nog zelf kan beslissen. Dat kan vanaf de diagnose.', $data['dementie_moment']) ?>
    <?= $this->radio('dementie_moment', 'omslag', 'Uiterlijk in de omslagzone', 'Zolang ik mijn wens nog zelf kan bevestigen.', $data['dementie_moment']) ?>
    <?= $this->radio('dementie_moment', 'later', 'Ook als ik gevorderde dementie heb en niet meer kan vragen', 'Dan moet mijn arts op deze verklaring varen. Dat is moeilijk en lukt maar zelden.', $data['dementie_moment']) ?>
    <?= $this->radio('dementie_moment', 'bespreken', 'Dit bespreek ik eerst met mijn huisarts', null, $data['dementie_moment']) ?>
</fieldset>

<div class="callout"><p>Soms kan iemand later zelf niet meer om euthanasie vragen, bijvoorbeeld door dementie of een herseninfarct. U kunt op voorhand aangeven of u ook dan wilt dat uw eerdere wens wordt uitgevoerd.</p></div>

<details class="more">
    <summary>Euthanasie bij dementie: de zes fasen</summary>
    <div class="more-body">
<p>Bij dementie komt er een moment dat u niet meer zelf om euthanasie kunt vragen. Dan mag uw arts uw eerder opgestelde schriftelijke verzoek gebruiken in plaats van uw mondelinge verzoek. Zet het daarom tijdig op papier, zolang u nog beslissingen kunt nemen, en bespreek het zo snel mogelijk met uw arts. Voor uw arts is het heel belangrijk te weten <em>wanneer</em> u ondraaglijk lijdt en euthanasie wilt. Schrijf dat op in uw persoonlijke aanvulling (stap 11).</p>
<p>Dementie verloopt in fasen, bij iedereen anders en onvoorspelbaar. Dit overzicht kunt u gebruiken in gesprekken met uw naasten en arts.</p>
<ol class="fasen">
    <li><strong>Niets aan de hand.</strong> U kunt al met familie en vrienden praten, verklaringen opstellen en uw huisarts vragen of die openstaat voor euthanasie bij dementie.</li>
    <li><strong>Beginnende klachten.</strong> Vraag naasten het u te zeggen als u meer vergeet dan normaal. Heeft u nog geen verklaringen? Stel ze nu op. Vermoedt u dementie? Vraag uw huisarts om een verwijzing naar een specialist.</li>
    <li><strong>De diagnose.</strong> Omschrijf in uw persoonlijke aanvulling wat u ondraaglijk lijden vindt. Praat regelmatig met naasten en met uw huisarts. Vraag naasten u te waarschuwen als het moment dichtbij komt dat u niet meer kunt beslissen. Kunt u duidelijk maken dat u ondraaglijk lijdt, dan kan uw huisarts euthanasie verlenen.</li>
    <li><strong>Omslagzone.</strong> U kunt niet altijd meer zeggen dat en waarom u euthanasie wilt. U kunt nog net een verzoek doen. Wacht u langer, dan loopt u het risico dat u het niet meer zelf kunt vragen.</li>
    <li><strong>Gevorderde dementie.</strong> U kunt niet meer om euthanasie vragen. Uw arts kan alleen handelen op grond van uw schriftelijk verzoek. Ondraaglijk lijden kan blijken uit dat verzoek, uit uw gedrag (pijn, verwarring, benauwdheid, verdriet, angst, agressie, onrust) en uit wat naasten en zorgverleners zien. Is niet zichtbaar dat u lijdt, dan kan de arts geen euthanasie verlenen.</li>
    <li><strong>Vegetatieve toestand.</strong> U kunt zich nauwelijks meer uiten. Euthanasie kan alleen met een schriftelijk verzoek en als uw arts overtuigd is dat u ondraaglijk lijdt. In deze fase kunnen levensverlengende behandelingen, zoals sondevoeding, wel worden gestopt, als u dat vooraf heeft vastgelegd in een behandelverbod.</li>
</ol>
<p>Vragen over dementie en het levenseinde? Het NVVE Steunpunt Levenseinde en Dementie helpt leden en hun naasten: 020 620 06 90.</p>
    </div>
</details>


<fieldset class="field">
    <legend>Bij wilsonbekwaamheid</legend>
    <?= $this->radio('euthanasie_wilsonbekwaam', 'ja', 'Ja, ik wil dat mijn verzoek dan nog steeds geldt', null, $data['euthanasie_wilsonbekwaam']) ?>
    <?= $this->radio('euthanasie_wilsonbekwaam', 'nee', 'Nee, alleen zolang ik nog wilsbekwaam ben', null, $data['euthanasie_wilsonbekwaam']) ?>
    <?= $this->radio('euthanasie_wilsonbekwaam', 'voorwaarden', 'Alleen onder voorwaarden', 'Beschrijf hieronder welke voorwaarden voor u gelden.', $data['euthanasie_wilsonbekwaam']) ?>
</fieldset>

<div class="field">
    <label for="euthanasie_wilsonbekwaam_toelichting">Toelichting</label>
    <p class="hint" id="wb-hint">Beschrijf wanneer voor u de grens bereikt is, bijvoorbeeld als u uw kinderen niet meer herkent of niet meer zelfstandig kunt leven.</p>
    <?= $this->textarea('euthanasie_wilsonbekwaam_toelichting', $data['euthanasie_wilsonbekwaam_toelichting'], 'Als ik dement ben en …', '', 'wb-hint') ?>
</div>

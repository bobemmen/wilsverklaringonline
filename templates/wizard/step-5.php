<div class="callout"><p>Antibiotica bestrijden infecties; sondevoeding brengt vloeistof en voeding binnen wanneer eten en drinken niet meer lukt. Soms verlengen deze behandelingen een leven dat al aan het eindigen is — u bepaalt of u dat wilt.</p></div>

<details class="more">
    <summary>Bewust stoppen met eten en drinken</summary>
    <div class="more-body">
<p>Sommige mensen kiezen ervoor om bewust te stoppen met eten en drinken, uit vrije wil, om sneller te overlijden. Meestal overlijdt u dan binnen twee weken, soms duurt het langer. Het is niet voor iedereen geschikt, en het vraagt een goede voorbereiding.</p>
<ul>
    <li>Maak vooraf duidelijke afspraken met uw arts, andere zorgverleners en naasten: over medicijnen, verzorging (zoals mondverzorging en het voorkomen van doorligwonden) en wie wat doet.</li>
    <li>Liefst is er dag en nacht iemand bij u.</li>
    <li>Als de klachten te erg worden en u nog ongeveer twee weken te leven heeft, kan uw arts palliatieve sedatie overwegen.</li>
</ul>
<p>Wilt u dit als mogelijkheid openhouden? Schrijf dat hieronder bij de toelichting. Meer informatie vindt u bij de NVVE.</p>
    </div>
</details>


<fieldset class="field">
    <legend>Antibiotica bij een ernstige infectie</legend>
    <?= $this->radio('antibiotica', 'ja', 'Ja, altijd toedienen', null, $data['antibiotica']) ?>
    <?= $this->radio('antibiotica', 'nee', 'Nee, als ik al ernstig ziek ben', null, $data['antibiotica']) ?>
    <?= $this->radio('antibiotica', 'afwegen', 'Alleen na afweging met de arts en mijn naasten', null, $data['antibiotica']) ?>
</fieldset>

<fieldset class="field">
    <legend>Sondevoeding of kunstmatige voeding</legend>
    <?= $this->radio('sonde', 'ja', 'Ja, zolang dat zinvol is', null, $data['sonde']) ?>
    <?= $this->radio('sonde', 'nee', 'Nee, ik wil geen sondevoeding', null, $data['sonde']) ?>
    <?= $this->radio('sonde', 'kort', 'Alleen als tijdelijke overbrugging', null, $data['sonde']) ?>
</fieldset>

<div class="field">
    <label for="behandel_toelichting">Toelichting (optioneel)</label>
    <?= $this->textarea('behandel_toelichting', $data['behandel_toelichting'], 'Mijn overwegingen …') ?>
</div>

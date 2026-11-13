<div class="callout"><p>Reanimatie kan levens redden, maar het komt ook met risico's. Geeft uw hart het op, dan wordt met druk op de borstkas en soms met stroomstoten geprobeerd u terug te halen. Wat wilt u?</p></div>

<details class="more">
    <summary>Wat u over reanimatie moet weten</summary>
    <div class="more-body">
<p>Reanimatie is een levensreddende behandeling na een hartstilstand. Het begint op het moment dat u bewusteloos bent, dus u kunt dan niet zeggen wat u wilt. Een hulpverlener is dan verplicht u te reanimeren.</p>
<p>Wilt u niet gereanimeerd worden? Geef dat dan <strong>vooraf</strong> door, vóór een behandeling of ziekenhuisopname.</p>
<h2>De niet-reanimerenpenning</h2>
<p>Voor als u onverwacht iets overkomt, bijvoorbeeld op straat, kunt u een niet-reanimerenpenning dragen: een aluminium plaatje aan een ketting met uw naam, pasfoto, handtekening en geboortedatum. Artsen en professionele zorgverleners mogen u dan niet reanimeren. Vertel ook uw familie, vrienden en buren dat u de penning draagt. De penning bestelt u via <strong>nvve.nl/penning</strong>.</p>
    </div>
</details>


<fieldset class="field">
    <legend>Reanimatie</legend>
    <?= $this->radio('reanimatie', 'ja', 'Ja, ik wil gereanimeerd worden', null, $data['reanimatie']) ?>
    <?= $this->radio('reanimatie', 'nee', 'Nee, ik wil niet gereanimeerd worden', 'Ook niet als de kans op herstel groot is.', $data['reanimatie']) ?>
    <?= $this->radio('reanimatie', 'voorwaarden', 'Alleen onder voorwaarden', 'Omschrijf hieronder wanneer wel en wanneer niet.', $data['reanimatie']) ?>
</fieldset>

<div class="field">
    <label for="reanimatie_toelichting">Toelichting (optioneel)</label>
    <?= $this->textarea('reanimatie_toelichting', $data['reanimatie_toelichting'], 'Ik wil niet gereanimeerd worden omdat …') ?>
</div>

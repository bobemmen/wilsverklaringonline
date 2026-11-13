<div class="callout"><p>Bij ernstig ademhalingsfalen kan kunstmatige beademing uw ademhaling overnemen. Soms gebeurt dat op de intensive care, soms tijdelijk met een kapje. Denk na over wat u wel en niet wilt.</p></div>

<details class="more">
    <summary>Mag ik een behandeling weigeren of stoppen?</summary>
    <div class="more-body">
<p>Ja. Artsen hebben uw toestemming nodig voor een behandeling. U mag nee zeggen en een arts moet naar uw wens handelen. Vraag wel naar de mogelijke gevolgen: het kan zijn dat u aan uw ziekte overlijdt.</p>
<ul>
    <li><strong>Geen enkele behandeling meer:</strong> bijvoorbeeld omdat u in uw laatste levensfase niet meer naar een arts of ziekenhuis wilt. Uw kwaliteit van leven kan dan achteruitgaan.</li>
    <li><strong>Bepaalde behandelingen niet meer:</strong> als een behandeling voor u meer nadelen dan voordelen heeft. Geef dat aan vóór de behandeling start.</li>
    <li>U kunt een behandeling die al is begonnen altijd stoppen.</li>
</ul>
<p>Ook als u geen levensverlengende behandelingen meer wilt, blijft u <strong>palliatieve zorg</strong> krijgen: behandelingen die pijn en andere klachten verminderen, zodat u niet onnodig lijdt.</p>
    </div>
</details>


<fieldset class="field">
    <legend>Kunstmatige beademing</legend>
    <?= $this->radio('beademing', 'ja', 'Ja, ik wil beademd worden', null, $data['beademing']) ?>
    <?= $this->radio('beademing', 'nee', 'Nee, ik wil niet beademd worden', null, $data['beademing']) ?>
    <?= $this->radio('beademing', 'kort', 'Alleen kortdurend, als herstel waarschijnlijk is', null, $data['beademing']) ?>
</fieldset>

<fieldset class="field">
    <legend>Opname op de intensive care</legend>
    <?= $this->radio('ic', 'ja', 'Ja, als het kan helpen', null, $data['ic']) ?>
    <?= $this->radio('ic', 'nee', 'Nee, ik wil niet naar de IC', null, $data['ic']) ?>
    <?= $this->radio('ic', 'twijfel', 'Alleen als de kans op herstel in redelijke kwaliteit groot is', null, $data['ic']) ?>
</fieldset>

<div class="field">
    <label for="beademing_toelichting">Toelichting (optioneel)</label>
    <?= $this->textarea('beademing_toelichting', $data['beademing_toelichting'], 'Waarom kies ik hiervoor …') ?>
</div>

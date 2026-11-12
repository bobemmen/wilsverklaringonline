<div class="info-block">
    Bij ernstig ademhalingsfalen kan kunstmatige beademing uw ademhaling overnemen. Soms gebeurt dat op de intensive care, soms tijdelijk met een kapje. Denk na over wat u wel en niet wilt.
</div>

<div class="field">
    <label class="label">Kunstmatige beademing</label>
    <div class="radio-group">
        <?= $this->radio('beademing', 'ja', 'Ja, ik wil beademd worden', null, $data['beademing']) ?>
        <?= $this->radio('beademing', 'nee', 'Nee, ik wil niet beademd worden', null, $data['beademing']) ?>
        <?= $this->radio('beademing', 'kort', 'Alleen kortdurend, als herstel waarschijnlijk is', null, $data['beademing']) ?>
    </div>
</div>

<div class="field">
    <label class="label">Opname op de intensive care</label>
    <div class="radio-group">
        <?= $this->radio('ic', 'ja', 'Ja, als het kan helpen', null, $data['ic']) ?>
        <?= $this->radio('ic', 'nee', 'Nee, ik wil niet naar de IC', null, $data['ic']) ?>
        <?= $this->radio('ic', 'twijfel', 'Alleen als de kans op herstel in redelijke kwaliteit groot is', null, $data['ic']) ?>
    </div>
</div>

<div class="field">
    <label class="label" for="beademing_toelichting">Toelichting (optioneel)</label>
    <?= $this->textarea('beademing_toelichting', $data['beademing_toelichting'], 'Waarom kies ik hiervoor &hellip;') ?>
</div>

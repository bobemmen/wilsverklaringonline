<div class="info-block">
    Leg uit wat u ondraaglijk zou vinden. Denk daarbij aan fysieke pijn, maar ook aan geestelijke nood. Uw beschrijving helpt de arts te begrijpen wanneer sedatie voor u op zijn plaats is.
</div>

<div class="field">
    <label class="label" for="palliatief_fysiek">Fysiek lijden dat ik niet wil dragen</label>
    <?= $this->textarea('palliatief_fysiek', $data['palliatief_fysiek'], 'Ondraaglijke pijn, benauwdheid, onrust &hellip;') ?>
</div>

<div class="field">
    <label class="label" for="palliatief_geestelijk">Geestelijk lijden dat ik niet wil dragen</label>
    <?= $this->textarea('palliatief_geestelijk', $data['palliatief_geestelijk'], 'Angst, verwardheid, het verlies van &hellip;') ?>
</div>

<div class="field">
    <label class="label">Diepte van de sedatie</label>
    <div class="radio-group">
        <?= $this->radio('palliatief_diepte', 'licht', 'Lichte sedatie &mdash; ik wil nog kunnen reageren', null, $data['palliatief_diepte']) ?>
        <?= $this->radio('palliatief_diepte', 'diep', 'Diepe sedatie &mdash; ik wil vol in slaap zijn', null, $data['palliatief_diepte']) ?>
        <?= $this->radio('palliatief_diepte', 'arts', 'Dit laat ik aan de arts over', null, $data['palliatief_diepte']) ?>
    </div>
</div>

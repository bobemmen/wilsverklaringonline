<div class="info-block">
    Leg uit wat u ondraaglijk zou vinden. Denk daarbij aan fysieke pijn, maar ook aan geestelijke nood. Uw beschrijving helpt de arts te begrijpen wanneer sedatie voor u op zijn plaats is.
</div>

<div class="field">
    <label class="label" for="palliatief_fysiek">Fysiek lijden dat ik niet wil dragen</label>
    <textarea id="palliatief_fysiek" wire:model="data.palliatief_fysiek" placeholder="Ondraaglijke pijn, benauwdheid, onrust &hellip;"></textarea>
</div>

<div class="field">
    <label class="label" for="palliatief_geestelijk">Geestelijk lijden dat ik niet wil dragen</label>
    <textarea id="palliatief_geestelijk" wire:model="data.palliatief_geestelijk" placeholder="Angst, verwardheid, het verlies van &hellip;"></textarea>
</div>

<div class="field">
    <label class="label">Diepte van de sedatie</label>
    <div class="radio-group">
        @include('partials.radio', ['name' => 'palliatief_diepte', 'value' => 'licht', 'label' => 'Lichte sedatie &mdash; ik wil nog kunnen reageren', 'model' => $data['palliatief_diepte']])
        @include('partials.radio', ['name' => 'palliatief_diepte', 'value' => 'diep', 'label' => 'Diepe sedatie &mdash; ik wil vol in slaap zijn', 'model' => $data['palliatief_diepte']])
        @include('partials.radio', ['name' => 'palliatief_diepte', 'value' => 'arts', 'label' => 'Dit laat ik aan de arts over', 'model' => $data['palliatief_diepte']])
    </div>
</div>

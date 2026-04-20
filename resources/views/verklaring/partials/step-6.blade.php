<div class="info-block">
    Bij palliatieve sedatie wordt u &mdash; meestal in de laatste levensfase &mdash; in slaap gebracht om ondraaglijk lijden te verzachten. Dit versnelt het sterven niet, maar haalt de scherpe randen eraf.
</div>

<div class="field">
    <label class="label">Wens palliatieve sedatie</label>
    <div class="radio-group">
        @include('partials.radio', ['name' => 'palliatief_wens', 'value' => 'ja', 'label' => 'Ja, als ik ondraaglijk lijd', 'help' => 'Ook wanneer ik dat zelf niet meer kan aangeven.', 'model' => $data['palliatief_wens']])
        @include('partials.radio', ['name' => 'palliatief_wens', 'value' => 'overleg', 'label' => 'Alleen na overleg met mijn naasten en arts', 'model' => $data['palliatief_wens']])
        @include('partials.radio', ['name' => 'palliatief_wens', 'value' => 'nee', 'label' => 'Nee, ik wil bewust blijven', 'model' => $data['palliatief_wens']])
    </div>
</div>

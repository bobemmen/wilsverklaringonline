<div class="info-block">
    Bij palliatieve sedatie wordt u &mdash; meestal in de laatste levensfase &mdash; in slaap gebracht om ondraaglijk lijden te verzachten. Dit versnelt het sterven niet, maar haalt de scherpe randen eraf.
</div>

<div class="field">
    <label class="label">Wens palliatieve sedatie</label>
    <div class="radio-group">
        <?= $this->radio('palliatief_wens', 'ja', 'Ja, als ik ondraaglijk lijd', 'Ook wanneer ik dat zelf niet meer kan aangeven.', $data['palliatief_wens']) ?>
        <?= $this->radio('palliatief_wens', 'overleg', 'Alleen na overleg met mijn naasten en arts', null, $data['palliatief_wens']) ?>
        <?= $this->radio('palliatief_wens', 'nee', 'Nee, ik wil bewust blijven', null, $data['palliatief_wens']) ?>
    </div>
</div>

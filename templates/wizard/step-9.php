<div class="info-block">
    Een arts mag alleen op uw verzoek ingaan als er sprake is van ondraaglijk en uitzichtloos lijden. Omschrijf zo concreet mogelijk wat voor u ondraaglijk is en in welke situatie u om euthanasie zou vragen.
</div>

<div class="field">
    <label class="label" for="euthanasie_lijden">Wat ik ondraaglijk vind</label>
    <?= $this->textarea('euthanasie_lijden', $data['euthanasie_lijden'], 'Ik lijd ondraaglijk wanneer &hellip;') ?>
</div>

<div class="field">
    <label class="label" for="euthanasie_situatie">Concrete situatie waarin ik om euthanasie zou vragen</label>
    <?= $this->textarea('euthanasie_situatie', $data['euthanasie_situatie'], 'Als ik niet meer &hellip; en geen uitzicht heb op &hellip;') ?>
</div>

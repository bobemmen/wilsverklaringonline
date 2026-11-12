<div class="callout"><p>Een arts mag alleen op uw verzoek ingaan als er sprake is van ondraaglijk en uitzichtloos lijden. Omschrijf zo concreet mogelijk wat voor u ondraaglijk is en in welke situatie u om euthanasie zou vragen.</p></div>

<div class="field">
    <label for="euthanasie_lijden">Wat ik ondraaglijk vind</label>
    <?= $this->textarea('euthanasie_lijden', $data['euthanasie_lijden'], 'Ik lijd ondraaglijk wanneer …') ?>
</div>

<div class="field">
    <label for="euthanasie_situatie">Concrete situatie waarin ik om euthanasie zou vragen</label>
    <?= $this->textarea('euthanasie_situatie', $data['euthanasie_situatie'], 'Als ik niet meer … en geen uitzicht heb op …') ?>
</div>

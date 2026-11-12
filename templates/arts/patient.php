<?php $this->layout('layouts/app', ['title' => $v['patient_name'] . ' · WilsVerklaring']) ?>
<div class="page-head">
    <p class="eyebrow"><a href="/arts">← Alle patiënten</a></p>
    <h1><?= e($v['patient_name']) ?></h1>
    <p class="muted">Patiënt-ID in het HIS: <strong><?= e($v['his_id']) ?></strong></p>
</div>

<?php if ($v['status'] === 'geverifieerd'): ?>
    <div class="alert alert-ok" role="status">
        Identiteit bevestigd op <?= e(fmt_date($v['verified_at'])) ?>. Dit is eenmalig: latere wijzigingen van de patiënt hoeft u niet opnieuw te bevestigen.
    </div>
<?php else: ?>
    <section class="card card-accent" aria-labelledby="b-titel">
        <h2 class="card-title" id="b-titel">Identiteit bevestigen</h2>
        <p>De patiënt heeft een account gekoppeld na controle van geboortedatum, BSN en een sms-code naar het door u opgegeven nummer. Bevestig hieronder dat dit account bij deze patiënt hoort.</p>
        <form method="post" action="/arts/patienten/<?= e($v['id']) ?>/bevestigen" id="verify">
            <?= $this->csrf() ?>
            <?= $this->fieldError('verify') ?>
            <label class="choice">
                <input type="checkbox" name="seen" value="1" required>
                <span><span class="choice-label">Ik heb deze patiënt in levende lijve gezien en gesproken.</span></span>
            </label>
            <label class="choice">
                <input type="checkbox" name="identity" value="1" required>
                <span><span class="choice-label">Ik heb de identiteit gecontroleerd en dit account hoort bij de patiënt met bovenstaand patiënt-ID.</span></span>
            </label>
            <p class="muted">Uw naam, praktijk, de datum en een deel van het patiënt-ID worden bij de verklaring getoond. U bevestigt de identiteit van het account, niet de inhoud van de verklaring.</p>
            <button type="submit" class="btn btn-primary">Bevestigen</button>
        </form>
    </section>
<?php endif ?>

<?php if ($doc): ?>
    <div class="card doc-head">
        <p class="eyebrow">Gepubliceerde wilsverklaring van</p>
        <h2 class="h1like"><?= e($v['patient_name']) ?></h2>
        <p class="muted mb0">Versie van <?= e(fmt_date($version['created_at'])) ?> · alleen ter inzage</p>
    </div>
    <?php $this->include('public/_body', ['doc' => $doc]) ?>
<?php else: ?>
    <section class="card">
        <h2 class="card-title">Nog geen definitieve verklaring</h2>
        <p class="mb0">De patiënt heeft zijn verklaring nog niet gepubliceerd. U kunt de identiteit wel alvast bevestigen.</p>
    </section>
<?php endif ?>

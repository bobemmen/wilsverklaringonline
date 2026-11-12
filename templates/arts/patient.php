<?php $this->layout('layouts/app', ['title' => $v['patient_name'] . ' · WilsVerklaring', 'wide' => false]) ?>
<div class="page-head">
    <p class="eyebrow"><a href="/arts">← Alle patiënten</a></p>
    <h1><?= e($v['patient_name']) ?></h1>
    <p class="muted">Patiënt-ID in het HIS: <strong><?= e($v['his_id']) ?></strong></p>
</div>

<?php if ($v['status'] === 'geverifieerd'): ?>
    <div class="alert alert-ok" role="status">
        Bevestigd op <?= e(fmt_date($v['verified_at'])) ?>.
        <?php if ($version && !$isCurrent): ?>
            <strong>De verklaring is daarna gewijzigd.</strong> Lees de nieuwste versie hieronder en bevestig opnieuw.
        <?php endif ?>
    </div>
<?php endif ?>

<?php if (!$doc): ?>
    <section class="card">
        <h2 class="card-title">Nog geen definitieve verklaring</h2>
        <p class="mb0">De patiënt is gekoppeld, maar heeft zijn verklaring nog niet gepubliceerd. U kunt pas bevestigen als er een definitieve verklaring is.</p>
    </section>
<?php else: ?>
    <div class="card doc-head">
        <p class="eyebrow">Wilsverklaring van</p>
        <h2 class="h1like"><?= e($v['patient_name']) ?></h2>
        <p class="muted mb0">Versie van <?= e(fmt_date($version['created_at'])) ?></p>
    </div>
    <?php $this->include('public/_body', ['doc' => $doc]) ?>

    <?php if (!($v['status'] === 'geverifieerd' && $isCurrent)): ?>
    <section class="card card-accent" aria-labelledby="b-titel">
        <h2 class="card-title" id="b-titel">Bevestigen als huisarts</h2>
        <form method="post" action="/arts/patienten/<?= e($v['id']) ?>/bevestigen" id="verify">
            <?= $this->csrf() ?>
            <input type="hidden" name="version" value="<?= e($version['id']) ?>">
            <?= $this->fieldError('verify') ?>
            <label class="choice">
                <input type="checkbox" name="seen" value="1" required>
                <span><span class="choice-label">Ik heb deze patiënt in levende lijve gezien en gesproken.</span></span>
            </label>
            <label class="choice">
                <input type="checkbox" name="identity" value="1" required>
                <span><span class="choice-label">Ik heb de identiteit gecontroleerd en dit is de patiënt met bovenstaand patiënt-ID.</span></span>
            </label>
            <p class="muted">Uw naam, praktijk, de datum en een deel van het patiënt-ID worden bij deze versie van de verklaring getoond. Wijzigt de patiënt de verklaring later, dan geldt deze bevestiging alleen voor de versie die u nu leest.</p>
            <button type="submit" class="btn btn-primary"><?= $v['status'] === 'geverifieerd' ? 'Opnieuw bevestigen' : 'Bevestigen' ?></button>
        </form>
    </section>
    <?php endif ?>
<?php endif ?>

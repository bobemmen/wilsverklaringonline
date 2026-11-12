<?php $this->layout('layouts/app', ['title' => $v['patient_name'] . ' · WilsVerklaring']) ?>
<div class="page-head">
    <p class="eyebrow"><a href="/arts">← Alle patiënten</a></p>
    <h1><?= e($v['patient_name']) ?></h1>
    <p class="muted">Patiënt-ID in het HIS: <strong><?= e($v['his_id']) ?></strong></p>
</div>

<?php if ($v['status'] === 'geverifieerd'): ?>
    <div class="alert alert-ok" role="status">Geregistreerd en bevestigd op <?= e(fmt_date($v['attested_at'])) ?>.</div>
<?php elseif ($v['status'] === 'ingetrokken'): ?>
    <div class="alert alert-bad" role="status">Deze koppeling is ingetrokken.</div>
<?php else: ?>
    <div class="alert alert-ok" role="status">De patiënt heeft zich nog niet geregistreerd.</div>
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
        <p class="mb0">De patiënt heeft zijn verklaring nog niet gepubliceerd.</p>
    </section>
<?php endif ?>

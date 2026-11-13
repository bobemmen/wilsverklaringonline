<?php $this->layout('layouts/app', ['title' => $v['patient_name'] . ' · WilsVerklaring']) ?>
<div class="page-head">
    <p class="eyebrow"><a href="/arts">← Alle patiënten</a></p>
    <h1><?= e($v['patient_name']) ?></h1>
    <p class="muted">Geboortedatum: <strong><?= e(isset($v['birthdate']) ? fmt_iso_date($v['birthdate']) : 'niet vastgelegd') ?></strong> · Patiënt-ID in het HIS: <strong><?= e($v['his_id']) ?></strong></p>
    <p class="muted">Begeleidend arts: <strong><?= $owner ? e($owner) . ($v['arts_id'] === $me ? ' (u)' : '') : 'geen (staat bij ' . e($practice['name']) . ')' ?></strong>
        · Identiteit bevestigd door: <strong><?= e($v['arts_name']) ?></strong> op <?= e(fmt_date($v['attested_at'])) ?></p>
    <?php if ($v['status'] !== 'ingetrokken' && $v['arts_id'] !== $me): ?>
        <form method="post" action="/arts/patienten/<?= e($v['id']) ?>/overnemen">
            <?= $this->csrf() ?>
            <button type="submit" class="btn"><?= $owner ? 'Deze patiënt van ' . e($owner) . ' overnemen' : 'Deze patiënt aan mij toewijzen' ?></button>
        </form>
    <?php endif ?>
</div>

<?php if ($v['status'] === 'geverifieerd'): ?>
    <div class="alert alert-ok" role="status">Geregistreerd en bevestigd op <?= e(fmt_date($v['attested_at'])) ?>.</div>
<?php elseif ($v['status'] === 'ingetrokken'): ?>
    <div class="alert alert-bad" role="status">Deze koppeling is ingetrokken.</div>
<?php else: ?>
    <div class="alert alert-ok" role="status">De patiënt heeft zich nog niet geregistreerd.</div>
<?php endif ?>

<?php if (!empty($v['history'])): ?>
    <details class="card">
        <summary>Historie van deze patiënt</summary>
        <ul>
        <?php foreach (array_reverse($v['history']) as $h): ?>
            <li><?= e(fmt_date($h['at'])) ?> ·
                <?php if ($h['action'] === 'arts_verwijderd'): ?>
                    <?= e($h['from']) ?> is uit de praktijk verwijderd door <?= e($h['by']) ?>; patiënt staat bij de praktijk.
                <?php else: ?>
                    overgenomen door <?= e($h['to'] ?? '—') ?><?= !empty($h['from']) ? ' (was: ' . e($h['from']) . ')' : ' (stond bij de praktijk)' ?>.
                <?php endif ?>
            </li>
        <?php endforeach ?>
        </ul>
    </details>
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

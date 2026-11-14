<?php $this->layout('layouts/plain', ['title' => 'Wilsverklaring van ' . $owner['name']]) ?>
<div class="card doc-head">
    <p class="eyebrow">Wilsverklaring van</p>
    <h1><?= e(\WV\Content::fullName($doc, $owner['name'])) ?></h1>
    <?php if (($birth = \WV\Content::birthLine($doc)) !== ''): ?><p class="muted"><?= e($birth) ?></p><?php endif ?>
    <p class="muted mb0">Versie <?= (int) $versionNumber ?> · <?= e(fmt_date($version['created_at'])) ?><?php if (!empty($doc['datum'])): ?> · ondertekend op <?= e(fmt_iso_date($doc['datum'])) ?><?php endif ?></p>
<?php $cmp = \WV\Content::completion($doc, true); if ($cmp['done'] < $cmp['total']): ?>
    <p class="mt mb0"><span class="badge badge-warn">Gedeeltelijk ingevuld · <?= (int) $cmp['done'] ?> van <?= (int) $cmp['total'] ?> stappen</span></p>
<?php endif ?>
</div>

<?php $this->include('public/_verified', ['verification' => $verification]) ?>
<?php $this->include('public/_body', ['doc' => $doc]) ?>

<p class="center muted mt">Raadpleeg de meest recente versie altijd via deze link.</p>

<?php $this->layout('layouts/plain', ['title' => 'Wilsverklaring van ' . $owner['name']]) ?>
<div class="card doc-head">
    <p class="eyebrow">Wilsverklaring van</p>
    <h1><?= e($owner['name']) ?></h1>
    <p class="muted mb0">Versie <?= (int) $versionNumber ?> · <?= e(fmt_date($version['created_at'])) ?><?php if (!empty($doc['datum'])): ?> · ondertekend op <?= e(fmt_iso_date($doc['datum'])) ?><?php endif ?></p>
</div>

<?php $this->include('public/_verified', ['verification' => $verification]) ?>
<?php $this->include('public/_body', ['doc' => $doc]) ?>

<p class="center muted mt">Raadpleeg de meest recente versie altijd via deze link.</p>

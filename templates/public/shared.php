<?php $this->layout('layouts/plain', ['title' => 'Wilsverklaring van ' . $owner['name']]) ?>
<div class="card doc-head">
    <p class="eyebrow">Wilsverklaring van</p>
    <h1><?= e(\WV\Content::fullName($doc, $owner['name'])) ?></h1>
    <?php if (($birth = \WV\Content::birthLine($doc)) !== ''): ?><p class="muted"><?= e($birth) ?></p><?php endif ?>
    <p class="muted mb0">Versie van <?= e(fmt_date($version['created_at'])) ?> · gedeeld met <?= e($share['name'] ?: $share['email']) ?> als <?= e($share['role']) ?></p>
</div>

<?php $this->include('public/_verified', ['verification' => $verification]) ?>
<?php $this->include('public/_body', ['doc' => $doc]) ?>

<p class="center muted mt">U ziet altijd de meest recente versie van deze verklaring.</p>

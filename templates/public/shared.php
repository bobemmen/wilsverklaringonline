<?php $this->layout('layouts/plain', ['title' => 'Wilsverklaring – ' . $owner['name']]) ?>
<div class="public-header">
    <div style="font-family: 'EB Garamond', Georgia, serif; font-size: 26px; color: var(--wv-purple-dark);">WilsVerklaring</div>
    <div class="small muted" style="margin-top: 6px;">Gedeeld met <?= e($share['name'] ?: $share['email']) ?> als <?= e($share['role']) ?>.</div>
</div>

<div class="public-wrap">
    <div class="card" style="text-align: center;">
        <div class="card-title" style="font-size: 20px;"><?= e($owner['name']) ?></div>
        <div class="small muted">Versie van <?= e(fmt_date($version['created_at'])) ?></div>
    </div>

    <?php $this->include('public/_body', ['doc' => $doc]) ?>

    <div class="small muted" style="text-align: center; margin-top: 40px;">
        U ziet altijd de meest recente versie van deze verklaring.
    </div>
</div>

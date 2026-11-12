<?php $this->layout('layouts/plain', ['title' => 'Wilsverklaring – ' . $owner['name']]) ?>
<div class="public-header">
    <div style="font-family: 'EB Garamond', Georgia, serif; font-size: 26px; color: var(--wv-purple-dark);">WilsVerklaring</div>
    <div class="small muted" style="margin-top: 6px;">Dit is een officiële wilsverklaring.</div>
</div>

<div class="public-wrap">
    <div class="card" style="text-align: center;">
        <div class="card-title" style="font-size: 20px;"><?= e($owner['name']) ?></div>
        <div class="small muted">Versie <?= (int) $versionNumber ?> &middot; <?= e(fmt_date($version['created_at'])) ?></div>
        <?php if (!empty($doc['datum'])): ?>
            <div class="small muted">Ondertekend op <?= e(fmt_iso_date($doc['datum'])) ?></div>
        <?php endif ?>
    </div>

    <?php $this->include('public/_body', ['doc' => $doc]) ?>

    <div class="small muted" style="text-align: center; margin-top: 40px;">
        Raadpleeg de meest recente versie altijd via deze link.
    </div>
</div>

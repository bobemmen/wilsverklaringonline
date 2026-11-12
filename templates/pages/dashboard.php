<?php $this->layout('layouts/app', ['title' => 'Dashboard · WilsVerklaring']) ?>
<h1>Dashboard</h1>
<p class="muted small" style="margin-bottom: 20px;">Een overzicht van uw verklaring, arts-toegang en naasten.</p>

<div class="card-grid">
    <div class="card">
        <div class="card-title">Status wilsverklaring</div>
        <div class="status-row" style="margin: 10px 0 14px;">
            <?php if ($declaration['is_published']): ?>
                <span class="pill pill-green">Definitief</span>
            <?php else: ?>
                <span class="pill pill-muted">Concept</span>
            <?php endif ?>
            <span class="small muted">v<?= (int) $versionCount ?></span>
        </div>
        <div class="small muted">
            <?php if ($currentVersion): ?>
                Laatste wijziging <?= e(fmt_date($currentVersion['created_at'])) ?>
            <?php else: ?>
                Nog niet gepubliceerd.
            <?php endif ?>
        </div>
        <div style="margin-top: 14px;">
            <a href="/verklaring/wizard" class="btn btn-primary">Openen in wizard</a>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Arts-toegang</div>
        <div class="status-row" style="margin: 10px 0 14px;">
            <?php if ($tokenActive): ?>
                <span class="pill pill-green">Actief</span>
            <?php elseif ($declaration['token_idx']): ?>
                <span class="pill pill-red">Verlopen</span>
            <?php else: ?>
                <span class="pill pill-muted">Geen code</span>
            <?php endif ?>
            <span class="small muted"><?= (int) $viewCount ?> raadpleging<?= $viewCount === 1 ? '' : 'en' ?></span>
        </div>
        <div class="small muted">
            <?php if ($declaration['token_expires_at']): ?>
                Vervalt <?= e(fmt_date($declaration['token_expires_at'])) ?>
            <?php else: ?>
                Geen vervaldatum ingesteld.
            <?php endif ?>
        </div>
        <div style="margin-top: 14px;">
            <a href="/token" class="btn btn-ghost">Beheer toegang</a>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Naasten</div>
        <div class="status-row" style="margin: 10px 0 14px;">
            <span class="pill pill-muted"><?= (int) $activeShares ?> actief</span>
        </div>
        <div class="small muted">Nodig een naaste of gemachtigde uit om uw verklaring in te zien.</div>
        <div style="margin-top: 14px;">
            <a href="/naasten" class="btn btn-ghost">Naasten beheren</a>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 18px;">
    <div class="card-title">Downloaden en bewaren</div>
    <p class="small muted">U kunt uw verklaring als PDF afdrukken en ondertekenen om op papier te bewaren. De online versie blijft altijd de leidende.</p>
    <a href="/verklaring/pdf" class="btn btn-ghost">Download PDF</a>
</div>

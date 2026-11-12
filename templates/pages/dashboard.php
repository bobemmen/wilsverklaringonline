<?php $this->layout('layouts/app', ['title' => 'Dashboard · WilsVerklaring']) ?>
<div class="page-head">
    <h1>Welkom, <?= e(explode(' ', trim($this->user()['name']))[0]) ?></h1>
    <p>Hier ziet u waar u staat met uw verklaring, wie er toegang toe heeft en wat u nog kunt doen.</p>
</div>

<div class="grid">
    <section class="card" aria-labelledby="d-status">
        <h2 class="card-title" id="d-status">Uw verklaring</h2>
        <div class="stat-row">
            <?php if ($declaration['is_published']): ?>
                <span class="badge badge-ok">Definitief</span>
            <?php else: ?>
                <span class="badge badge-muted">Concept</span>
            <?php endif ?>
            <span class="muted">Versie <?= (int) $versionCount ?></span>
        </div>
        <p class="muted">
            <?php if ($currentVersion): ?>
                Laatst gepubliceerd op <?= e(fmt_date($currentVersion['created_at'])) ?>.
            <?php else: ?>
                Nog niet gepubliceerd.
            <?php endif ?>
        </p>
        <div class="card-foot"><a href="/verklaring/wizard" class="btn btn-primary btn-block"><?= $declaration['is_published'] ? 'Verklaring aanpassen' : 'Verder met invullen' ?></a></div>
    </section>

    <section class="card" aria-labelledby="d-arts">
        <h2 class="card-title" id="d-arts">Toegang voor uw arts</h2>
        <div class="stat-row">
            <?php if ($tokenActive): ?>
                <span class="badge badge-ok">Actief</span>
            <?php elseif ($declaration['token_idx']): ?>
                <span class="badge badge-bad">Verlopen</span>
            <?php else: ?>
                <span class="badge badge-muted">Geen code</span>
            <?php endif ?>
            <span class="muted"><?= (int) $viewCount ?> keer bekeken</span>
        </div>
        <p class="muted">
            <?php if ($declaration['token_expires_at']): ?>
                Vervalt op <?= e(fmt_date($declaration['token_expires_at'])) ?>.
            <?php else: ?>
                Geen vervaldatum ingesteld.
            <?php endif ?>
        </p>
        <div class="card-foot"><a href="/token" class="btn btn-block">Toegang beheren</a></div>
    </section>

    <section class="card" aria-labelledby="d-naasten">
        <h2 class="card-title" id="d-naasten">Naasten</h2>
        <div class="stat-row"><span class="badge badge-muted"><?= (int) $activeShares ?> actief</span></div>
        <p class="muted">Nodig een naaste of gemachtigde uit om uw verklaring te lezen.</p>
        <div class="card-foot"><a href="/naasten" class="btn btn-block">Naasten beheren</a></div>
    </section>
</div>

<?php if ($verifications): ?>
<section class="card" aria-labelledby="d-huisarts">
    <h2 class="card-title" id="d-huisarts">Identiteit bevestigd door uw huisarts</h2>
    <?php foreach ($verifications as $v): ?>
        <div class="stat-row">
            <strong><?= e($v['arts_name']) ?><?= !empty($v['practice']) ? ' · ' . e($v['practice']) : '' ?></strong>
            <span class="badge badge-ok">Bevestigd op <?= e(fmt_date($v['attested_at'] ?? $v['verified_at'])) ?></span>
        </div>
        <p class="muted">Alle artsen van <?= !empty($v['practice']) ? e($v['practice']) : 'deze praktijk' ?> kunnen uw gepubliceerde verklaring inzien. Elke inzage ziet u terug onder “Arts-toegang”.</p>
        <form method="post" action="/verificatie/<?= e($v['id']) ?>/intrekken" class="inline-form">
            <?= $this->csrf() ?>
            <button type="submit" class="btn btn-small btn-danger" data-confirm="Toegang van uw huisarts intrekken?">Toegang van huisarts intrekken</button>
        </form>
    <?php endforeach ?>
</section>
<?php endif ?>

<?php $this->include('partials/hulp') ?>

<section class="card" aria-labelledby="d-pdf">
    <h2 class="card-title" id="d-pdf">Op papier bewaren</h2>
    <p>Print uw verklaring en onderteken hem om op papier te bewaren. De online versie blijft altijd de leidende.</p>
    <div class="btn-row">
        <a href="/verklaring/pdf" class="btn">Download als PDF</a>
        <a href="/verklaring/pdf?groot=1" class="btn">Download als PDF (grote letters)</a>
    </div>
</section>

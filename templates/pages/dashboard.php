<?php
$this->layout('layouts/app', ['title' => 'Dashboard · WilsVerklaring', 'wide' => true]);
$names = \WV\Content::CHAPTER_NAMES;
$optional = [4 => true, 5 => true];
$current = null;
foreach ($progress as $n => $p) {
    if (!in_array($p['status'], ['klaar', 'overgeslagen'], true)) { $current = $n; break; }
}
$statusText = [
    'klaar' => 'Ingevuld',
    'overgeslagen' => 'Overgeslagen',
    'bezig' => 'Hier was u gebleven',
    'nieuw' => 'Nog niet begonnen',
];
?>
<div class="page-head">
    <h1>Welkom, <?= e(explode(' ', trim($this->user()['name']))[0]) ?></h1>
    <p>Hier ziet u waar u staat met uw verklaring, wie er toegang toe heeft en wat u nog kunt doen. Neem gerust de tijd.</p>
</div>

<div class="dash">
    <div class="dash-main">
        <section class="card" aria-labelledby="d-status">
            <h2 class="card-title" id="d-status">Uw verklaring</h2>
            <div class="stat-row">
                <?php if ($declaration['is_published']): ?>
                    <span class="badge badge-ok">Definitief</span>
                <?php else: ?>
                    <span class="badge badge-muted">Concept</span>
                <?php endif ?>
                <?php if ($hasDraft && $declaration['is_published']): ?>
                    <span class="badge badge-muted">Wijzigingen nog niet gepubliceerd</span>
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

            <h3 class="mt">Uw pad</h3>
            <ol class="pad">
                <?php foreach ($names as $n => $name):
                    $p = $progress[$n];
                    $isCurrent = $n === $current;
                    $cls = $isCurrent ? 'current' : ($p['status'] === 'klaar' ? 'done' : '');
                    $first = \WV\Content::firstStepOfChapter($n);
                    $goHref = $p['status'] === 'nieuw' ? '/verklaring/wizard?stap=' . $first . '&uitleg=1' : '/verklaring/wizard?stap=' . $p['next'];
                ?>
                    <li class="<?= $cls ?>"<?= $isCurrent ? ' aria-current="step"' : '' ?>>
                        <span class="pad-dot" aria-hidden="true"><?= $p['status'] === 'klaar' ? '✓' : $n ?></span>
                        <div class="pad-body">
                            <div>
                                <strong><?= e($name) ?></strong>
                                <span class="muted"><?= $isCurrent && $p['status'] === 'nieuw' ? 'Hier gaat u verder' : $statusText[$p['status']] ?><?= isset($optional[$n]) ? ' · niet verplicht' : '' ?></span>
                            </div>
                            <?php if ($isCurrent): ?>
                                <a class="btn btn-primary" href="<?= e($goHref) ?>"><?= $p['status'] === 'nieuw' ? 'Beginnen' : 'Verder' ?><span class="sr-only"> met <?= e($name) ?></span></a>
                            <?php elseif ($p['status'] !== 'nieuw'): ?>
                                <a href="/verklaring/wizard?stap=<?= (int) $first ?>">Bekijken of aanpassen<span class="sr-only">: <?= e($name) ?></span></a>
                            <?php endif ?>
                        </div>
                    </li>
                <?php endforeach ?>
            </ol>

            <div class="card-foot btn-row">
                <?php if ($current === null): ?>
                    <a href="/verklaring/wizard?stap=<?= \WV\Content::TOTAL_STEPS ?>" class="btn btn-primary"><?= $declaration['is_published'] && !$hasDraft ? 'Verklaring aanpassen' : 'Nakijken en publiceren' ?></a>
                <?php endif ?>
                <?php if ($declaration['is_published']): ?>
                    <a href="/verklaring/pdf" class="btn">Download als PDF</a>
                    <a href="/verklaring/pdf?groot=1" class="btn">PDF met grote letters</a>
                <?php endif ?>
            </div>
        </section>
    </div>

    <div class="dash-side">
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
            <a href="/token" class="btn btn-small">Toegang beheren</a>
        </section>

        <section class="card" aria-labelledby="d-naasten">
            <h2 class="card-title" id="d-naasten">Naasten</h2>
            <div class="stat-row"><span class="badge badge-muted"><?= (int) $activeShares ?> actief</span></div>
            <p class="muted">Nodig een naaste of gemachtigde uit om uw verklaring te lezen.</p>
            <a href="/naasten" class="btn btn-small">Naasten beheren</a>
        </section>

        <?php if ($verifications): ?>
        <section class="card verified-ok" aria-labelledby="d-huisarts">
            <h2 class="card-title" id="d-huisarts">Identiteit bevestigd door uw huisarts</h2>
            <?php foreach ($verifications as $v): ?>
                <p class="mb0"><strong><?= e($v['arts_name']) ?><?= !empty($v['practice']) ? ' · ' . e($v['practice']) : '' ?></strong></p>
                <div class="stat-row"><span class="badge badge-ok">Bevestigd op <?= e(fmt_date($v['attested_at'] ?? $v['verified_at'])) ?></span></div>
                <p class="muted small">Alle artsen van <?= !empty($v['practice']) ? e($v['practice']) : 'deze praktijk' ?> kunnen uw gepubliceerde verklaring inzien. Elke inzage ziet u terug onder “Arts-toegang”.</p>
                <form method="post" action="/verificatie/<?= e($v['id']) ?>/intrekken" class="inline-form">
                    <?= $this->csrf() ?>
                    <button type="submit" class="btn btn-small btn-danger" data-confirm="Toegang van uw huisarts intrekken?">Toegang van huisarts intrekken</button>
                </form>
            <?php endforeach ?>
        </section>
        <?php endif ?>

        <section class="sun" aria-labelledby="d-praten">
            <h2 id="d-praten">Even praten?</h2>
            <p>Bespreek uw keuzes met uw huisarts voordat u ze definitief maakt. Zo weet uw huisarts wat u bedoelt en waarom.</p>
        </section>
    </div>
</div>

<?php $this->include('partials/hulp') ?>

<?php if (!$declaration['is_published']): ?>
<section class="card" aria-labelledby="d-pdf">
    <h2 class="card-title" id="d-pdf">Op papier bewaren</h2>
    <p>Na het publiceren kunt u uw verklaring als PDF downloaden, printen en ondertekenen. De online versie blijft altijd de leidende.</p>
</section>
<?php endif ?>

<?php
$this->layout('layouts/app', ['title' => 'Dashboard · WilsVerklaring', 'wide' => true]);
$names = \WV\Content::CHAPTER_NAMES;
$optional = [4 => true, 5 => true];
$current = null;
foreach ($progress as $n => $p) {
    if (!in_array($p['status'], ['klaar', 'overgeslagen'], true)) { $current = $n; break; }
}
$statusText = [
    'klaar' => 'Compleet',
    'overgeslagen' => 'Overgeslagen',
    'bezig' => 'Begonnen',
    'nieuw' => 'Nog niet begonnen',
];
// Ring met één streepje per stap (gekleurd = ingevuld). Kleuren via CSS-klassen.
$ring = function (array $p): string {
    $steps = array_values($p['steps']);
    $n = count($steps);
    $svg = '<svg class="pad-ring" width="40" height="40" viewBox="0 0 42 42" aria-hidden="true" focusable="false">';
    if ($p['status'] === 'overgeslagen') {
        return $svg . '<circle class="seg seg-skip" cx="21" cy="21" r="18" fill="none"/></svg>';
    }
    if ($n === 1) {
        return $svg . '<circle class="seg ' . ($steps[0] === 'klaar' ? 'seg-done' : 'seg-open') . '" cx="21" cy="21" r="18" fill="none"/></svg>';
    }
    $gap = 14.0;
    $seg = 360.0 / $n;
    $pt = fn(float $deg): array => [21 + 18 * cos(deg2rad($deg - 90)), 21 + 18 * sin(deg2rad($deg - 90))];
    foreach ($steps as $k => $st) {
        $a0 = $k * $seg + $gap / 2;
        $a1 = ($k + 1) * $seg - $gap / 2;
        [$x0, $y0] = $pt($a0);
        [$x1, $y1] = $pt($a1);
        $svg .= sprintf('<path class="seg %s" d="M%.2F %.2F A18 18 0 %d 1 %.2F %.2F" fill="none"/>', $st === 'klaar' ? 'seg-done' : 'seg-open', $x0, $y0, ($a1 - $a0) > 180 ? 1 : 0, $x1, $y1);
    }
    return $svg . '</svg>';
};
$titles = \WV\Content::TITLES;
// Alleen nog publiceren over? Dan staat de knop "Nakijken en publiceren" onderaan.
$onlyPublishLeft = true;
foreach ($progress as $pp) {
    if (!in_array($pp['status'], ['klaar', 'overgeslagen'], true) && $pp['open'] !== [\WV\Content::TOTAL_STEPS]) {
        $onlyPublishLeft = false;
    }
}
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
                <?php if (!$declaration['is_published']): ?>
                    <span class="badge badge-muted">Concept</span>
                <?php elseif ($hasDraft): ?>
                    <span class="badge badge-warn">Wijzigingen nog niet gepubliceerd</span>
                <?php else: ?>
                    <span class="badge badge-ok">Gepubliceerd</span>
                <?php endif ?>
                <?php if ($declaration['is_published']): ?><span class="muted">Versie <?= (int) $versionCount ?></span><?php endif ?>
            </div>
            <?php if (!$declaration['is_published']): ?>
                <p class="muted">Nog niet gepubliceerd. Uw arts ziet uw verklaring pas als u hem publiceert (laatste stap).</p>
            <?php elseif ($hasDraft): ?>
                <p class="muted">Uw arts ziet nog de gepubliceerde versie van <?= e(fmt_date($currentVersion['created_at'])) ?>. Uw wijzigingen gelden pas als u opnieuw publiceert.</p>
                <p><a href="/verklaring/wizard?stap=17" class="btn btn-primary">Verder naar publiceren</a></p>
            <?php else: ?>
                <p class="muted">Laatst gepubliceerd op <?= e(fmt_date($currentVersion['created_at'])) ?>. U kunt alles altijd aanpassen. Een aanpassing geldt pas als u opnieuw publiceert.</p>
            <?php endif ?>

            <h3 class="mt">Uw pad</h3>
            <div class="wz-progress">
                <p class="wz-progress-text"><strong><?= (int) $completion['done'] ?> van <?= (int) $completion['total'] ?> stappen compleet</strong> <span class="muted">(<?= (int) $completion['percent'] ?>%)</span></p>
                <progress max="<?= max(1, (int) $completion['total']) ?>" value="<?= (int) $completion['done'] ?>" aria-label="Voortgang vragenlijst"><?= (int) $completion['percent'] ?>%</progress>
            </div>
            <ol class="pad">
                <?php foreach ($names as $n => $name):
                    $p = $progress[$n];
                    $isCurrent = $n === $current;
                    $cls = trim(($isCurrent ? 'current ' : '') . ($p['status'] === 'klaar' ? 'done' : ($p['status'] === 'bezig' ? 'part' : '')));
                    $partText = null;
                    $first = \WV\Content::firstStepOfChapter($n);
                    $goHref = $p['status'] === 'nieuw' ? '/verklaring/wizard?stap=' . $first . '&uitleg=1' : '/verklaring/wizard?stap=' . $p['next'];
                    // Het rondje is zelf ook een link. Heeft het onderwerp al een tekstlink of knop, dan is het rondje alleen een extra klikvlak.
                    $hasTextLink = $isCurrent || $p['status'] !== 'nieuw';
                    $dotHref = $isCurrent ? $goHref : ($p['status'] === 'nieuw' ? $goHref : '/verklaring/wizard?stap=' . $first);
                ?>
                    <li class="<?= $cls ?>"<?= $isCurrent ? ' aria-current="step"' : '' ?>>
                        <a class="pad-dot" href="<?= e($dotHref) ?>"<?= $hasTextLink ? ' tabindex="-1" aria-hidden="true"' : ' aria-label="' . e($name . ': ' . $statusText[$p['status']]) . '"' ?>><?= $p['status'] === 'klaar' ? '✓' : $ring($p) . '<span class="pad-num">' . $n . '</span>' ?></a>
                        <div class="pad-body">
                            <div>
                                <strong><?= e($name) ?></strong>
                                <span class="muted"><?= $isCurrent && $p['status'] === 'nieuw' ? 'Hier gaat u verder' : ($partText ?? $statusText[$p['status']]) ?><?= isset($optional[$n]) ? ' · niet verplicht' : '' ?></span>
                            </div>
                            <?php if ($isCurrent): ?>
                                <a class="btn btn-primary" href="<?= e($goHref) ?>"><?= $p['status'] === 'nieuw' ? 'Beginnen' : 'Verder' ?><span class="sr-only"> met <?= e($name) ?></span></a>
                            <?php elseif ($p['status'] === 'bezig'): ?>
                                <a href="/verklaring/wizard?stap=<?= (int) $p['next'] ?>">Verder met stap <?= (int) $p['next'] ?>: <?= e($titles[$p['next']] ?? '') ?><span class="sr-only"> (<?= e($name) ?>)</span></a>
                            <?php elseif ($p['status'] !== 'nieuw'): ?>
                                <a href="/verklaring/wizard?stap=<?= (int) $first ?>">Bekijken of aanpassen<span class="sr-only">: <?= e($name) ?></span></a>
                            <?php endif ?>
                        </div>
                    </li>
                <?php endforeach ?>
            </ol>

            <div class="card-foot btn-row">
                <?php if ($current === null || $onlyPublishLeft): ?>
                    <a href="/verklaring/wizard?stap=<?= \WV\Content::TOTAL_STEPS ?>" class="btn btn-primary"><?= $declaration['is_published'] && !$hasDraft ? 'Verklaring aanpassen' : 'Nakijken en publiceren' ?></a>
                <?php endif ?>
                <?php if ($declaration['is_published'] && !$hasDraft): ?>
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

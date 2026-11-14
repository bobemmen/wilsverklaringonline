<?php
/** @var bool $nav */
$p = $this->prefs();
$user = $this->user();
$items = [
    ['/dashboard', 'Dashboard'],
    ['/verklaring/wizard', 'Mijn verklaring'],
    ['/verklaring/versies', 'Versies'],
    ['/token', 'Arts-toegang'],
    ['/naasten', 'Naasten'],
    ['/account', 'Account'],
];
if ($user && ($user['role'] ?? 'patient') === 'arts') {
    $items = [
        ['/arts', 'Patiënten'],
        ['/arts/team', 'Team'],
        ['/2fa/beheer', 'Beveiliging'],
    ];
}
?>
<header class="site-header">
    <?php if ($this->isDemo()): ?>
        <div class="demo-bar" role="region" aria-label="Demo">
            <p><strong>Dit is een demo.</strong> Vul geen echte gegevens in. Alles wordt automatisch verwijderd.</p>
            <form method="post" action="/demo/stoppen" class="inline-form">
                <?= $this->csrf() ?>
                <button type="submit" class="btn btn-soft">Demo afsluiten</button>
            </form>
        </div>
    <?php endif ?>
    <div class="bar">
        <a class="brand" href="<?= $user ? '/dashboard' : '/' ?>">
            <span class="brand-logo" aria-hidden="true"><span class="brand-mark"></span></span>
            <span>WilsVerklaring</span>
        </a>
        <div class="tools" role="group" aria-label="Weergave en voorlezen">
            <?php if ($p->size > \WV\Prefs::MIN_SIZE): ?>
                <a class="tool tool-a" href="<?= e($this->prefLink(['t' => $p->size - 1])) ?>" data-size="-1" title="Tekst kleiner"><span aria-hidden="true">A−</span><span class="sr-only">Tekst kleiner</span></a>
            <?php else: ?>
                <span class="tool tool-a" aria-disabled="true" role="img" aria-label="Tekst kleiner (niet beschikbaar, dit is al de kleinste maat)">A−</span>
            <?php endif ?>
            <?php if ($p->size < \WV\Prefs::MAX_SIZE): ?>
                <a class="tool tool-a" href="<?= e($this->prefLink(['t' => $p->size + 1])) ?>" data-size="1" title="Tekst groter"><span aria-hidden="true">A+</span><span class="sr-only">Tekst groter</span></a>
            <?php else: ?>
                <span class="tool tool-a" aria-disabled="true" role="img" aria-label="Tekst groter (niet beschikbaar, dit is al de grootste maat)">A+</span>
            <?php endif ?>
            <a class="tool" href="<?= e($this->prefLink([])) ?>" data-weergave>Weergave</a>
            <button type="button" class="tool" data-speak hidden>Lees voor</button>
        </div>
    </div>
    <?php if (!empty($nav) && $user): ?>
        <nav class="mainnav" aria-label="Hoofdmenu">
            <ul>
                <?php foreach ($items as [$href, $label]): ?>
                    <li><a href="<?= e($href) ?>"<?= $this->active($href) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                <?php endforeach ?>
                <li class="push">
                    <form method="post" action="/uitloggen" class="inline-form">
                        <?= $this->csrf() ?>
                        <button type="submit" class="navbtn">Uitloggen</button>
                    </form>
                </li>
            </ul>
        </nav>
    <?php endif ?>
</header>

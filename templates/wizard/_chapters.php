<?php
/**
 * Voortgang en navigatie door de vragenlijst.
 * @var int   $chapter  huidig onderwerp
 * @var int   $step     huidige stap (0 = uitlegscherm)
 * @var array $data     werkversie van de verklaring
 * @var bool  $published true: gepubliceerd en geen openstaande wijzigingen (stap 17 telt dan als klaar)
 * @var bool  $form     true: knoppen die eerst het concept opslaan (binnen het stappenformulier); false: gewone links
 */
$statuses = \WV\Content::stepStatuses($data, $published);
$done = \WV\Content::completion($data, $published);
$labels = ['klaar' => 'compleet', 'nieuw' => 'nog te doen', 'overgeslagen' => 'overgeslagen', 'overzicht' => 'overzicht en publicatie'];
$go = function (int $n, string $class, string $text, string $label, bool $current) use ($form): string {
    $aria = $current ? ' aria-current="step"' : '';
    if ($form) {
        return '<button type="submit" form="wizard-form" formnovalidate name="ga" value="' . $n . '" class="' . $class . '" title="' . e($label) . '" aria-label="' . e($label) . '"' . $aria . '>' . $text . '</button>';
    }
    return '<a href="/verklaring/wizard?stap=' . $n . '" class="' . $class . '" title="' . e($label) . '" aria-label="' . e($label) . '"' . $aria . '>' . $text . '</a>';
};
?>
<nav class="wz-nav" aria-label="Voortgang en navigatie door de vragenlijst">
    <div class="wz-summary">
        <p class="wz-where"><?php if ($step > 0): ?><strong>Stap <?= (int) $step ?> van <?= count(\WV\Content::TITLES) ?></strong> <span class="muted">· <?= e(\WV\Content::CHAPTER_NAMES[$chapter] ?? '') ?></span><?php else: ?><strong>Uitleg</strong> <span class="muted">· <?= e(\WV\Content::CHAPTER_NAMES[$chapter] ?? '') ?></span><?php endif ?></p>
        <p class="wz-count"><strong><?= (int) $done['done'] ?> van <?= (int) $done['total'] ?> stappen compleet</strong> <span class="muted">(<?= (int) $done['percent'] ?>%)</span></p>
    </div>
    <progress max="<?= max(1, (int) $done['total']) ?>" value="<?= (int) $done['done'] ?>" aria-label="Voortgang vragenlijst"><?= (int) $done['percent'] ?>%</progress>
    <details class="wz-all">
        <summary><span class="wz-open">Alle stappen tonen</span><span class="wz-close">Alle stappen verbergen</span></summary>
    <ol class="wz-chapters">
        <?php foreach (\WV\Content::CHAPTER_NAMES as $n => $name):
            $steps = \WV\Content::chapterSteps($n);
            $stat = array_map(fn($s) => $statuses[$s], $steps);
            $allDone = $stat && !array_diff($stat, ['klaar', 'overgeslagen', 'overzicht']) && in_array('klaar', $stat, true);
            $cls = 'wz-chapter' . ($n === $chapter ? ' current' : '') . ($allDone ? ' done' : '') . (in_array('overgeslagen', $stat, true) ? ' skipped' : '');
        ?>
            <li class="<?= $cls ?>">
                <span class="wz-chapter-name"><?= e($name) ?></span>
                <ol class="wz-steps">
                    <?php foreach ($steps as $s):
                        $st = $statuses[$s];
                        $isCur = $s === $step;
                        $label = 'Stap ' . $s . ': ' . \WV\Content::TITLES[$s] . ', ' . ($s === \WV\Content::TOTAL_STEPS ? ($st === 'klaar' ? 'gepubliceerd' : 'nog te doen') : $labels[$st]) . ($isCur ? ' (u bent hier)' : '');
                        $c = 'wz-step ' . $st . ($isCur ? ' is-current' : '');
                    ?>
                        <li><?= $go($s, $c, ($st === 'klaar' && !$isCur ? '<span aria-hidden="true">✓</span>' : (string) $s) , $label, $isCur) ?></li>
                    <?php endforeach ?>
                </ol>
            </li>
        <?php endforeach ?>
    </ol>
    </details>
</nav>

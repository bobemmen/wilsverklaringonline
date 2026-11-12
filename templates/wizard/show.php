<?php
$this->layout('layouts/app', ['title' => $stepTitle . ' · WilsVerklaring']);
$chapterNames = [1 => 'Introductie', 2 => 'Behandelverbod', 3 => 'Palliatieve sedatie', 4 => 'Euthanasieverzoek', 5 => 'Afsluiting'];
?>
<p class="eyebrow">Stap <?= (int) $step ?> van <?= (int) $total ?></p>
<h1 class="wizard-title"><?= e($stepTitle) ?></h1>

<nav aria-label="Voortgang per hoofdstuk">
    <ol class="chapters">
        <?php foreach ($chapterNames as $n => $name): ?>
            <li class="<?= $n < $chapter ? 'done' : ($n === $chapter ? 'current' : '') ?>"<?= $n === $chapter ? ' aria-current="step"' : '' ?>>
                <span class="sr-only"><?= $n < $chapter ? 'Afgerond: ' : ($n === $chapter ? 'Nu bezig: ' : 'Nog te doen: ') ?></span>
                <?= $n ?>. <?= e($name) ?>
            </li>
        <?php endforeach ?>
    </ol>
</nav>

<div class="card">
    <form method="post" action="/verklaring/wizard">
        <?= $this->csrf() ?>
        <input type="hidden" name="stap" value="<?= (int) $step ?>">
        <?php /* Enter in een tekstveld slaat het concept op (eerste knop in de DOM-volgorde) */ ?>
        <button type="submit" name="actie" value="concept" class="sr-only" tabindex="-1" aria-hidden="true">Opslaan als concept</button>

        <?php $this->include('wizard/step-' . $step, ['data' => $data]) ?>

        <div class="actions">
            <div>
                <?php if ($step > 1): ?>
                    <button type="submit" name="actie" value="terug" class="btn">← Terug</button>
                <?php endif ?>
            </div>
            <div class="actions-right">
                <?php if ($step === 8): ?>
                    <button type="submit" name="actie" value="overslaan" class="btn">Ik sla dit over</button>
                <?php endif ?>
                <button type="submit" name="actie" value="concept" class="btn">Opslaan als concept</button>
                <?php if ($step < $total): ?>
                    <button type="submit" name="actie" value="volgende" class="btn btn-primary">Volgende →</button>
                <?php else: ?>
                    <button type="submit" name="actie" value="publiceer" class="btn btn-primary" data-confirm="Weet u zeker dat u uw wilsverklaring wilt publiceren? Eerdere versies blijven bewaard.">Publiceer verklaring</button>
                <?php endif ?>
            </div>
        </div>
    </form>
</div>

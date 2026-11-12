<?php $this->layout('layouts/app', ['title' => 'Wizard · WilsVerklaring']) ?>
<div>
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
        <div>
            <div class="small muted" style="letter-spacing: 0.12em; text-transform: uppercase;">Hoofdstuk <?= (int) $chapter ?> van 5 &middot; <?= e($chapterTitle) ?></div>
            <h1 style="margin-top: 4px;"><?= e($stepTitle) ?></h1>
        </div>
        <div class="small muted">Stap <?= (int) $step ?> van <?= (int) $total ?></div>
    </div>

    <div class="progress-bar">
        <?php for ($c = 1; $c <= 5; $c++): ?>
            <div class="progress-seg<?= $c < $chapter ? ' done' : '' ?><?= $c === $chapter ? ' active' : '' ?>"></div>
        <?php endfor ?>
    </div>

    <div class="card">
        <form method="post" action="/verklaring/wizard">
            <?= $this->csrf() ?>
            <input type="hidden" name="stap" value="<?= (int) $step ?>">
            <?php /* Enter in een tekstveld slaat het concept op (eerste knop in de DOM-volgorde) */ ?>
            <button type="submit" name="actie" value="concept" class="sr-only" tabindex="-1" aria-hidden="true">Opslaan als concept</button>

            <?php $this->include('wizard/step-' . $step, ['data' => $data]) ?>

            <div class="action-bar">
                <div class="left">
                    <?php if ($step > 1): ?>
                        <button type="submit" name="actie" value="terug" class="btn btn-ghost">Terug</button>
                    <?php endif ?>
                </div>
                <div class="right">
                    <?php if ($step === 8): ?>
                        <button type="submit" name="actie" value="overslaan" class="btn btn-ghost">Ik sla dit over</button>
                    <?php endif ?>
                    <button type="submit" name="actie" value="concept" class="btn btn-ghost">Opslaan als concept</button>
                    <?php if ($step < $total): ?>
                        <button type="submit" name="actie" value="volgende" class="btn btn-primary">Volgende</button>
                    <?php else: ?>
                        <button type="submit" name="actie" value="publiceer" class="btn btn-primary" data-confirm="Weet u zeker dat u uw wilsverklaring wilt publiceren? Eerdere versies blijven bewaard.">Publiceer verklaring</button>
                    <?php endif ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
$this->layout('layouts/app', ['title' => $stepTitle . ' · WilsVerklaring']);
$chapterNames = \WV\Content::CHAPTER_NAMES;
?>
<form method="post" action="/verklaring/wizard" id="wizard-form">
    <?= $this->csrf() ?>
    <input type="hidden" name="stap" value="<?= (int) $step ?>">
    <?php /* Enter in een tekstveld slaat het concept op (eerste verzendknop in de DOM-volgorde) */ ?>
    <button type="submit" name="actie" value="concept" class="sr-only" tabindex="-1" aria-hidden="true">Opslaan als concept</button>

    <?php if (!empty($isPublished)): ?>
        <div class="callout" role="note">
            <?php if ($published): ?>
                <p><strong>Uw verklaring is gepubliceerd.</strong> U kunt alles aanpassen. Een aanpassing geldt pas als u opnieuw publiceert (laatste stap). Tot dan ziet uw arts de gepubliceerde versie.</p>
            <?php else: ?>
                <p><strong>U past een gepubliceerde verklaring aan.</strong> Uw arts ziet nog de gepubliceerde versie. Uw wijzigingen gelden pas als u opnieuw publiceert (stap 17).</p>
            <?php endif ?>
        </div>
    <?php endif ?>

    <?php $this->include('wizard/_chapters', ['chapter' => $chapter, 'step' => $step, 'data' => $data, 'published' => $published, 'form' => true]) ?>

    <div class="card conversation">
        <p class="eyebrow">Onderwerp <?= (int) $chapter ?> van <?= count($chapterNames) ?> · Stap <?= (int) $step ?> van <?= (int) $total ?></p>
        <h1 class="wizard-title"><?= e($stepTitle) ?></h1>

        <?php
        // Nog niets ingevuld bij deze stap? Dan staat de eerste uitleg ("Wat u moet weten") alvast open.
        ob_start();
        $this->include('wizard/step-' . $step, ['data' => $data]);
        $stepHtml = ob_get_clean();
        if ($infoOpen) {
            $stepHtml = preg_replace('/<details class="more">/', '<details class="more" open>', $stepHtml, 1);
        }
        echo $stepHtml;
        ?>

        <div class="actions">
            <div>
                <?php if ($step > 1): ?>
                    <button type="submit" name="actie" value="terug" class="btn">← Terug</button>
                <?php endif ?>
            </div>
            <div class="actions-right">
                <?php if (isset(\WV\Content::GATES[$step])): ?>
                    <button type="submit" name="actie" value="overslaan" class="btn">Ik sla dit over</button>
                <?php endif ?>
                <button type="submit" name="actie" value="concept" class="btn btn-soft">Opslaan als concept</button>
                <?php if ($step < $total): ?>
                    <button type="submit" name="actie" value="volgende" class="btn btn-primary">Verder →</button>
                <?php else: ?>
                    <button type="submit" name="actie" value="publiceer" class="btn btn-primary" data-confirm="Weet u zeker dat u uw wilsverklaring wilt publiceren? Eerdere versies blijven bewaard."><?= !empty($isPublished) ? 'Opnieuw publiceren' : 'Publiceer verklaring' ?></button>
                <?php endif ?>
            </div>
        </div>
    </div>
</form>
<p class="center muted small">Uw antwoorden worden bewaard als u verdergaat of op “Opslaan als concept” klikt. U kunt altijd stoppen en later verder.</p>

<?php if ($step === $total): ?>
    <div class="callout"><p><strong>Na het publiceren:</strong> download de PDF, print hem en onderteken met datum en handtekening. Geef een kopie aan uw arts, uw naasten en uw gevolmachtigde en laat hem opnemen in uw medisch dossier. Controleer uw verklaring af en toe: bent u het nog eens, zet er dan een nieuwe datum en handtekening onder.</p></div>
    <?php $this->include('partials/hulp') ?>
<?php endif ?>
<?php $this->include('partials/bron') ?>

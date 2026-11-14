<?php
$this->layout('layouts/app', ['title' => $intro['title'] . ' · WilsVerklaring']);
?>
<?php $this->include('wizard/_chapters', ['chapter' => $chapter, 'step' => 0, 'data' => $data, 'published' => $published, 'form' => false]) ?>

<div class="card conversation">
    <div class="callout"><p>Voordat de vragen beginnen, leggen we eerst uit waar dit onderwerp over gaat. Neem er gerust de tijd voor.</p></div>

    <p class="eyebrow mt">Onderwerp <?= (int) $chapter ?> van <?= count(\WV\Content::CHAPTER_NAMES) ?></p>
    <h1><?= e($intro['title']) ?></h1>
    <p class="intro-lead"><?= e($intro['lead']) ?></p>

    <div class="intro-grid">
        <?php foreach ($intro['cards'] as [$head, $text]): ?>
            <section class="intro-card">
                <h2><?= e($head) ?></h2>
                <p><?= e($text) ?></p>
            </section>
        <?php endforeach ?>
    </div>

    <section aria-labelledby="intro-vragen" class="mb">
        <h2 id="intro-vragen">Hierover gaan we het hebben</h2>
        <ol class="topics">
            <?php foreach ($steps as $i => $s): ?>
                <li><span class="nr" aria-hidden="true"><?= $i + 1 ?></span><span><strong><?= e(\WV\Content::TITLES[$s]) ?></strong><span class="muted"><?= e(\WV\Content::STEP_SUMMARIES[$s]) ?></span></span></li>
            <?php endforeach ?>
        </ol>
        <p class="muted small">Bij de vragen leest u steeds eerst uitleg over wat de keuze betekent.</p>
    </section>

    <section class="sun" aria-labelledby="intro-tips">
        <h2 id="intro-tips">Een paar tips</h2>
        <ul>
            <li>Lees dit eventueel samen met iemand die u vertrouwt.</li>
            <li>Twijfelt u? Laat een vraag open en bespreek hem eerst met uw huisarts.</li>
            <li>Er zijn geen goede of foute antwoorden. Het gaat om wat u wilt.</li>
        </ul>
    </section>

    <div class="actions">
        <div>
            <a class="btn" href="<?= e($backHref) ?>">← Terug</a>
        </div>
        <div class="actions-right">
            <a class="btn btn-primary" href="/verklaring/wizard?stap=<?= (int) $firstStep ?>">Ik ben er klaar voor →</a>
        </div>
    </div>
</div>
<?php $this->include('partials/bron') ?>

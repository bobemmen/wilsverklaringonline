<?php /** @var ?array $verification */ ?>
<?php if ($verification): ?>
    <section class="card verified<?= $verification['current'] ? ' verified-ok' : '' ?>" aria-labelledby="verified-title">
        <h2 class="card-title" id="verified-title">
            <?= $verification['current'] ? 'Bevestigd door de huisarts' : 'Eerder bevestigd door de huisarts' ?>
        </h2>
        <p>
            <strong><?= e($verification['arts_name']) ?></strong><?= !empty($verification['practice']) ? ', ' . e($verification['practice']) : '' ?>
            heeft op <?= e(fmt_date($verification['verified_at'])) ?> bevestigd dat deze patiënt in persoon is gezien en dat de identiteit is gecontroleerd.
            <?php if ($verification['his_masked'] !== ''): ?>Patiënt-ID in het HIS: <strong><?= e($verification['his_masked']) ?></strong>.<?php endif ?>
        </p>
        <?php if (!$verification['current']): ?>
            <p class="mb0"><strong>Let op:</strong> deze verklaring is na die bevestiging gewijzigd. De huidige versie is nog niet opnieuw bevestigd.</p>
        <?php endif ?>
    </section>
<?php endif ?>

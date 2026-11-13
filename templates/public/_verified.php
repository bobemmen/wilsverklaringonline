<?php /** @var ?array $verification */ ?>
<?php if ($verification): ?>
    <section class="card verified verified-ok" aria-labelledby="verified-title">
        <h2 class="card-title" id="verified-title">Identiteit bevestigd door de huisarts</h2>
        <p class="mb0">
            <strong><?= e($verification['arts_name']) ?></strong><?= !empty($verification['practice']) ? ', ' . e($verification['practice']) : '' ?>
            heeft op <?= e(fmt_date($verification['verified_at'])) ?> bevestigd dat dit account bij deze patiënt hoort en dat de patiënt in persoon is gezien en geïdentificeerd.
            <?php if ($verification['his_masked'] !== ''): ?>Patiënt-ID in het HIS: <strong><?= e($verification['his_masked']) ?></strong>.<?php endif ?>
        </p>
    </section>
<?php endif ?>

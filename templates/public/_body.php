<?php
$has = fn(string $k): bool => trim((string) ($doc[$k] ?? '')) !== '';
$pillRow = function (string $label, string $value, string $mb = '8px'): string {
    return '<div style="margin-bottom: ' . $mb . ';"><span class="summary-label" style="display: inline-block; min-width: 150px;">' . e($label)
        . '</span><span class="pill">' . e(ucfirst_mb($value)) . '</span></div>';
};
$block = function (string $label, string $value, string $mb = '12px'): string {
    return '<div style="margin-bottom: ' . $mb . ';"><div class="summary-label">' . e($label) . '</div><div class="summary-value">' . nl2br_e($value) . '</div></div>';
};
$quote = '<div class="summary-value" style="margin-top: 10px; font-style: italic; color: var(--wv-text-muted); border-left: 2px solid var(--wv-purple-light); padding-left: 10px;">';
?>

<?php if ($has('intro') || $has('waarde')): ?>
    <div class="block-header">Over mij</div>
    <div class="block-section">
        <?php if ($has('intro')) echo $block('Wie ik ben', $doc['intro']) ?>
        <?php if ($has('waarde')) echo $block('Wat mijn leven waarde geeft', $doc['waarde'], '0') ?>
    </div>
<?php endif ?>

<?php if ($has('grens') || $has('ervaring')): ?>
    <div class="block-header">Mijn grens</div>
    <div class="block-section">
        <?php if ($has('grens')) echo $block('Grens', $doc['grens']) ?>
        <?php if ($has('ervaring')) echo $block('Ervaringen', $doc['ervaring'], '0') ?>
    </div>
<?php endif ?>

<div class="block-header">Behandelverbod</div>
<div class="block-section">
    <?php foreach (['reanimatie' => 'Reanimatie', 'beademing' => 'Beademing', 'ic' => 'Intensive care', 'antibiotica' => 'Antibiotica', 'sonde' => 'Sondevoeding'] as $k => $label): ?>
        <?php if ($has($k)) echo $pillRow($label, $doc[$k]) ?>
    <?php endforeach ?>
    <?php foreach (['reanimatie_toelichting', 'beademing_toelichting', 'behandel_toelichting'] as $k): ?>
        <?php if ($has($k)): ?><?= $quote ?><?= nl2br_e($doc[$k]) ?></div><?php endif ?>
    <?php endforeach ?>
</div>

<?php if ($has('palliatief_wens')): ?>
    <div class="block-header">Palliatieve sedatie</div>
    <div class="block-section">
        <?= $pillRow('Wens', $doc['palliatief_wens']) ?>
        <?php foreach (['palliatief_fysiek' => 'Fysiek lijden', 'palliatief_geestelijk' => 'Geestelijk lijden'] as $k => $label): ?>
            <?php if ($has($k)) echo $block($label, $doc[$k], '0') . '<div style="height:10px"></div>' ?>
        <?php endforeach ?>
        <?php if ($has('palliatief_diepte')) echo $pillRow('Diepte', $doc['palliatief_diepte']) ?>
    </div>
<?php endif ?>

<?php if (\WV\Content::euthanasieIncluded($doc)): ?>
    <div class="block-header">Euthanasieverzoek</div>
    <div class="block-section">
        <?php foreach (['euthanasie_lijden' => 'Wat ik ondraaglijk vind', 'euthanasie_situatie' => 'Situatie'] as $k => $label): ?>
            <?php if ($has($k)) echo $block($label, $doc[$k]) ?>
        <?php endforeach ?>
        <?php if ($has('euthanasie_wilsonbekwaam')) echo $pillRow('Bij wilsonbekwaamheid', $doc['euthanasie_wilsonbekwaam'], '12px') ?>
        <?php if ($has('euthanasie_wilsonbekwaam_toelichting')) echo $block('Toelichting', $doc['euthanasie_wilsonbekwaam_toelichting']) ?>
        <?php if ($has('euthanasie_brief')): ?>
            <div style="margin-top: 14px;">
                <div class="summary-label">Brief aan de arts</div>
                <div class="summary-value" style="font-style: italic; color: var(--wv-text-muted); border-left: 2px solid var(--wv-purple-light); padding-left: 10px; margin-top: 6px;">
                    <?= nl2br_e($doc['euthanasie_brief']) ?>
                </div>
            </div>
        <?php endif ?>
    </div>
<?php endif ?>

<?php if ($has('orgaandonatie') || $has('uitvaart') || $has('overig')): ?>
    <div class="block-header">Na het overlijden</div>
    <div class="block-section">
        <?php if ($has('orgaandonatie')) echo $pillRow('Orgaandonatie', $doc['orgaandonatie'], '10px') ?>
        <?php foreach (['uitvaart' => 'Uitvaart', 'overig' => 'Overig'] as $k => $label): ?>
            <?php if ($has($k)) echo $block($label, $doc[$k], '10px') ?>
        <?php endforeach ?>
    </div>
<?php endif ?>

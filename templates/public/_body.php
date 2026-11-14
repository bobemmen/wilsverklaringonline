<?php
$has = fn(string $k): bool => trim((string) ($doc[$k] ?? '')) !== '';
$row = fn(string $label, string $value): string => '<div class="doc-row"><span class="doc-label">' . e($label) . '</span>'
    . (trim($value) === '' ? '<span class="badge badge-empty">Niet ingevuld</span>' : '<span class="badge badge-brand">' . e(\WV\Content::choiceLabel($value)) . '</span>') . '</div>';
$block = fn(string $label, string $value): string => '<h3>' . e($label) . '</h3><p class="doc-text">' . nl2br_e($value) . '</p>';
$quote = fn(string $value): string => '<blockquote class="doc-quote">' . nl2br_e($value) . '</blockquote>';
$sec = fn(string $t) => '<h2>' . e($t) . '</h2>';
?>
<div class="card">
<?php if ($has('intro') || $has('waarde')): ?>
    <section class="doc-section"><?= $sec('Over mij') ?>
        <?php if ($has('intro')) echo $block('Wie ik ben', $doc['intro']) ?>
        <?php if ($has('waarde')) echo $block('Wat mijn leven waarde geeft', $doc['waarde']) ?>
    </section>
<?php endif ?>

<?php if ($has('grens') || $has('ervaring')): ?>
    <section class="doc-section"><?= $sec('Mijn grens') ?>
        <?php if ($has('grens')) echo $block('Grens', $doc['grens']) ?>
        <?php if ($has('ervaring')) echo $block('Ervaringen', $doc['ervaring']) ?>
    </section>
<?php endif ?>

<section class="doc-section"><?= $sec('Behandelverbod') ?>
    <?php foreach (['reanimatie' => 'Reanimatie', 'beademing' => 'Beademing', 'ic' => 'Intensive care', 'antibiotica' => 'Antibiotica', 'sonde' => 'Sondevoeding'] as $k => $label): ?>
        <?= $row($label, (string) ($doc[$k] ?? '')) ?>
    <?php endforeach ?>
    <?php foreach (['reanimatie_toelichting', 'beademing_toelichting', 'behandel_toelichting'] as $k): ?>
        <?php if ($has($k)) echo $quote($doc[$k]) ?>
    <?php endforeach ?>
</section>

<section class="doc-section"><?= $sec('Palliatieve sedatie') ?>
        <?= $row('Wens', (string) ($doc['palliatief_wens'] ?? '')) ?>
        <?php foreach (['palliatief_fysiek' => 'Fysiek lijden', 'palliatief_geestelijk' => 'Geestelijk lijden'] as $k => $label): ?>
            <?php if ($has($k)) echo $block($label, $doc[$k]) ?>
        <?php endforeach ?>
        <?php if ($has('palliatief_diepte')) echo $row('Diepte', $doc['palliatief_diepte']) ?>
</section>

<?php if (\WV\Content::euthanasieIncluded($doc)): ?>
    <section class="doc-section"><?= $sec('Euthanasieverzoek') ?>
        <?php foreach (['euthanasie_lijden' => 'Wat ik ondraaglijk vind', 'euthanasie_situatie' => 'Situatie'] as $k => $label): ?>
            <?php if ($has($k)) echo $block($label, $doc[$k]) ?>
        <?php endforeach ?>
        <?php if ($has('euthanasie_wilsonbekwaam')) echo $row('Als ik zelf niet meer kan vragen', $doc['euthanasie_wilsonbekwaam']) ?>
        <?php if ($has('euthanasie_wilsonbekwaam_toelichting')) echo $quote($doc['euthanasie_wilsonbekwaam_toelichting']) ?>
        <?php if ($has('euthanasie_brief')): ?>
            <h3>Brief aan de arts</h3>
            <?= $quote($doc['euthanasie_brief']) ?>
        <?php endif ?>
    </section>
<?php endif ?>

<?php if (\WV\Content::dementieIncluded($doc)): ?>
    <section class="doc-section"><?= $sec('Euthanasieverzoek bij dementie') ?>
        <?php if ($has('dementie_lijden')) echo $block('Situatie waarin ik mijn leven niet meer wil verlengen', $doc['dementie_lijden']) ?>
        <?php if ($has('dementie_moment')) echo $row('Vanaf welk moment', $doc['dementie_moment']) ?>
        <?php if ($has('dementie_naasten')) echo $block('Wat mijn naasten moeten weten of doen', $doc['dementie_naasten']) ?>
        <?php if ($has('dementie_huisarts')) echo $row('Huisarts open voor euthanasie bij dementie', $doc['dementie_huisarts']) ?>
    </section>
<?php endif ?>

<?php if ($has('volmacht')): ?>
    <section class="doc-section"><?= $sec('Medische volmacht') ?>
        <p class="doc-text">Namens mij mag beslissen over mijn medische behandeling, als ik dat zelf niet meer kan:</p>
        <p class="doc-text"><strong><?= e($doc['volmacht']) ?></strong><?php if ($has('volmacht_contact')): ?><br><?= e($doc['volmacht_contact']) ?><?php endif ?></p>
    </section>
<?php endif ?>

<section class="doc-section"><?= $sec('Na het overlijden') ?>
        <?= $row('Orgaandonatie', (string) ($doc['orgaandonatie'] ?? '')) ?>
        <?php foreach (['uitvaart' => 'Uitvaart', 'overig' => 'Overig'] as $k => $label): ?>
            <?php if ($has($k)) echo $block($label, $doc[$k]) ?>
        <?php endforeach ?>
</section>
<p class="doc-text muted mb0">Ik heb deze verklaring zelf en uit vrije wil opgesteld.</p>
</div>

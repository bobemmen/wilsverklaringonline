<div class="callout"><p>Lees uw verklaring nog eens rustig door. Klopt alles? Dan kunt u hieronder publiceren. Uw arts zal altijd de meest recente versie te zien krijgen.</p></div>

<?php
$d = \WV\Content::normalize($data);
$c = fn(string $k): string => $d[$k] === '' ? '' : \WV\Content::choiceLabel((string) $d[$k]);
$blocks = [
    'Introductie' => [
        'Wie ik ben' => $d['intro'],
        'Wat mijn leven waarde geeft' => $d['waarde'],
    ],
    'Grens en ervaring' => [
        'Mijn grens' => $d['grens'],
        'Vormende ervaringen' => $d['ervaring'],
    ],
    'Behandelverbod' => [
        'Reanimatie' => $c('reanimatie') . ($d['reanimatie_toelichting'] ? ' — ' . $d['reanimatie_toelichting'] : ''),
        'Beademing' => $c('beademing'),
        'Intensive care' => $c('ic'),
        'Antibiotica' => $c('antibiotica'),
        'Sondevoeding' => $c('sonde'),
        'Toelichting' => trim($d['beademing_toelichting'] . ' ' . $d['behandel_toelichting']),
    ],
    'Palliatieve sedatie' => [
        'Wens' => $c('palliatief_wens'),
        'Fysiek lijden' => $d['palliatief_fysiek'],
        'Geestelijk lijden' => $d['palliatief_geestelijk'],
        'Diepte' => $c('palliatief_diepte'),
    ],
];
if (\WV\Content::euthanasieIncluded($d)) {
    $blocks['Euthanasieverzoek'] = [
        'Ondraaglijk lijden' => $d['euthanasie_lijden'],
        'Concrete situatie' => $d['euthanasie_situatie'],
        'Bij wilsonbekwaamheid' => $c('euthanasie_wilsonbekwaam'),
        'Toelichting wilsonbekwaamheid' => $d['euthanasie_wilsonbekwaam_toelichting'],
        'Brief aan de arts' => $d['euthanasie_brief'],
    ];
}
$blocks['Na het overlijden'] = [
    'Orgaandonatie' => $c('orgaandonatie'),
    'Uitvaart' => $d['uitvaart'],
    'Overig' => $d['overig'],
];
$blocks['Afsluiting'] = [
    'Gevolmachtigde' => trim($d['volmacht'] . ($d['volmacht_contact'] !== '' ? ' — ' . $d['volmacht_contact'] : '')),
    'Arts' => $d['arts'],
    'Besproken met arts' => $c('besproken'),
    'Datum' => fmt_iso_date($d['datum']),
];
?>
<?php foreach ($blocks as $title => $items): ?>
    <section class="summary" aria-labelledby="sam-<?= e(md5($title)) ?>">
        <h2 id="sam-<?= e(md5($title)) ?>"><?= e($title) ?></h2>
        <dl>
            <?php foreach ($items as $label => $value): ?>
                <dt><?= e($label) ?></dt>
                <dd>
                    <?php if (trim((string) $value) === ''): ?>
                        <em>Niet ingevuld</em>
                    <?php else: ?>
                        <?= nl2br_e(trim((string) $value)) ?>
                    <?php endif ?>
                </dd>
            <?php endforeach ?>
        </dl>
    </section>
<?php endforeach ?>

<div class="callout"><p>Lees uw verklaring nog eens rustig door. Klopt alles? Dan kunt u hieronder publiceren. Uw arts zal altijd de meest recente versie te zien krijgen.</p></div>

<?php
$d = \WV\Content::normalize($data);
$c = fn(string $k): string => $d[$k] === '' ? '' : \WV\Content::choiceLabel((string) $d[$k]);
$blocks = [
    'Uw gegevens' => [
        'Naam' => \WV\Content::fullName($d),
        'Geboortenaam' => $d['geboortenaam'],
        'Geboortedatum' => fmt_iso_date($d['geboortedatum']),
        'Geboorteplaats' => $d['geboorteplaats'],
    ],
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
    $blocks['Euthanasieverzoek algemeen'] = [
        'Ondraaglijk lijden' => $d['euthanasie_lijden'],
        'Concrete situatie' => $d['euthanasie_situatie'],
        'Als ik zelf niet meer kan vragen' => $c('euthanasie_wilsonbekwaam'),
        'Toelichting' => $d['euthanasie_wilsonbekwaam_toelichting'],
        'Brief aan de arts' => $d['euthanasie_brief'],
    ];
}
if (\WV\Content::dementieIncluded($d)) {
    $blocks['Euthanasieverzoek dementie'] = [
        'Situatie waarin ik mijn leven niet meer wil verlengen' => $d['dementie_lijden'],
        'Vanaf welk moment dit geldt' => $c('dementie_moment'),
        'Wat mijn naasten moeten weten of doen' => $d['dementie_naasten'],
        'Huisarts staat open voor euthanasie bij dementie' => $c('dementie_huisarts'),
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
    'Plaats' => $d['plaats'],
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

<?php $open = \WV\Content::notFilled($d); $missingNow = \WV\Content::missingForPublish($d); ?>
<?php if ($missingNow): ?>
    <div class="callout callout-warn" role="note">
        <p><strong>Dit moet nog ingevuld zijn voordat u kunt publiceren:</strong></p>
        <ul>
            <?php foreach ($missingNow as $field => $m): ?><li><a href="/verklaring/wizard?stap=<?= (int) \WV\Content::stepOfField($field) ?>#<?= e($field) ?>"><?= e(rtrim(preg_replace('/^Vul /', '', $m), '.')) ?></a></li><?php endforeach ?>
        </ul>
        <p class="mb0 muted">Met de knoppen bovenaan de pagina gaat u direct naar die stap.</p>
    </div>
<?php endif ?>
<?php if ($open): ?>
    <div class="callout" role="note">
        <p><strong>Deze onderdelen heeft u niet ingevuld:</strong></p>
        <ul>
            <?php foreach ($open as $o): ?><li><?= e($o) ?></li><?php endforeach ?>
        </ul>
        <p class="mb0">U mag gewoon publiceren. In uw verklaring staat dan bij deze onderdelen “Niet ingevuld”, zodat uw arts niet denkt dat u geen bezwaar heeft. U kunt later altijd een nieuwe versie publiceren.</p>
    </div>
<?php endif ?>
<fieldset class="field" id="bevestig">
    <legend class="sr-only">Bevestiging</legend>
    <?= $this->fieldError('bevestig') ?>
    <label class="choice">
        <input type="checkbox" name="bevestig" value="1"<?= $this->invalid('bevestig') ?>>
        <span><span class="choice-label">Ik heb mijn verklaring rustig doorgelezen en wil hem publiceren.</span></span>
    </label>
</fieldset>

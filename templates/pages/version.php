<?php $this->layout('layouts/app', ['title' => 'Versie ' . $number . ' · WilsVerklaring']) ?>
<p><a href="/verklaring/versies">← Alle versies</a></p>

<div class="card doc-head">
    <p class="eyebrow">Wilsverklaring van</p>
    <h1><?= e(\WV\Content::fullName($doc, $this->user()['name'])) ?></h1>
    <?php if (($birth = \WV\Content::birthLine($doc)) !== ''): ?><p class="muted"><?= e($birth) ?></p><?php endif ?>
    <p class="muted mb0">Versie <?= (int) $number ?> · <?= e(fmt_date($version['created_at'])) ?><?php if (!empty($doc['datum'])): ?> · ondertekend op <?= e(fmt_iso_date($doc['datum'])) ?><?php endif ?></p>
    <?php if ($isCurrent): ?><p class="mt mb0"><a href="/verklaring/versies/<?= (int) $number ?>/pdf" class="btn">Download als PDF</a></p><?php endif ?>
</div>

<?php if ($isCurrent): ?>
    <div class="alert alert-ok" role="note">Dit is de huidige versie: dit ziet uw arts.</div>
<?php else: ?>
    <div class="alert alert-warn" role="note">Dit is een oudere versie, alleen ter inzage. Uw arts ziet de nieuwste versie.</div>
<?php endif ?>

<?php $this->include('public/_body', ['doc' => $doc]) ?>

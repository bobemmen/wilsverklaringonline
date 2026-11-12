<?php $this->layout('layouts/app', ['title' => 'Patiënten · WilsVerklaring']) ?>
<div class="page-head">
    <h1>Patiënten</h1>
    <p>Praktijk <strong><?= e($practice['name']) ?></strong>. Alle artsen van de praktijk zien dezelfde patiënten; per patiënt staat welke arts hem begeleidt. Alleen patiënten die een arts uitnodigt kunnen zich registreren. Zo staat bij elke verklaring dat een huisarts de identiteit van de patiënt heeft bevestigd.</p>
</div>

<?php if ($paperCode): ?>
    <section class="card card-accent" aria-labelledby="i-code">
        <h2 class="card-title" id="i-code">Code voor op papier</h2>
        <p>Schrijf deze code op voor de patiënt. Hij wordt hier maar één keer getoond en is bij ons alleen versleuteld bewaard.</p>
        <p class="paper-code" aria-label="Code: <?= e(implode(' ', str_split($paperCode))) ?>"><?= e(substr($paperCode, 0, 3) . ' ' . substr($paperCode, 3)) ?></p>
    </section>
<?php endif ?>

<section class="card" aria-labelledby="i-nieuw">
    <h2 class="card-title" id="i-nieuw">Patiënt uitnodigen</h2>
    <p class="muted">Nodig alleen een patiënt uit die u in persoon heeft gezien. U krijgt een code van 6 cijfers die u de patiënt op papier meegeeft. Daarmee registreert de patiënt zich, samen met e-mailadres en BSN; zijn identiteit is dan meteen bevestigd. Een patiënt kan zich niet op een andere manier registreren.</p>
    <form method="post" action="/arts/uitnodigen" autocomplete="off">
        <?= $this->csrf() ?>
        <div class="field">
            <label for="name">Naam patiënt</label>
            <input type="text" id="name" name="name" value="<?= e($this->old('name')) ?>" maxlength="120" required<?= $this->invalid('name') ?>>
            <?= $this->fieldError('name') ?>
        </div>
        <div class="field">
            <label for="email">E-mailadres patiënt</label>
            <input type="email" id="email" name="email" value="<?= e($this->old('email')) ?>" required<?= $this->invalid('email') ?>>
            <?= $this->fieldError('email') ?>
        </div>
        <div class="field">
            <label for="bsn">BSN</label>
            <input type="text" id="bsn" name="bsn" inputmode="numeric" pattern="[0-9 ]{8,11}" maxlength="11" required<?= $this->invalid('bsn') ?>>
            <?= $this->fieldError('bsn') ?>
        </div>
        <div class="field">
            <label for="his_id">Patiënt-ID uit het HIS</label>
            <p class="hint" id="his-hint">Kopieer het ID uit het patiëntdossier. De patiënt kent dit ID niet; het wordt als bewijs bij de verklaring bewaard.</p>
            <input type="text" id="his_id" name="his_id" value="<?= e($this->old('his_id')) ?>" maxlength="64" spellcheck="false" required aria-describedby="his-hint"<?= $this->invalid('his_id') ?>>
            <?= $this->fieldError('his_id') ?>
        </div>
        <fieldset class="field" id="seen">
            <legend>Bevestiging door u als huisarts</legend>
            <?= $this->fieldError('seen') ?>
            <label class="choice">
                <input type="checkbox" name="seen" value="1" required>
                <span><span class="choice-label">Ik heb deze patiënt in levende lijve gezien en gesproken.</span></span>
            </label>
            <label class="choice">
                <input type="checkbox" name="identity" value="1" required>
                <span><span class="choice-label">Ik heb de identiteit gecontroleerd en dit is de patiënt met bovenstaand patiënt-ID.</span></span>
            </label>
            <label class="choice">
                <input type="checkbox" name="toestemming" value="1" required>
                <span><span class="choice-label">De patiënt weet dat ik naam, e-mailadres, BSN en HIS-ID invoer in dit platform en gaat hiermee mondeling akkoord.</span></span>
            </label>
            <p class="muted">Uw naam, praktijk, de datum en een deel van het patiënt-ID worden bij de verklaring getoond. U bevestigt de identiteit, niet de inhoud van de verklaring.</p>
        </fieldset>
        <button type="submit" class="btn btn-primary">Uitnodigen en code aanmaken</button>
    </form>
</section>

<section class="card" aria-labelledby="i-lijst">
    <h2 class="card-title" id="i-lijst">Uitnodigingen en verificaties</h2>
    <?php $filtered = $filter['q'] !== '' || $filter['bsn'] !== '' || $filter['status'] !== '' || $filter['arts'] !== '' ?>
    <?php if ($total > 0): ?>
    <form method="post" action="/arts/zoeken" class="search" role="search" aria-label="Zoek in uw patiënten" autocomplete="off">
        <?= $this->csrf() ?>
        <div class="field">
            <label for="q">Zoek op naam, e-mailadres of HIS-ID</label>
            <input type="search" id="q" name="q" value="<?= e($filter['q']) ?>" autocomplete="off">
        </div>
        <div class="field">
            <label for="zbsn">Zoek op BSN (exact)</label>
            <input type="text" id="zbsn" name="bsn" value="<?= e($filter['bsn']) ?>" inputmode="numeric" maxlength="11" autocomplete="off">
        </div>
        <div class="field">
            <label for="zarts">Arts</label>
            <select id="zarts" name="arts">
                <option value=""<?= $filter['arts'] === '' ? ' selected' : '' ?>>Alle artsen</option>
                <option value="mine"<?= $filter['arts'] === 'mine' ? ' selected' : '' ?>>Mijn patiënten</option>
                <option value="none"<?= $filter['arts'] === 'none' ? ' selected' : '' ?>>Zonder arts (alleen praktijk)</option>
                <?php foreach ($team as $tid => $tname): ?>
                    <option value="<?= e($tid) ?>"<?= $filter['arts'] === $tid ? ' selected' : '' ?>><?= e($tname) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="field">
            <label for="zstatus">Status</label>
            <select id="zstatus" name="status">
                <?php foreach (['' => 'Alle', 'geverifieerd' => 'Geregistreerd', 'uitgenodigd' => 'Wacht op registratie', 'ingetrokken' => 'Ingetrokken', 'verlopen' => 'Verlopen'] as $val => $label): ?>
                    <option value="<?= e($val) ?>"<?= $filter['status'] === $val ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Zoeken</button>
            <?php if ($filtered): ?><a class="btn" href="/arts">Wis zoekopdracht</a><?php endif ?>
        </div>
    </form>
    <p class="muted" role="status"><?= $filtered ? (int) $matched . ' van ' . (int) $total . ' gevonden' : (int) $total . ' in totaal' ?></p>
    <?php endif ?>
    <?php if ($total === 0): ?>
        <p class="mb0">U heeft nog niemand uitgenodigd.</p>
    <?php elseif (!$items): ?>
        <p class="mb0">Geen patiënten gevonden met deze zoekopdracht.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Patiënt</th><th scope="col">Arts</th><th scope="col">Uitgenodigd op</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Acties</span></th></tr></thead>
                <tbody>
                <?php foreach ($items as $v): ?>
                    <tr>
                        <th scope="row"><?= e($v['patient_name']) ?></th>
                        <td><?php if ($v['owner_name']): ?><?= e($v['owner_name']) ?><?= $v['arts_id'] === $me ? ' <span class="muted">(u)</span>' : '' ?><?php else: ?><span class="badge badge-muted">Praktijk</span><?php endif ?></td>
                        <td><?= e(fmt_date($v['created_at'])) ?></td>
                        <td>
                            <?php switch ($v['effective']):
                                case 'geverifieerd': ?><span class="badge badge-ok">Geregistreerd</span><?php break;
                                case 'ingetrokken': case 'verlopen': ?><span class="badge badge-bad"><?= $v['effective'] === 'verlopen' ? 'Verlopen' : 'Ingetrokken' ?></span><?php break;
                                default: ?><span class="badge badge-muted">Wacht op registratie</span><?php
                            endswitch ?>
                        </td>
                        <td>
                            <?php if (in_array($v['effective'], ['geverifieerd', 'uitgenodigd'], true) && $v['arts_id'] !== $me): ?>
                                <form method="post" action="/arts/patienten/<?= e($v['id']) ?>/overnemen" class="inline-form">
                                    <?= $this->csrf() ?>
                                    <button type="submit" class="btn btn-small">Overnemen<span class="sr-only"> (<?= e($v['patient_name']) ?>)</span></button>
                                </form>
                            <?php endif ?>
                            <?php if ($v['effective'] === 'geverifieerd'): ?>
                                <a class="btn btn-small" href="/arts/patienten/<?= e($v['id']) ?>">Openen<span class="sr-only"> (<?= e($v['patient_name']) ?>)</span></a>
                            <?php endif ?>
                            <?php if ($v['effective'] === 'uitgenodigd'): ?>
                                <form method="post" action="/arts/patienten/<?= e($v['id']) ?>/papiercode" class="inline-form">
                                    <?= $this->csrf() ?>
                                    <button type="submit" class="btn btn-small">Code op papier<span class="sr-only"> (<?= e($v['patient_name']) ?>)</span></button>
                                </form>
                            <?php endif ?>
                            <?php if (in_array($v['effective'], ['uitgenodigd', 'geverifieerd'], true)): ?>
                                <form method="post" action="/arts/patienten/<?= e($v['id']) ?>/intrekken" class="inline-form">
                                    <?= $this->csrf() ?>
                                    <button type="submit" class="btn btn-small btn-danger" data-confirm="Intrekken?">Intrekken<span class="sr-only"> (<?= e($v['patient_name']) ?>)</span></button>
                                </form>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
    <?php if ($pages > 1): ?>
        <nav aria-label="Pagina's" class="btn-row mt">
            <?php $qs = fn(int $p) => '/arts?pagina=' . $p ?>
            <?php if ($page > 1): ?><a class="btn btn-small" href="<?= e($qs($page - 1)) ?>">← Vorige</a><?php endif ?>
            <span class="muted">Pagina <?= (int) $page ?> van <?= (int) $pages ?></span>
            <?php if ($page < $pages): ?><a class="btn btn-small" href="<?= e($qs($page + 1)) ?>">Volgende →</a><?php endif ?>
        </nav>
    <?php endif ?>
</section>

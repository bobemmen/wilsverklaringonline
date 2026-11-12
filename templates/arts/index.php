<?php $this->layout('layouts/app', ['title' => 'Patiënten · WilsVerklaring']) ?>
<div class="page-head">
    <h1>Patiënten</h1>
    <p>Alleen patiënten die u uitnodigt kunnen zich registreren. Zo staat bij elke verklaring dat de huisarts de identiteit van de patiënt heeft bevestigd.</p>
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
            <p class="muted">Uw naam, praktijk, de datum en een deel van het patiënt-ID worden bij de verklaring getoond. U bevestigt de identiteit, niet de inhoud van de verklaring.</p>
        </fieldset>
        <button type="submit" class="btn btn-primary">Uitnodigen en code aanmaken</button>
    </form>
</section>

<section class="card" aria-labelledby="i-lijst">
    <h2 class="card-title" id="i-lijst">Uitnodigingen en verificaties</h2>
    <?php if (!$items): ?>
        <p class="mb0">U heeft nog niemand uitgenodigd.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Patiënt</th><th scope="col">Uitgenodigd op</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Acties</span></th></tr></thead>
                <tbody>
                <?php foreach ($items as $v): ?>
                    <tr>
                        <th scope="row"><?= e($v['patient_name']) ?></th>
                        <td><?= e(fmt_date($v['created_at'])) ?></td>
                        <td>
                            <?php switch ($v['effective']):
                                case 'geverifieerd': ?><span class="badge badge-ok">Geregistreerd</span><?php break;
                                case 'ingetrokken': case 'verlopen': ?><span class="badge badge-bad"><?= $v['effective'] === 'verlopen' ? 'Verlopen' : 'Ingetrokken' ?></span><?php break;
                                default: ?><span class="badge badge-muted">Wacht op registratie</span><?php
                            endswitch ?>
                        </td>
                        <td>
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
</section>

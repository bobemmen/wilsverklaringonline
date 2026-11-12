<?php $this->layout('layouts/app', ['title' => 'Patiënten · WilsVerklaring']) ?>
<div class="page-head">
    <h1>Patiënten</h1>
    <p>Nodig een patiënt uit om zijn wilsverklaring online te maken. Nadat u de patiënt in persoon heeft gezien, bevestigt u hier dat de verklaring echt van deze patiënt is.</p>
</div>

<?php if ($paperCode): ?>
    <section class="card card-accent" aria-labelledby="i-code">
        <h2 class="card-title" id="i-code">Code voor op papier</h2>
        <p>Schrijf deze code op voor de patiënt. Hij wordt hier maar één keer getoond en is bij ons alleen versleuteld bewaard.</p>
        <p class="paper-code" aria-label="Code: <?= e(implode(' ', str_split($paperCode))) ?>"><?= e(substr($paperCode, 0, 3) . ' ' . substr($paperCode, 3)) ?></p>
    </section>
<?php endif ?>

<?php if ($inviteUrl): ?>
    <section class="card card-accent" aria-labelledby="i-link">
        <h2 class="card-title" id="i-link">Persoonlijke link</h2>
        <p>Deze link is ook per e-mail verstuurd. Hij is veertien dagen geldig en wordt hier maar één keer getoond.</p>
        <div class="token-box"><?= e($inviteUrl) ?></div>
    </section>
<?php endif ?>

<section class="card" aria-labelledby="i-nieuw">
    <h2 class="card-title" id="i-nieuw">Patiënt uitnodigen</h2>
    <p class="muted">De patiënt krijgt alleen een e-mail met een link. Geboortedatum, BSN en patiënt-ID worden niet gemaild. De patiënt bevestigt geboortedatum en BSN zelf en voert daarna een code in: per sms of de code die u op papier meegeeft. BSN en geboortedatum worden niet leesbaar opgeslagen.</p>
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
            <label for="dob">Geboortedatum</label>
            <input type="date" id="dob" name="dob" value="<?= e($this->old('dob')) ?>" required<?= $this->invalid('dob') ?>>
            <?= $this->fieldError('dob') ?>
        </div>
        <div class="field">
            <label for="bsn">BSN</label>
            <input type="text" id="bsn" name="bsn" inputmode="numeric" pattern="[0-9 ]{8,11}" maxlength="11" required<?= $this->invalid('bsn') ?>>
            <?= $this->fieldError('bsn') ?>
        </div>
        <?php $method = $this->old('method', 'sms') ?>
        <fieldset class="field">
            <legend>Tweede controle voor de patiënt</legend>
            <label class="choice<?= $method === 'sms' ? ' selected' : '' ?>">
                <input type="radio" name="method" value="sms"<?= $method === 'sms' ? ' checked' : '' ?>>
                <span><span class="choice-label">Code per sms</span><span class="choice-help">Naar het mobiele nummer hieronder.</span></span>
            </label>
            <label class="choice<?= $method === 'papier' ? ' selected' : '' ?>">
                <input type="radio" name="method" value="papier"<?= $method === 'papier' ? ' checked' : '' ?>>
                <span><span class="choice-label">Code op papier</span><span class="choice-help">U krijgt een code van 6 cijfers die u de patiënt meegeeft.</span></span>
            </label>
        </fieldset>
        <div class="field">
            <label for="phone">Mobiel nummer patiënt <span class="muted">(alleen bij sms)</span></label>
            <p class="hint" id="phone-hint">Vul het nummer in dat u kent uit het dossier.</p>
            <input type="tel" id="phone" name="phone" value="<?= e($this->old('phone')) ?>" autocomplete="off" inputmode="tel" aria-describedby="phone-hint"<?= $this->invalid('phone') ?>>
            <?= $this->fieldError('phone') ?>
        </div>
        <div class="field">
            <label for="his_id">Patiënt-ID uit het HIS</label>
            <p class="hint" id="his-hint">Kopieer het ID uit het patiëntdossier. De patiënt kent dit ID niet; het wordt als bewijs bij de verklaring bewaard.</p>
            <input type="text" id="his_id" name="his_id" value="<?= e($this->old('his_id')) ?>" maxlength="64" spellcheck="false" required aria-describedby="his-hint"<?= $this->invalid('his_id') ?>>
            <?= $this->fieldError('his_id') ?>
        </div>
        <button type="submit" class="btn btn-primary">Uitnodiging versturen</button>
    </form>
</section>

<section class="card" aria-labelledby="i-lijst">
    <h2 class="card-title" id="i-lijst">Uitnodigingen en verificaties</h2>
    <?php if (!$items): ?>
        <p class="mb0">U heeft nog niemand uitgenodigd.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Patiënt</th><th scope="col">Uitgenodigd</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Acties</span></th></tr></thead>
                <tbody>
                <?php foreach ($items as $v): ?>
                    <tr>
                        <th scope="row"><?= e($v['patient_name']) ?></th>
                        <td><?= e(fmt_date($v['created_at'])) ?></td>
                        <td>
                            <?php switch ($v['effective']):
                                case 'geverifieerd': ?><span class="badge badge-ok">Bevestigd</span><?php break;
                                case 'gekoppeld': ?><span class="badge badge-brand">Wacht op uw bevestiging</span><?php break;
                                case 'ingetrokken': case 'verlopen': ?><span class="badge badge-bad"><?= $v['effective'] === 'verlopen' ? 'Verlopen' : 'Ingetrokken' ?></span><?php break;
                                default: ?><span class="badge badge-muted">Uitgenodigd</span><?php
                            endswitch ?>
                        </td>
                        <td>
                            <?php if (in_array($v['effective'], ['gekoppeld', 'geverifieerd'], true)): ?>
                                <a class="btn btn-small" href="/arts/patienten/<?= e($v['id']) ?>">Openen<span class="sr-only"> (<?= e($v['patient_name']) ?>)</span></a>
                            <?php endif ?>
                            <?php if ($v['effective'] === 'uitgenodigd'): ?>
                                <form method="post" action="/arts/patienten/<?= e($v['id']) ?>/papiercode" class="inline-form">
                                    <?= $this->csrf() ?>
                                    <button type="submit" class="btn btn-small">Code op papier<span class="sr-only"> (<?= e($v['patient_name']) ?>)</span></button>
                                </form>
                            <?php endif ?>
                            <?php if (in_array($v['effective'], ['uitgenodigd', 'gekoppeld', 'geverifieerd'], true)): ?>
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

<?php $this->layout('layouts/app', ['title' => 'Toegang voor uw arts · WilsVerklaring']) ?>
<div class="page-head">
    <h1>Toegang voor uw arts</h1>
    <p>Uw huisarts hoeft geen account aan te maken. U geeft hem of haar een persoonlijke link, en daarmee is altijd de nieuwste versie van uw verklaring te lezen.</p>
</div>

<?php if ($plaintextToken): ?>
    <section class="card card-accent" aria-labelledby="t-nieuw">
        <h2 class="card-title" id="t-nieuw">Uw nieuwe link</h2>
        <p>Kopieer deze link nu. Wij bewaren de code alleen versleuteld: na deze pagina kunt u hem niet meer terugzien.</p>
        <div class="token-box"><?= e($this->url('/v/' . $plaintextToken)) ?></div>
    </section>
<?php endif ?>

<section class="card" aria-labelledby="t-status">
    <h2 class="card-title" id="t-status">Status</h2>
    <div class="stat-row">
        <?php if ($tokenActive): ?>
            <span class="badge badge-ok">Actief</span>
        <?php elseif ($declaration['token_idx']): ?>
            <span class="badge badge-bad">Verlopen</span>
        <?php else: ?>
            <span class="badge badge-muted">Nog geen link</span>
        <?php endif ?>
        <span class="muted"><?= (int) $viewCount ?> keer bekeken</span>
    </div>
    <div class="btn-row">
        <form method="post" action="/token/genereer">
            <?= $this->csrf() ?>
            <button type="submit" class="btn btn-primary"<?= $declaration['token_idx'] ? ' data-confirm="De huidige link werkt daarna niet meer. Doorgaan?"' : '' ?>><?= $declaration['token_idx'] ? 'Nieuwe link maken' : 'Link maken' ?></button>
        </form>
        <?php if ($declaration['token_idx']): ?>
            <form method="post" action="/token/intrekken">
                <?= $this->csrf() ?>
                <button type="submit" class="btn btn-danger" data-confirm="Weet u zeker dat u de toegang wilt intrekken?">Toegang intrekken</button>
            </form>
        <?php endif ?>
    </div>
</section>

<section class="card" aria-labelledby="t-inst">
    <h2 class="card-title" id="t-inst">Instellingen</h2>
    <form method="post" action="/token/instellingen">
        <?= $this->csrf() ?>
        <?php
        $emailValue = $this->old('notification_email', (string) ($declaration['notification_email'] ?? ''));
        $expiresValue = $this->old('access_token_expires_at', $declaration['token_expires_at'] ? date('Y-m-d', $declaration['token_expires_at']) : '');
        ?>
        <div class="field">
            <label for="notification_email">E-mailadres van uw arts (niet verplicht)</label>
            <p class="hint" id="notification_email-hint">Als u dit invult, krijgt uw arts eerst een bevestigingslink per e-mail voordat de verklaring opent. Zonder dit adres is de toegang direct.</p>
            <input type="email" id="notification_email" name="notification_email" value="<?= e($emailValue) ?>" placeholder="praktijk@huisarts.nl" aria-describedby="notification_email-hint<?= $this->error('notification_email') ? ' notification_email-error' : '' ?>"<?= $this->error('notification_email') ? ' aria-invalid="true"' : '' ?>>
            <?= $this->fieldError('notification_email') ?>
        </div>
        <div class="field">
            <label for="access_token_expires_at">Vervaldatum (niet verplicht)</label>
            <input type="date" id="access_token_expires_at" name="access_token_expires_at" value="<?= e($expiresValue) ?>"<?= $this->invalid('access_token_expires_at') ?>>
            <?= $this->fieldError('access_token_expires_at') ?>
        </div>
        <button type="submit" class="btn btn-primary">Opslaan</button>
    </form>
</section>

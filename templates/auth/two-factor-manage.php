<?php $this->layout('layouts/app', ['title' => 'Beveiliging · WilsVerklaring']) ?>
<div class="page-head">
    <h1>Tweestapsverificatie en herstelcodes</h1>
    <p>Tweestapsverificatie staat aan. Met herstelcodes komt u binnen als u uw telefoon kwijt bent.</p>
</div>

<section class="card" aria-labelledby="b-codes">
    <h2 class="card-title" id="b-codes">Herstelcodes</h2>
    <p>
        <?php if ($remaining > 3): ?>
            <span class="badge badge-ok"><?= (int) $remaining ?> over</span>
        <?php elseif ($remaining > 0): ?>
            <span class="badge badge-bad"><?= (int) $remaining ?> over</span> Maak binnenkort nieuwe codes.
        <?php else: ?>
            <span class="badge badge-bad">Geen codes meer</span> Maak nu nieuwe codes.
        <?php endif ?>
    </p>
    <form method="post" action="/2fa/herstelcodes/nieuw">
        <?= $this->csrf() ?>
        <div class="field">
            <label for="pw1">Bevestig met uw wachtwoord</label>
            <input type="password" id="pw1" name="password" autocomplete="current-password" required<?= $this->invalid('password') ?>>
            <?= $this->fieldError('password') ?>
        </div>
        <button type="submit" class="btn" data-confirm="Nieuwe codes maken? De oude codes werken dan niet meer.">Nieuwe herstelcodes maken</button>
    </form>
</section>

<section class="card" aria-labelledby="b-dev">
    <h2 class="card-title" id="b-dev">Nieuw apparaat koppelen</h2>
    <p>Nieuwe telefoon? Koppel hem hier. De oude koppeling vervalt zodra u de nieuwe heeft bevestigd.</p>
    <form method="post" action="/2fa/opnieuw">
        <?= $this->csrf() ?>
        <div class="field">
            <label for="pw2">Bevestig met uw wachtwoord</label>
            <input type="password" id="pw2" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn" data-confirm="Een nieuw apparaat koppelen? U wordt gevraagd een nieuwe code te bevestigen.">Nieuw apparaat koppelen</button>
    </form>
</section>

<?php if ($isArts): ?>
<section class="card" aria-labelledby="b-pw">
    <h2 class="card-title" id="b-pw">Wachtwoord</h2>
    <a href="/arts/wachtwoord" class="btn">Wachtwoord wijzigen</a>
</section>
<?php endif ?>

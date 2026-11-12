<?php $this->layout('layouts/app', ['title' => 'Wachtwoord wijzigen · WilsVerklaring']) ?>
<div class="page-head">
    <h1>Wachtwoord wijzigen</h1>
    <?php if ($forced): ?>
        <p>U logde in met een tijdelijk wachtwoord. Kies eerst een eigen wachtwoord om verder te gaan.</p>
    <?php endif ?>
</div>
<section class="card">
    <form method="post" action="/arts/wachtwoord" autocomplete="off">
        <?= $this->csrf() ?>
        <div class="field">
            <label for="current">Huidig wachtwoord</label>
            <input type="password" id="current" name="current" autocomplete="current-password" required<?= $this->invalid('current') ?>>
            <?= $this->fieldError('current') ?>
        </div>
        <div class="field">
            <label for="password">Nieuw wachtwoord</label>
            <p class="hint" id="pw-hint">Minimaal 12 tekens. Een zin van een paar woorden werkt goed.</p>
            <input type="password" id="password" name="password" autocomplete="new-password" minlength="12" required aria-describedby="pw-hint"<?= $this->invalid('password') ?>>
            <?= $this->fieldError('password') ?>
        </div>
        <div class="field">
            <label for="password_confirmation">Nieuw wachtwoord nog eens</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-primary">Wachtwoord wijzigen</button>
    </form>
</section>

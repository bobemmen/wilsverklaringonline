<?php $this->layout('layouts/guest', ['title' => 'Registreren · WilsVerklaring']) ?>
<h1>Registreren</h1>
<p class="muted">U kunt zich alleen registreren nadat uw huisarts u heeft uitgenodigd. Uw huisarts geeft u daarvoor een code van 6 cijfers op papier. Daarmee is meteen uw identiteit bevestigd.</p>

<form method="post" action="/registreren" autocomplete="off">
    <?= $this->csrf() ?>
    <div class="field">
        <label for="name">Uw naam</label>
        <input type="text" id="name" name="name" value="<?= e($this->old('name')) ?>" autocomplete="name" required autofocus<?= $this->invalid('name') ?>>
        <?= $this->fieldError('name') ?>
    </div>
    <div class="field">
        <label for="bsn">Uw BSN</label>
        <input type="text" id="bsn" name="bsn" inputmode="numeric" pattern="[0-9 ]{8,11}" maxlength="11" required<?= $this->invalid('bsn') ?>>
        <?= $this->fieldError('bsn') ?>
    </div>
    <div class="field">
        <label for="email">E-mailadres</label>
        <p class="hint" id="email-hint">Gebruik het e-mailadres dat uw huisarts van u heeft.</p>
        <input type="email" id="email" name="email" value="<?= e($this->old('email')) ?>" autocomplete="username" required aria-describedby="email-hint"<?= $this->invalid('email') ?>>
        <?= $this->fieldError('email') ?>
    </div>
    <div class="field">
        <label for="password">Wachtwoord</label>
        <p class="hint" id="password-hint">Minimaal 10 tekens. Een zin van een paar woorden werkt goed en is makkelijk te onthouden.</p>
        <input type="password" id="password" name="password" autocomplete="new-password" minlength="10" required aria-describedby="password-hint<?= $this->error('password') ? ' password-error' : '' ?>"<?= $this->error('password') ? ' aria-invalid="true"' : '' ?>>
        <?= $this->fieldError('password') ?>
    </div>
    <div class="field">
        <label for="password_confirmation">Wachtwoord nog eens</label>
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
    </div>
    <div class="field">
        <label for="code">Code van uw huisarts</label>
        <p class="hint" id="code-hint">De code van 6 cijfers die u op papier heeft gekregen.</p>
        <input type="text" id="code" name="code" inputmode="numeric" maxlength="7" pattern="[0-9 ]{6,7}" required aria-describedby="code-hint"<?= $this->invalid('code') ?>>
        <?= $this->fieldError('code') ?>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Registreren</button>
</form>

<p class="guest-links mb0">Al een account? <a href="/inloggen">Inloggen</a></p>

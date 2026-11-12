<?php $this->layout('layouts/guest', ['title' => 'Account aanmaken · WilsVerklaring']) ?>
<h1>Account aanmaken</h1>
<p class="muted">Uw verklaring wordt versleuteld opgeslagen. Wij vragen alleen om wat nodig is.</p>

<form method="post" action="/registreren">
    <?= $this->csrf() ?>
    <div class="field">
        <label for="name">Uw naam</label>
        <input type="text" id="name" name="name" value="<?= e($this->old('name')) ?>" autocomplete="name" required autofocus<?= $this->invalid('name') ?>>
        <?= $this->fieldError('name') ?>
    </div>
    <div class="field">
        <label for="email">E-mailadres</label>
        <input type="email" id="email" name="email" value="<?= e($this->old('email')) ?>" autocomplete="username" required<?= $this->invalid('email') ?>>
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
    <button type="submit" class="btn btn-primary btn-block">Account aanmaken</button>
</form>

<p class="guest-links mb0">Al een account? <a href="/inloggen">Inloggen</a></p>

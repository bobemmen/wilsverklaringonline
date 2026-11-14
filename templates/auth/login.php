<?php $this->layout('layouts/guest', ['title' => 'Inloggen · WilsVerklaring']) ?>
<h1>Inloggen</h1>
<p class="muted">Log in om uw wilsverklaring te openen of aan te passen.</p>

<form method="post" action="/inloggen">
    <?= $this->csrf() ?>
    <div class="field">
        <label for="email">E-mailadres</label>
        <input type="email" id="email" name="email" value="<?= e($this->old('email')) ?>" autocomplete="username" required autofocus<?= $this->invalid('email') ?>>
        <?= $this->fieldError('email') ?>
    </div>
    <div class="field">
        <label for="password">Wachtwoord</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Inloggen</button>
</form>

<p class="guest-links"><a href="/wachtwoord-vergeten">Wachtwoord vergeten?</a></p>
<?php if ($this->demoEnabled()): ?><p class="guest-links">Eerst even kijken? <a href="/demo">Bekijk de demo</a></p><?php endif ?>
<p class="guest-links mb0">Nog geen account? <a href="/registreren">Account aanmaken</a></p>

<?php $this->layout('layouts/guest', ['title' => 'Inloggen · WilsVerklaring']) ?>
<h1 style="font-size: 22px; margin-bottom: 4px;">Inloggen</h1>
<p class="small muted" style="margin-bottom: 18px;">Log in om uw wilsverklaring te openen of bij te werken.</p>

<form method="post" action="/inloggen">
    <?= $this->csrf() ?>
    <div class="field">
        <label class="label" for="email">E-mailadres</label>
        <input type="email" id="email" name="email" value="<?= e($this->old('email')) ?>" autocomplete="username" required autofocus>
        <?php if ($err = $this->error('email')): ?><div class="error"><?= e($err) ?></div><?php endif ?>
    </div>
    <div class="field">
        <label class="label" for="password">Wachtwoord</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn btn-primary" style="width: 100%;">Inloggen</button>
</form>

<div class="guest-links">
    Nog geen account? <a href="/registreren">Registreer</a>
</div>

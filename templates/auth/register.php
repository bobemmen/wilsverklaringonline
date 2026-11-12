<?php $this->layout('layouts/guest', ['title' => 'Account aanmaken · WilsVerklaring']) ?>
<h1 style="font-size: 22px; margin-bottom: 4px;">Account aanmaken</h1>
<p class="small muted" style="margin-bottom: 18px;">Uw verklaring blijft versleuteld opgeslagen. Wij vragen alleen om wat nodig is.</p>

<form method="post" action="/registreren">
    <?= $this->csrf() ?>
    <div class="field">
        <label class="label" for="name">Naam</label>
        <input type="text" id="name" name="name" value="<?= e($this->old('name')) ?>" autocomplete="name" required autofocus>
        <?php if ($err = $this->error('name')): ?><div class="error"><?= e($err) ?></div><?php endif ?>
    </div>
    <div class="field">
        <label class="label" for="email">E-mailadres</label>
        <input type="email" id="email" name="email" value="<?= e($this->old('email')) ?>" autocomplete="username" required>
        <?php if ($err = $this->error('email')): ?><div class="error"><?= e($err) ?></div><?php endif ?>
    </div>
    <div class="field">
        <label class="label" for="password">Wachtwoord (minimaal 10 tekens)</label>
        <input type="password" id="password" name="password" autocomplete="new-password" minlength="10" required>
        <?php if ($err = $this->error('password')): ?><div class="error"><?= e($err) ?></div><?php endif ?>
    </div>
    <div class="field">
        <label class="label" for="password_confirmation">Wachtwoord herhalen</label>
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
    </div>
    <button type="submit" class="btn btn-primary" style="width: 100%;">Account aanmaken</button>
</form>

<div class="guest-links">
    Al een account? <a href="/inloggen">Inloggen</a>
</div>

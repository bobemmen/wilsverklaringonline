<?php $this->layout('layouts/guest', ['title' => 'Wachtwoord vergeten · WilsVerklaring']) ?>
<h1>Wachtwoord vergeten</h1>
<p class="muted">Vul het e-mailadres van uw account in. U krijgt een e-mail met een link om een nieuw wachtwoord te kiezen. Tweestapsverificatie blijft daarna gewoon nodig.</p>

<form method="post" action="/wachtwoord-vergeten">
    <?= $this->csrf() ?>
    <div class="field">
        <label for="email">E-mailadres</label>
        <input type="email" id="email" name="email" value="<?= e($this->old('email')) ?>" autocomplete="username" required autofocus<?= $this->invalid('email') ?>>
        <?= $this->fieldError('email') ?>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Stuur mij een link</button>
</form>

<p class="guest-links mb0"><a href="/inloggen">Terug naar inloggen</a></p>
<p class="muted small">Bent u huisarts? Vraag uw praktijkbeheerder om een nieuw tijdelijk wachtwoord.</p>

<?php $this->layout('layouts/guest', ['title' => 'Registreren · WilsVerklaring']) ?>
<h1>Registreren</h1>
<p class="muted">U kunt zich alleen registreren nadat uw huisarts u heeft uitgenodigd. Uw huisarts geeft u daarvoor een code van 6 cijfers op papier. Samen met uw geboortedatum is daarmee uw identiteit bevestigd.</p>

<form method="post" action="/registreren" autocomplete="off">
    <?= $this->csrf() ?>
    <div class="field">
        <label for="name">Uw naam</label>
        <input type="text" id="name" name="name" value="<?= e($this->old('name')) ?>" autocomplete="name" required autofocus<?= $this->invalid('name') ?>>
        <?= $this->fieldError('name') ?>
    </div>
    <div class="field">
        <label for="email">E-mailadres</label>
        <p class="hint" id="email-hint">Gebruik het e-mailadres dat uw huisarts van u heeft.</p>
        <input type="email" id="email" name="email" value="<?= e($this->old('email')) ?>" autocomplete="username" required aria-describedby="email-hint"<?= $this->invalid('email') ?>>
        <?= $this->fieldError('email') ?>
    </div>
    <div class="field">
        <label for="birthdate">Geboortedatum</label>
        <p class="hint" id="birthdate-hint">Bijvoorbeeld 07-03-1985. Uw huisarts vergelijkt dit met uw dossier.</p>
        <input type="text" id="birthdate" name="birthdate" value="<?= e($this->old('birthdate')) ?>" inputmode="numeric" maxlength="10" placeholder="dd-mm-jjjj" autocomplete="off" required aria-describedby="birthdate-hint"<?= $this->invalid('birthdate') ?>>
        <?= $this->fieldError('birthdate') ?>
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
    <fieldset class="field" id="akkoord">
        <legend>Toestemming</legend>
        <?= $this->fieldError('akkoord') ?>
        <label class="choice">
            <input type="checkbox" name="akkoord" value="1" required>
            <span><span class="choice-label">Ik heb de <a href="/pilot" target="_blank" rel="noopener">pilotinformatie</a> en de <a href="/privacy" target="_blank" rel="noopener">privacyverklaring</a> gelezen. Ik geef toestemming voor het opslaan van mijn gegevens en wilsverklaring in deze pilot, en ervoor dat de artsen van mijn huisartsenpraktijk mijn gepubliceerde verklaring kunnen inzien. Ik kan dit altijd intrekken.</span></span>
        </label>
    </fieldset>
    <button type="submit" class="btn btn-primary btn-block">Registreren</button>
</form>

<p class="guest-links mb0">Al een account? <a href="/inloggen">Inloggen</a></p>

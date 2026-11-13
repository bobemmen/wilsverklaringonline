<?php $this->layout('layouts/guest', ['title' => 'Nieuw wachtwoord · WilsVerklaring']) ?>
<?php if (!$valid): ?>
    <h1>Deze link werkt niet meer</h1>
    <p>De link is verlopen of al gebruikt. Vraag een nieuwe aan.</p>
    <a href="/wachtwoord-vergeten" class="btn btn-primary btn-block">Nieuwe link aanvragen</a>
<?php else: ?>
    <h1>Nieuw wachtwoord kiezen</h1>
    <form method="post" action="/wachtwoord-resetten/<?= e($token) ?>" autocomplete="off">
        <?= $this->csrf() ?>
        <div class="field">
            <label for="password">Nieuw wachtwoord</label>
            <p class="hint" id="pw-hint">Minimaal 10 tekens. Een zin van een paar woorden werkt goed.</p>
            <input type="password" id="password" name="password" autocomplete="new-password" minlength="10" required autofocus aria-describedby="pw-hint"<?= $this->invalid('password') ?>>
            <?= $this->fieldError('password') ?>
        </div>
        <div class="field">
            <label for="password_confirmation">Nieuw wachtwoord nog eens</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Wachtwoord opslaan</button>
    </form>
<?php endif ?>

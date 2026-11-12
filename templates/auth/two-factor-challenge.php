<?php $this->layout('layouts/guest', ['title' => 'Verificatiecode · WilsVerklaring']) ?>
<h1>Verificatiecode</h1>
<p class="muted">Open uw authenticator-app en typ de code van 6 cijfers die daar nu staat.</p>

<form method="post" action="/2fa/controle">
    <?= $this->csrf() ?>
    <div class="field">
        <label for="code">Code</label>
        <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required autofocus<?= $this->invalid('code') ?>>
        <?= $this->fieldError('code') ?>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Bevestigen</button>
</form>

<form method="post" action="/uitloggen" class="guest-links">
    <?= $this->csrf() ?>
    <button type="submit" class="btn btn-text">Uitloggen</button>
</form>

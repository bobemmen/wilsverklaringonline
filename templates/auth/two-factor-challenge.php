<?php $this->layout('layouts/guest', ['title' => 'Verificatie · WilsVerklaring']) ?>
<h1 style="font-size: 22px;">Verificatie</h1>
<p class="small muted" style="margin-bottom: 18px;">Voer de actuele code uit uw authenticator-app in.</p>

<form method="post" action="/2fa/controle">
    <?= $this->csrf() ?>
    <div class="field">
        <label class="label" for="code">Code</label>
        <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required autofocus>
        <?php if ($err = $this->error('code')): ?><div class="error"><?= e($err) ?></div><?php endif ?>
    </div>
    <button type="submit" class="btn btn-primary" style="width: 100%;">Bevestigen</button>
</form>

<form method="post" action="/uitloggen" style="margin-top: 14px; text-align: center;">
    <?= $this->csrf() ?>
    <button type="submit" class="btn btn-ghost">Uitloggen</button>
</form>

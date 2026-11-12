<?php $this->layout('layouts/app', ['title' => 'Sms-code · WilsVerklaring']) ?>
<div class="page-head">
    <h1>Voer uw sms-code in</h1>
    <p>We hebben een code van 6 cijfers gestuurd naar <strong><?= e($phone) ?></strong>. Dit nummer heeft uw huisarts opgegeven.</p>
</div>
<section class="card">
    <form method="post" action="/uitnodiging/<?= e($token) ?>/code" autocomplete="off">
        <?= $this->csrf() ?>
        <div class="field" id="code">
            <label for="codeveld">Code uit de sms</label>
            <input type="text" id="codeveld" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]{6,7}" maxlength="7" required autofocus<?= $this->invalid('code') ?>>
            <?= $this->fieldError('code') ?>
        </div>
        <p class="muted">De code is 10 minuten geldig. Na vijf foute pogingen vervalt de uitnodiging.</p>
        <button type="submit" class="btn btn-primary">Bevestigen</button>
    </form>
    <?php if ($canResend): ?>
        <form method="post" action="/uitnodiging/<?= e($token) ?>/opnieuw" class="mt">
            <?= $this->csrf() ?>
            <button type="submit" class="btn">Stuur een nieuwe code</button>
        </form>
    <?php endif ?>
</section>

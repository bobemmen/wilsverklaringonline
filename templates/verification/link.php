<?php $this->layout('layouts/app', ['title' => 'Koppelen aan uw huisarts · WilsVerklaring']) ?>
<div class="page-head">
    <h1>Koppelen aan uw huisarts</h1>
    <p><?= e($arts['name'] ?: 'Uw huisarts') ?><?= !empty($arts['practice']) ? ' (' . e($arts['practice']) . ')' : '' ?> heeft u uitgenodigd, <?= e($name) ?>. Bevestig uw gegevens zodat wij zeker weten dat de uitnodiging voor u is.</p>
</div>
<section class="card">
    <form method="post" action="/uitnodiging/<?= e($token) ?>" autocomplete="off">
        <?= $this->csrf() ?>
        <?= $this->fieldError('match') ?>
        <div class="field">
            <label for="dob">Uw geboortedatum</label>
            <input type="date" id="dob" name="dob" required>
        </div>
        <div class="field">
            <label for="bsn">Uw BSN</label>
            <input type="text" id="bsn" name="bsn" inputmode="numeric" pattern="[0-9 ]{8,11}" maxlength="11" required>
        </div>
        <label class="choice" id="consent">
            <input type="checkbox" name="consent" value="1" required>
            <span><span class="choice-label">Mijn huisarts mag mijn gepubliceerde wilsverklaring lezen en bevestigen dat ik het ben.</span><span class="choice-help">U kunt dit altijd weer intrekken op uw dashboard.</span></span>
        </label>
        <p class="muted">Na vijf foute pogingen vervalt de uitnodiging; uw huisarts kan u dan opnieuw uitnodigen.</p>
        <button type="submit" class="btn btn-primary">Koppelen</button>
    </form>
</section>

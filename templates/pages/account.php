<?php $this->layout('layouts/app', ['title' => 'Account en gegevens · WilsVerklaring']) ?>
<h1>Account en gegevens</h1>
<p class="muted small" style="margin-bottom: 18px;">U beslist over uw gegevens: u kunt ze op elk moment downloaden of volledig laten verwijderen.</p>

<div class="card">
    <div class="card-title">Gegevens exporteren</div>
    <p class="small muted">Download alles wat wij van u bewaren (profiel, alle versies van uw verklaring, uitnodigingen en raadplegingen) als JSON-bestand.</p>
    <a href="/account/export" class="btn btn-ghost">Download mijn gegevens</a>
</div>

<div class="card" style="border-color: #E8B4B4;">
    <div class="card-title">Account verwijderen</div>
    <p class="small muted">Hiermee worden uw account, uw verklaring met alle versies, alle uitnodigingen en toegangscodes <strong>definitief</strong> verwijderd. Dit kan niet ongedaan worden gemaakt.</p>
    <form method="post" action="/account/verwijderen">
        <?= $this->csrf() ?>
        <div class="field">
            <label class="label" for="password">Bevestig met uw wachtwoord</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            <?php if ($err = $this->error('password')): ?><div class="error"><?= e($err) ?></div><?php endif ?>
        </div>
        <button type="submit" class="btn btn-danger" data-confirm="Weet u zeker dat u uw account en alle gegevens definitief wilt verwijderen?">Account definitief verwijderen</button>
    </form>
</div>

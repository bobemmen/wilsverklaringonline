<?php $this->layout('layouts/app', ['title' => 'Account · WilsVerklaring']) ?>
<div class="page-head">
    <h1>Account en gegevens</h1>
    <p>U beslist over uw gegevens: u kunt ze op elk moment downloaden of volledig laten verwijderen.</p>
</div>

<section class="card" aria-labelledby="a-weergave">
    <h2 class="card-title" id="a-weergave">Weergave</h2>
    <p>Tekstgrootte, contrast en ruimte tussen de regels aanpassen.</p>
    <a href="<?= e($this->prefLink([])) ?>" class="btn">Weergave aanpassen</a>
</section>

<section class="card" aria-labelledby="a-2fa">
    <h2 class="card-title" id="a-2fa">Beveiliging</h2>
    <p>Uw verklaring is alleen bereikbaar na tweestapsverificatie met een authenticator-app.</p>
    <a href="/2fa/instellen" class="btn">Status tweestapsverificatie</a>
</section>

<section class="card" aria-labelledby="a-export">
    <h2 class="card-title" id="a-export">Gegevens downloaden</h2>
    <p>Download alles wat wij van u bewaren (profiel, alle versies van uw verklaring, uitnodigingen en raadplegingen) als JSON-bestand.</p>
    <a href="/account/export" class="btn">Download mijn gegevens</a>
</section>

<section class="card card-danger" aria-labelledby="a-del">
    <h2 class="card-title" id="a-del">Account verwijderen</h2>
    <p>Hiermee worden uw account, uw verklaring met alle versies, alle uitnodigingen en toegangscodes <strong>definitief</strong> verwijderd. Dit kan niet ongedaan worden gemaakt.</p>
    <form method="post" action="/account/verwijderen">
        <?= $this->csrf() ?>
        <div class="field">
            <label for="password">Bevestig met uw wachtwoord</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required<?= $this->invalid('password') ?>>
            <?= $this->fieldError('password') ?>
        </div>
        <button type="submit" class="btn btn-danger" data-confirm="Weet u zeker dat u uw account en alle gegevens definitief wilt verwijderen?">Account definitief verwijderen</button>
    </form>
</section>

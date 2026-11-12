<?php $this->layout('layouts/guest', ['title' => 'Tweestapsverificatie · WilsVerklaring']) ?>
<h1 style="font-size: 22px;">Tweestapsverificatie</h1>

<?php if ($confirmed): ?>
    <div class="info-block">Tweestapsverificatie is actief. Om veiligheidsredenen tonen wij de geheime sleutel niet opnieuw.</div>
    <p class="small muted">Is uw telefoon kwijt? Neem dan contact op met de beheerder van deze installatie; die kan tweestapsverificatie resetten.</p>
    <a href="/dashboard" class="btn btn-primary">Naar het dashboard</a>
<?php else: ?>
    <p class="small muted" style="margin-bottom: 18px;">Uw verklaring is alleen bereikbaar na tweestapsverificatie. Voer de sleutel hieronder in een authenticator-app in (bijv. Google Authenticator, 1Password, Aegis) en bevestig met de code die de app toont.</p>

    <div class="field">
        <div class="label">Geheime sleutel</div>
        <div class="token-display"><?= e(trim(chunk_split($secret, 4, ' '))) ?></div>
    </div>

    <div class="field">
        <div class="label">otpauth-URL (voor apps die dit openen)</div>
        <div class="small muted" style="word-break: break-all;"><a href="<?= e($otpauth) ?>"><?= e($otpauth) ?></a></div>
    </div>

    <form method="post" action="/2fa/instellen">
        <?= $this->csrf() ?>
        <div class="field">
            <label class="label" for="code">Code uit uw app</label>
            <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required autofocus>
            <?php if ($err = $this->error('code')): ?><div class="error"><?= e($err) ?></div><?php endif ?>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Bevestigen</button>
    </form>
<?php endif ?>

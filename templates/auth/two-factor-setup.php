<?php $this->layout('layouts/guest', ['title' => 'Tweestapsverificatie · WilsVerklaring']) ?>
<h1>Tweestapsverificatie</h1>

<?php if ($confirmed): ?>
    <div class="callout"><p>Tweestapsverificatie staat aan. Om veiligheidsredenen tonen wij de geheime sleutel niet opnieuw.</p></div>
    <p class="muted">Is uw telefoon kwijt? Neem dan contact op met de beheerder van deze installatie; die kan tweestapsverificatie opnieuw instellen.</p>
    <a href="/dashboard" class="btn btn-primary btn-block">Naar het dashboard</a>
<?php else: ?>
    <p>Uw verklaring is alleen bereikbaar met een tweede stap: een code uit een app op uw telefoon, zoals Google Authenticator, Microsoft Authenticator of Aegis.</p>
    <ol class="steps">
        <li>Open de app en kies <strong>toevoegen</strong>.</li>
        <li>Kies <strong>sleutel handmatig invoeren</strong> en typ de sleutel hieronder over.</li>
        <li>Typ de code van 6 cijfers die de app dan toont in het vak onderaan.</li>
    </ol>

    <div class="field">
        <p class="eyebrow" id="sleutel-label">Geheime sleutel</p>
        <div class="token-box" aria-labelledby="sleutel-label"><?= e(trim(chunk_split($secret, 4, ' '))) ?></div>
    </div>

    <p class="small muted">Op een telefoon met de app erop kunt u ook <a href="<?= e($otpauth) ?>">deze link openen</a>.</p>

    <form method="post" action="/2fa/instellen">
        <?= $this->csrf() ?>
        <div class="field">
            <label for="code">Code uit uw app</label>
            <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required autofocus<?= $this->invalid('code') ?>>
            <?= $this->fieldError('code') ?>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Bevestigen</button>
    </form>
<?php endif ?>

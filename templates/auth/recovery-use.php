<?php $this->layout('layouts/guest', ['title' => 'Herstelcode · WilsVerklaring']) ?>
<h1>Herstelcode gebruiken</h1>
<p class="muted">Typ een van de herstelcodes die u bij het instellen heeft gekregen. Elke code werkt één keer.</p>

<form method="post" action="/2fa/herstel" autocomplete="off">
    <?= $this->csrf() ?>
    <div class="field">
        <label for="code">Herstelcode</label>
        <p class="hint" id="rc-hint">Bijvoorbeeld K7QM2-X9PDA</p>
        <input type="text" id="code" name="code" maxlength="14" spellcheck="false" autocapitalize="characters" required autofocus aria-describedby="rc-hint"<?= $this->invalid('code') ?>>
        <?= $this->fieldError('code') ?>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Inloggen met herstelcode</button>
</form>

<p class="guest-links"><a href="/2fa/controle">Terug naar de code uit de app</a></p>

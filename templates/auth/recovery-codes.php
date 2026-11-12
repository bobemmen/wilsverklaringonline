<?php $this->layout('layouts/guest', ['title' => 'Herstelcodes · WilsVerklaring']) ?>
<h1>Bewaar uw herstelcodes</h1>
<p>Raakt u uw telefoon kwijt, dan kunt u met een herstelcode toch inloggen. Elke code werkt <strong>één keer</strong>. Bewaar ze op een veilige plek, los van uw telefoon: op papier, of in uw wachtwoordmanager.</p>

<ol class="codes" aria-label="Herstelcodes">
    <?php foreach ($codes as $c): ?>
        <li><?= e($c) ?></li>
    <?php endforeach ?>
</ol>

<p class="muted">Deze codes worden niet opnieuw getoond. U kunt later nieuwe codes maken; de oude werken dan niet meer.</p>

<form method="post" action="/2fa/herstelcodes/bewaard">
    <?= $this->csrf() ?>
    <button type="submit" class="btn btn-primary btn-block">Ik heb de codes bewaard</button>
</form>

<?php $this->layout('layouts/plain', ['title' => 'WilsVerklaring · Uw stem, voor wanneer u zelf niet meer kunt spreken']) ?>
<div class="page-head">
    <p class="eyebrow">Wilsverklaring online</p>
    <h1>Uw stem, voor wanneer u zelf niet meer kunt spreken</h1>
    <p class="lead">WilsVerklaring helpt u stap voor stap uw wensen voor het einde van uw leven vast te leggen. U schrijft in uw eigen woorden, deelt met uw huisarts en naasten, en houdt zelf de regie.</p>
</div>

<div class="btn-row mb">
    <?php if ($loggedIn): ?>
        <a href="/dashboard" class="btn btn-primary">Naar mijn dashboard</a>
    <?php else: ?>
        <a href="/registreren" class="btn btn-primary">Registreren met code van uw huisarts</a>
        <a href="/inloggen" class="btn">Inloggen</a>
    <?php endif ?>
</div>

<div class="card">
    <h2 class="card-title">Hoe werkt het?</h2>
    <ol class="steps">
        <li><strong>Uw huisarts nodigt u uit</strong> en geeft u een code op papier. Daarmee registreert u zich; uw identiteit is dan meteen bevestigd. Stel daarna tweestapsverificatie in.</li>
        <li><strong>Doorloop de vragen</strong> in zes hoofdstukken: wie u bent, wat u wel en niet wilt, en waar uw grens ligt. U kunt altijd stoppen en later verdergaan.</li>
        <li><strong>Publiceer uw verklaring.</strong> Uw huisarts en naasten kunnen via een persoonlijke link altijd de nieuwste versie lezen.</li>
    </ol>
</div>

<div class="card">
    <h2 class="card-title">Prettig lezen</h2>
    <p>Is de tekst te klein of te zwak van kleur? Met <strong>A+</strong> bovenaan maakt u alles groter. Bij <a href="<?= e($this->prefLink([])) ?>">Weergave</a> kiest u ook hoog contrast, een donker thema of ruimere regels. Met <strong>Lees voor</strong> laat u de pagina uitspreken.</p>
</div>

<?php $this->layout('layouts/plain', ['title' => 'WilsVerklaring · Samen nadenken over wat u belangrijk vindt', 'wide' => true]) ?>
<section class="card hero" aria-labelledby="hero-titel">
    <div class="hero-text">
        <div class="hero-logo"><?= $this->logo(96, false) ?></div>
        <h1 id="hero-titel">Samen nadenken over wat u belangrijk vindt</h1>
        <p class="lead">We stellen u rustig de vragen die bij een wilsverklaring horen, met uitleg bij elke keuze. U schrijft in uw eigen woorden, deelt met uw huisarts en naasten, en houdt zelf de regie.</p>
        <div class="btn-row">
            <?php if ($loggedIn): ?>
                <a href="/dashboard" class="btn btn-primary">Naar mijn dashboard</a>
            <?php else: ?>
                <a href="/registreren" class="btn btn-primary">Beginnen met de code van mijn huisarts</a>
                <a href="/inloggen" class="btn btn-soft">Inloggen</a>
                <?php if ($this->demoEnabled()): ?><a href="/demo" class="btn btn-soft">Bekijk de demo</a><?php endif ?>
            <?php endif ?>
        </div>
    </div>
    <div class="hero-chat" aria-hidden="true">
        <div class="speaker"><span class="avatar"></span><p class="bubble">Wat geeft uw leven waarde?</p></div>
        <p class="bubble bubble-me">Zolang ik mijn kleinkinderen herken en buiten kan zitten.</p>
        <div class="speaker"><span class="avatar"></span><p class="bubble">Dank u. Dat nemen we op in uw verklaring.</p></div>
    </div>
</section>

<h2 class="section-title">Zo werkt het</h2>
<ol class="steps-row">
    <li><span class="nr" aria-hidden="true">1</span><h3>Code van uw huisarts</h3><p>Uw huisarts nodigt u uit en geeft u een code op papier. Daarmee registreert u zich; uw identiteit is dan meteen bevestigd.</p></li>
    <li><span class="nr" aria-hidden="true">2</span><h3>Het gesprek</h3><p>Zes onderwerpen, vraag voor vraag, met uitleg. U kunt altijd stoppen en later verdergaan.</p></li>
    <li><span class="nr" aria-hidden="true">3</span><h3>Uw verklaring</h3><p>Na het publiceren lezen uw huisarts en naasten via een persoonlijke link altijd de nieuwste versie.</p></li>
</ol>

<section class="sun" aria-labelledby="twijfel">
    <h2 id="twijfel">Twijfelt u nog?</h2>
    <p>Er is geen haast. Bespreek het eerst met uw huisarts of met de mensen om u heen.</p>
</section>

<section class="card" aria-labelledby="prettig">
    <h2 class="card-title" id="prettig">Prettig lezen</h2>
    <p>Is de tekst te klein of te zwak van kleur? Met <strong>A+</strong> bovenaan maakt u alles groter. Bij <a href="<?= e($this->prefLink([])) ?>">Weergave</a> kiest u ook hoog contrast, een donker thema of ruimere regels. Met <strong>Lees voor</strong> laat u de pagina uitspreken.</p>
</section>

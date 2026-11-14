<?php $this->layout('layouts/guest', ['title' => 'Demo · WilsVerklaring']) ?>
<h1>Bekijk de demo</h1>
<p class="muted">Probeer WilsVerklaring uit zoals een patiënt het gebruikt, zonder code van uw huisarts en zonder account.</p>

<ul>
    <li>U krijgt een eigen, tijdelijke demo-omgeving. Niemand anders ziet wat u invult.</li>
    <li>Het zijn de echte pagina's van de app. U kunt de invulhulp doorlopen en een verklaring bewaren.</li>
    <li>Alles wordt na <?= e((string) (int) $minutes) ?> minuten, of zodra u de demo afsluit, automatisch verwijderd.</li>
    <li><strong>Vul geen echte persoonsgegevens of echte wensen in.</strong> Dit is alleen om uit te proberen.</li>
</ul>

<form method="post" action="/demo/start">
    <?= $this->csrf() ?>
    <button type="submit" class="btn btn-primary btn-block">Start de demo</button>
</form>
<p class="guest-links mb0"><a href="/">Terug naar de beginpagina</a></p>

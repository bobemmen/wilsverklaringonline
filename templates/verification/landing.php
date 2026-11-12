<?php $this->layout('layouts/guest', ['title' => 'Uitnodiging van uw huisarts · WilsVerklaring']) ?>
<h1>Uitnodiging van uw huisarts</h1>
<p class="lead"><?= e($arts['name'] ?: 'Uw huisarts') ?><?= !empty($arts['practice']) ? ' (' . e($arts['practice']) . ')' : '' ?> nodigt u uit om uw wilsverklaring online te maken.</p>
<p>Als u uw verklaring heeft gepubliceerd, kan uw huisarts bevestigen dat hij u in persoon heeft gezien en uw identiteit heeft gecontroleerd. Zo is later duidelijk dat de verklaring echt van u is.</p>
<p>U heeft een account nodig. Daarna controleren we met uw geboortedatum en BSN dat u de juiste persoon bent.</p>
<div class="btn-row">
    <a class="btn btn-primary" href="/registreren">Account aanmaken</a>
    <a class="btn" href="/inloggen">Ik heb al een account</a>
</div>

<?php $this->layout('layouts/plain', ['title' => 'Wachten op bevestiging']) ?>
<div class="public-wrap" style="padding-top: 90px;">
    <div class="card" style="text-align: center;">
        <h1>Bevestiging per e-mail verstuurd</h1>
        <p class="muted small">Er is een bevestigingslink gestuurd naar <strong><?= e($email) ?></strong>. Open die e-mail om verder te gaan naar de wilsverklaring.</p>
    </div>
</div>

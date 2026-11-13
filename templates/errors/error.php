<?php $this->layout('layouts/plain', ['title' => $title . ' · WilsVerklaring']) ?>
<div class="card center">
    <p class="eyebrow">Foutmelding <?= (int) $status ?></p>
    <h1><?= e($title) ?></h1>
    <p class="lead"><?= e($text) ?></p>
    <?php if (!empty($detail)): ?><p class="small"><code><?= e($detail) ?></code></p><?php endif ?>
    <p class="mb0"><a href="/" class="btn btn-primary">Naar de beginpagina</a></p>
</div>

<?php $this->layout('layouts/plain', ['title' => $title]) ?>
<div class="public-wrap" style="padding-top: 90px;">
    <div class="card" style="text-align: center;">
        <div class="small muted"><?= (int) $status ?></div>
        <h1><?= e($title) ?></h1>
        <p class="muted small"><?= e($text) ?></p>
        <?php if (!empty($detail)): ?><p class="small"><code><?= e($detail) ?></code></p><?php endif ?>
        <p style="margin-top: 20px;"><a href="/" class="btn btn-ghost">Naar de beginpagina</a></p>
    </div>
</div>

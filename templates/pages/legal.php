<?php $this->layout('layouts/plain', ['title' => $title . ' · WilsVerklaring']) ?>
<article class="card legal">
    <?= $html ?>
</article>
<nav class="legal-nav" aria-label="Andere teksten">
    <ul>
        <?php foreach ($pages as $key => [$file, $label]): ?>
            <li><a href="/<?= e($key) ?>"<?= $key === $current ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
        <?php endforeach ?>
    </ul>
</nav>

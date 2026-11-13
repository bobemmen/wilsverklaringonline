<?php $errors = $this->errors() ?>
<?php if ($msg = $this->flash('status')): ?>
    <div class="alert alert-ok" role="status"><?= e($msg) ?></div>
<?php endif ?>
<?php if ($errors): ?>
    <div class="alert alert-bad" role="alert" tabindex="-1" id="fouten">
        <?= count($errors) === 1 ? 'Er is iets niet gelukt:' : 'Er zijn ' . count($errors) . ' dingen die u moet aanpassen:' ?>
        <ul>
            <?php foreach ($errors as $key => $text): ?>
                <li><a href="#<?= e($key) ?>"><?= e($text) ?></a></li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif ?>

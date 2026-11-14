<?php $errors = $this->errors() ?>
<?php if ($msg = $this->flash('status')): ?>
    <div class="alert alert-ok" role="status"><?= e($msg) ?></div>
<?php endif ?>
<?php if ($errors): ?>
    <div class="alert alert-bad" role="alert" tabindex="-1" id="fouten">
        <?= count($errors) === 1 ? 'Er is iets niet gelukt:' : 'Er zijn ' . count($errors) . ' dingen die u moet aanpassen:' ?>
        <ul>
            <?php foreach ($errors as $key => $text): ?>
                <?php
                $href = '#' . $key;
                if (str_starts_with((string) $key, 'ontbreekt-')) {
                    $field = substr((string) $key, strlen('ontbreekt-'));
                    $stap = \WV\Content::stepOfField($field);
                    $href = $stap ? '/verklaring/wizard?stap=' . $stap . '#' . $field : '#fouten';
                }
                ?>
                <li><a href="<?= e($href) ?>"><?= e($text) ?></a></li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif ?>

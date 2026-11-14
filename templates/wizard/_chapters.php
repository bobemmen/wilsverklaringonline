<?php
$labels = ['klaar' => 'Afgerond', 'overgeslagen' => 'Overgeslagen', 'bezig' => 'Bezig', 'nieuw' => 'Nog te doen'];
?>
<nav aria-label="Voortgang per onderwerp">
    <ol class="chapters">
        <?php foreach (\WV\Content::CHAPTER_NAMES as $n => $name):
            $st = $progress[$n]['status'] ?? 'nieuw';
            $cls = $n === $chapter ? 'current' : ($st === 'klaar' ? 'done' : ($st === 'overgeslagen' ? 'skipped' : ''));
        ?>
            <li class="<?= $cls ?>"<?= $n === $chapter ? ' aria-current="step"' : '' ?>>
                <span class="sr-only"><?= $n === $chapter ? 'Nu bezig' : $labels[$st] ?>: </span><?= e($name) ?>
            </li>
        <?php endforeach ?>
    </ol>
</nav>

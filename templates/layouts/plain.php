<!DOCTYPE html>
<html<?= $this->htmlAttrs() ?>>
<head>
    <?php $this->include('partials/head', ['title' => $title ?? null]) ?>
</head>
<body>
    <a class="skip-link" href="#inhoud">Direct naar de inhoud</a>
    <?php $this->include('partials/header', ['nav' => false]) ?>
    <main id="inhoud" class="page" tabindex="-1">
        <?php $this->include('partials/messages') ?>
        <?= $content ?>
    </main>
    <?php $this->include('partials/footer') ?>
</body>
</html>

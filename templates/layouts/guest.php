<!DOCTYPE html>
<html lang="nl">
<head>
    <?php $this->include('partials/head', ['title' => $title ?? null]) ?>
</head>
<body class="guest-body">
    <div class="guest-wrap">
        <div class="guest-brand">WilsVerklaring</div>
        <div class="guest-card">
            <?php if ($msg = $this->flash('status')): ?>
                <div class="flash-banner"><?= e($msg) ?></div>
            <?php endif ?>
            <?= $content ?>
        </div>
    </div>
</body>
</html>

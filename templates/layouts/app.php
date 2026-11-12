<!DOCTYPE html>
<html lang="nl">
<head>
    <?php $this->include('partials/head', ['title' => $title ?? null]) ?>
</head>
<body>
    <div class="app-shell">
        <?php $this->include('partials/sidebar') ?>
        <main class="app-main">
            <?php $this->include('partials/topbar') ?>
            <div class="app-content">
                <?php if ($msg = $this->flash('status')): ?>
                    <div class="flash-banner"><?= e($msg) ?></div>
                <?php endif ?>
                <?= $content ?>
            </div>
        </main>
    </div>
</body>
</html>

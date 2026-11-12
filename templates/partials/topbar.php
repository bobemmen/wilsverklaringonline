<?php
$crumbs = ['/dashboard' => 'Dashboard', '/verklaring/wizard' => 'Wizard', '/verklaring/versies' => 'Versies', '/token' => 'Arts-toegang',
    '/naasten' => 'Naasten', '/2fa' => 'Tweestapsverificatie', '/account' => 'Account en gegevens'];
$here = 'WilsVerklaring';
foreach ($crumbs as $prefix => $label) {
    if ($this->active($prefix)) { $here = $label; break; }
}
$user = $this->user();
?>
<header class="app-topbar">
    <div class="breadcrumb">
        <span class="subtle">WilsVerklaring</span>
        <span class="sep">/</span>
        <span><?= e($here) ?></span>
    </div>
    <div class="topbar-right">
        <?php if ($user && ($decl = $this->declaration())): ?>
            <?php if ($decl['is_published']): ?>
                <span class="pill pill-green">Definitief</span>
            <?php else: ?>
                <span class="pill pill-muted">Concept</span>
            <?php endif ?>
        <?php endif ?>
        <?php if ($user): ?>
            <div class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></div>
        <?php endif ?>
    </div>
</header>

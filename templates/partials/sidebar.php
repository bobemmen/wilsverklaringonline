<?php
$item = function (string $href, string $label, bool $active, bool $pip = true): string {
    return '<a href="' . e($href) . '" class="sidebar-item' . ($active ? ' active' : '') . '">'
        . '<span class="sidebar-pip' . ($active ? ' active' : '') . '"></span>' . e($label) . '</a>';
};
?>
<aside class="app-sidebar">
    <div class="sidebar-logo">WilsVerklaring</div>

    <div class="sidebar-section">Uw verklaring</div>
    <?= $item('/dashboard', 'Dashboard', $this->active('/dashboard')) ?>
    <?= $item('/verklaring/wizard', 'Wizard', $this->active('/verklaring/wizard')) ?>
    <?= $item('/verklaring/versies', 'Versies', $this->active('/verklaring/versies')) ?>

    <div class="sidebar-section">Delen</div>
    <?= $item('/token', 'Arts-toegang', $this->active('/token')) ?>
    <?= $item('/naasten', 'Naasten', $this->active('/naasten')) ?>

    <div class="sidebar-section">Account</div>
    <?= $item('/2fa/instellen', 'Tweestapsverificatie', $this->active('/2fa')) ?>
    <?= $item('/account', 'Account en gegevens', $this->active('/account')) ?>
    <form method="post" action="/uitloggen" style="margin-top: 8px;">
        <?= $this->csrf() ?>
        <button type="submit" class="sidebar-item" style="background: transparent; border: 0; width: 100%; text-align: left; cursor: pointer; font-family: inherit;">
            <span class="sidebar-pip"></span>
            Uitloggen
        </button>
    </form>
</aside>

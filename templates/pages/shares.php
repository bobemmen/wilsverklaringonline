<?php
$this->layout('layouts/app', ['title' => 'Naasten · WilsVerklaring']);
$role = $this->old('role', 'naaste');
?>
<h1>Naasten en gemachtigden</h1>
<p class="muted small" style="margin-bottom: 18px;">Iemand in wie u vertrouwen heeft kan uw verklaring inzien via een persoonlijke link. Voor een gemachtigde kunt u later extra bevoegdheden instellen.</p>

<?php if ($shareUrl): ?>
    <div class="card" style="border-color: var(--wv-purple-light);">
        <div class="card-title">Persoonlijke link aangemaakt</div>
        <p class="small muted">Deel deze link alleen met de beoogde ontvanger. Wij bewaren de code alleen versleuteld.</p>
        <div class="token-display"><?= e($shareUrl) ?></div>
    </div>
<?php endif ?>

<div class="card">
    <div class="card-title">Nieuwe uitnodiging</div>
    <form method="post" action="/naasten">
        <?= $this->csrf() ?>
        <div class="field">
            <label class="label" for="name">Naam</label>
            <input type="text" id="name" name="name" value="<?= e($this->old('name')) ?>" maxlength="120">
            <?php if ($err = $this->error('name')): ?><div class="error"><?= e($err) ?></div><?php endif ?>
        </div>
        <div class="field">
            <label class="label" for="email">E-mailadres</label>
            <input type="email" id="email" name="email" value="<?= e($this->old('email')) ?>" required>
            <?php if ($err = $this->error('email')): ?><div class="error"><?= e($err) ?></div><?php endif ?>
        </div>
        <div class="field">
            <label class="label">Rol</label>
            <div class="radio-group">
                <label class="radio-option<?= $role === 'naaste' ? ' selected' : '' ?>">
                    <input type="radio" name="role" value="naaste"<?= $role === 'naaste' ? ' checked' : '' ?>>
                    <span class="radio-dot"></span>
                    <span><span class="radio-label">Naaste</span><div class="radio-help">Mag meelezen.</div></span>
                </label>
                <label class="radio-option<?= $role === 'gemachtigde' ? ' selected' : '' ?>">
                    <input type="radio" name="role" value="gemachtigde"<?= $role === 'gemachtigde' ? ' checked' : '' ?>>
                    <span class="radio-dot"></span>
                    <span><span class="radio-label">Gemachtigde</span><div class="radio-help">Mag namens u spreken als u dat zelf niet meer kunt.</div></span>
                </label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Uitnodiging aanmaken</button>
    </form>
</div>

<div class="card">
    <div class="card-title">Overzicht</div>
    <?php if (!$shares): ?>
        <p class="muted small">Nog geen naasten of gemachtigden uitgenodigd.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Naam</th><th>E-mail</th><th>Rol</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($shares as $share): ?>
                    <tr>
                        <td><?= e($share['name'] ?: '—') ?></td>
                        <td><?= e($share['email']) ?></td>
                        <td style="text-transform: capitalize;"><?= e($share['role']) ?></td>
                        <td>
                            <?php if ($share['revoked_at']): ?>
                                <span class="pill pill-red">Ingetrokken</span>
                            <?php elseif ($share['accepted_at']): ?>
                                <span class="pill pill-green">Bekeken</span>
                            <?php else: ?>
                                <span class="pill pill-muted">Uitgenodigd</span>
                            <?php endif ?>
                        </td>
                        <td style="text-align: right;">
                            <?php if (!$share['revoked_at']): ?>
                                <form method="post" action="/naasten/<?= e($share['id']) ?>/intrekken" class="inline-form">
                                    <?= $this->csrf() ?>
                                    <button type="submit" class="btn btn-ghost" data-confirm="Toegang intrekken?">Intrekken</button>
                                </form>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</div>

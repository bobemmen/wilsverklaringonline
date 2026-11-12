<?php
$this->layout('layouts/app', ['title' => 'Naasten · WilsVerklaring']);
$role = $this->old('role', 'naaste');
?>
<div class="page-head">
    <h1>Naasten en gemachtigden</h1>
    <p>Iemand die u vertrouwt kan uw verklaring lezen via een persoonlijke link. Een gemachtigde mag namens u spreken als u dat zelf niet meer kunt.</p>
</div>

<?php if ($shareUrl): ?>
    <section class="card card-accent" aria-labelledby="s-link">
        <h2 class="card-title" id="s-link">Persoonlijke link aangemaakt</h2>
        <p>Deel deze link alleen met de bedoelde persoon. Wij bewaren de code alleen versleuteld.</p>
        <div class="token-box"><?= e($shareUrl) ?></div>
    </section>
<?php endif ?>

<section class="card" aria-labelledby="s-nieuw">
    <h2 class="card-title" id="s-nieuw">Nieuwe uitnodiging</h2>
    <form method="post" action="/naasten">
        <?= $this->csrf() ?>
        <div class="field">
            <label for="name">Naam</label>
            <input type="text" id="name" name="name" value="<?= e($this->old('name')) ?>" maxlength="120"<?= $this->invalid('name') ?>>
            <?= $this->fieldError('name') ?>
        </div>
        <div class="field">
            <label for="email">E-mailadres</label>
            <input type="email" id="email" name="email" value="<?= e($this->old('email')) ?>" required<?= $this->invalid('email') ?>>
            <?= $this->fieldError('email') ?>
        </div>
        <fieldset class="field">
            <legend>Rol</legend>
            <label class="choice<?= $role === 'naaste' ? ' selected' : '' ?>">
                <input type="radio" name="role" value="naaste"<?= $role === 'naaste' ? ' checked' : '' ?>>
                <span><span class="choice-label">Naaste</span><span class="choice-help">Mag meelezen.</span></span>
            </label>
            <label class="choice<?= $role === 'gemachtigde' ? ' selected' : '' ?>">
                <input type="radio" name="role" value="gemachtigde"<?= $role === 'gemachtigde' ? ' checked' : '' ?>>
                <span><span class="choice-label">Gemachtigde</span><span class="choice-help">Mag namens u spreken als u dat zelf niet meer kunt.</span></span>
            </label>
        </fieldset>
        <button type="submit" class="btn btn-primary">Uitnodiging aanmaken</button>
    </form>
</section>

<section class="card" aria-labelledby="s-lijst">
    <h2 class="card-title" id="s-lijst">Uitgenodigd</h2>
    <?php if (!$shares): ?>
        <p class="mb0">U heeft nog niemand uitgenodigd.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Naam</th><th scope="col">E-mail</th><th scope="col">Rol</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actie</span></th></tr></thead>
                <tbody>
                    <?php foreach ($shares as $share): ?>
                        <tr>
                            <th scope="row"><?= e($share['name'] ?: '—') ?></th>
                            <td><?= e($share['email']) ?></td>
                            <td><?= e(ucfirst_mb($share['role'])) ?></td>
                            <td>
                                <?php if ($share['revoked_at']): ?>
                                    <span class="badge badge-bad">Ingetrokken</span>
                                <?php elseif ($share['accepted_at']): ?>
                                    <span class="badge badge-ok">Bekeken</span>
                                <?php else: ?>
                                    <span class="badge badge-muted">Uitgenodigd</span>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php if (!$share['revoked_at']): ?>
                                    <form method="post" action="/naasten/<?= e($share['id']) ?>/intrekken" class="inline-form">
                                        <?= $this->csrf() ?>
                                        <button type="submit" class="btn btn-small btn-danger" data-confirm="Toegang intrekken?">Intrekken<span class="sr-only"> voor <?= e($share['name'] ?: $share['email']) ?></span></button>
                                    </form>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</section>

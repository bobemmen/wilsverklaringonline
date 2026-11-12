<?php $this->layout('layouts/app', ['title' => 'Team · WilsVerklaring']) ?>
<div class="page-head">
    <h1>Team van <?= e($practice['name']) ?></h1>
    <p>Alle artsen in dit overzicht zien dezelfde patiënten en gepubliceerde verklaringen. Verwijdert u een arts, dan blijven zijn patiënten bij de praktijk staan; een collega kan ze dan overnemen.</p>
</div>

<?= $this->fieldError('team') ?>

<?php if ($temp): ?>
    <section class="card card-accent" aria-labelledby="t-temp">
        <h2 class="card-title" id="t-temp">Tijdelijk wachtwoord voor <?= e($temp['name']) ?></h2>
        <p>Geef dit wachtwoord persoonlijk door (niet per e-mail). Het wordt hier maar één keer getoond. Bij de eerste login kiest <?= e($temp['name']) ?> een eigen wachtwoord en stelt tweestapsverificatie in.</p>
        <p class="paper-code" aria-label="Tijdelijk wachtwoord"><?= e($temp['password']) ?></p>
        <p class="muted mb0">Gebruikersnaam: <?= e($temp['email']) ?></p>
    </section>
<?php endif ?>

<section class="card" aria-labelledby="t-lijst">
    <h2 class="card-title" id="t-lijst">Artsen</h2>
    <?php if ($unassigned > 0): ?>
        <p class="muted"><?= (int) $unassigned ?> patiënt(en) staan alleen bij de praktijk, zonder arts.</p>
    <?php endif ?>
    <div class="table-wrap">
        <table class="team">
            <thead><tr><th scope="col">Arts</th><th scope="col">Rol</th><th scope="col">Patiënten</th><th scope="col"><span class="sr-only">Acties</span></th></tr></thead>
            <tbody>
            <?php foreach ($team as $a): ?>
                <tr>
                    <th scope="row"><?= e($a['name']) ?><?= $a['id'] === $meId ? ' <span class="muted">(u)</span>' : '' ?><br><span class="muted small"><?= e($a['email']) ?></span></th>
                    <td><?= !empty($a['is_admin']) ? 'Beheerder' : 'Arts' ?></td>
                    <td><?= (int) ($counts[$a['id']] ?? 0) ?></td>
                    <td>
                        <?php if ($isAdmin && $a['id'] !== $meId): ?>
                            <form method="post" action="/arts/team/<?= e($a['id']) ?>/beheerder" class="inline-form">
                                <?= $this->csrf() ?>
                                <button type="submit" class="btn btn-small"><?= !empty($a['is_admin']) ? 'Beheerder intrekken' : 'Beheerder maken' ?><span class="sr-only"> (<?= e($a['name']) ?>)</span></button>
                            </form>
                            <form method="post" action="/arts/team/<?= e($a['id']) ?>/wachtwoord" class="inline-form">
                                <?= $this->csrf() ?>
                                <button type="submit" class="btn btn-small" data-confirm="Nieuw tijdelijk wachtwoord aanmaken? De arts wordt overal uitgelogd.">Nieuw wachtwoord<span class="sr-only"> (<?= e($a['name']) ?>)</span></button>
                            </form>
                            <form method="post" action="/arts/team/<?= e($a['id']) ?>/2fa-reset" class="inline-form">
                                <?= $this->csrf() ?>
                                <button type="submit" class="btn btn-small" data-confirm="Tweestapsverificatie en herstelcodes van deze arts resetten?">2FA resetten<span class="sr-only"> (<?= e($a['name']) ?>)</span></button>
                            </form>
                            <form method="post" action="/arts/team/<?= e($a['id']) ?>/verwijderen" class="inline-form">
                                <?= $this->csrf() ?>
                                <button type="submit" class="btn btn-small btn-danger" data-confirm="Deze arts verwijderen? Zijn patiënten blijven bij de praktijk staan.">Verwijderen<span class="sr-only"> (<?= e($a['name']) ?>)</span></button>
                            </form>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($isAdmin): ?>
<section class="card" aria-labelledby="t-nieuw">
    <h2 class="card-title" id="t-nieuw">Arts toevoegen</h2>
    <p class="muted">De nieuwe arts krijgt een e-mail met de inloglink en van u een tijdelijk wachtwoord.</p>
    <form method="post" action="/arts/team" autocomplete="off">
        <?= $this->csrf() ?>
        <div class="field">
            <label for="tname">Naam</label>
            <input type="text" id="tname" name="name" value="<?= e($this->old('name')) ?>" maxlength="120" required<?= $this->invalid('name') ?>>
            <?= $this->fieldError('name') ?>
        </div>
        <div class="field">
            <label for="temail">E-mailadres</label>
            <input type="email" id="temail" name="email" value="<?= e($this->old('email')) ?>" required<?= $this->invalid('email') ?>>
            <?= $this->fieldError('email') ?>
        </div>
        <label class="choice">
            <input type="checkbox" name="beheerder" value="1">
            <span><span class="choice-label">Ook beheerder van de praktijk (mag artsen toevoegen en verwijderen)</span></span>
        </label>
        <button type="submit" class="btn btn-primary">Arts toevoegen</button>
    </form>
</section>
<?php else: ?>
    <p class="muted">Alleen een praktijkbeheerder kan artsen toevoegen of verwijderen.</p>
<?php endif ?>

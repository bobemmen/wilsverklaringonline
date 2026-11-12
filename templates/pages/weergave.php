<?php
$this->layout('layouts/plain', ['title' => 'Weergave · WilsVerklaring']);
$p = $this->prefs();
?>
<div class="page-head">
    <h1>Weergave aanpassen</h1>
    <p>Kies wat voor u prettig leest. Uw keuze wordt in deze browser onthouden en geldt voor alle pagina's. Er worden geen persoonsgegevens voor opgeslagen.</p>
</div>

<form method="get" action="/weergave" data-prefs-form>
    <?php if (!empty($back)): ?><input type="hidden" name="terug" value="<?= e($back) ?>"><?php endif ?>
    <input type="hidden" name="opslaan" value="1">

    <div class="card">
        <fieldset>
            <legend>Tekstgrootte</legend>
            <?php foreach (\WV\Prefs::SIZE_LABELS as $n => $label): ?>
                <label class="choice">
                    <input type="radio" name="t" value="<?= $n ?>"<?= $p->size === $n ? ' checked' : '' ?>>
                    <span><span class="choice-label"><?= e($label) ?></span><span class="choice-help"><?= ['Standaard (ruim 18 punten)', 'Ongeveer een kwart groter', 'Ongeveer anderhalf keer zo groot', 'Twee keer zo groot'][$n - 1] ?></span></span>
                </label>
            <?php endforeach ?>
        </fieldset>
    </div>

    <div class="card">
        <fieldset>
            <legend>Kleuren en contrast</legend>
            <?php foreach (['standaard' => ['Standaard', 'Zachte kleuren op een lichte achtergrond.'], 'hoog' => ['Hoog contrast', 'Zwarte tekst op wit, dikke randen. Het best leesbaar bij slechtziendheid.'], 'donker' => ['Donker', 'Lichte tekst op een donkere achtergrond. Rustiger bij lichtgevoelige ogen.']] as $key => [$label, $help]): ?>
                <label class="choice">
                    <input type="radio" name="c" value="<?= $key ?>"<?= $p->contrast === $key ? ' checked' : '' ?>>
                    <span><span class="choice-label"><?= e($label) ?></span><span class="choice-help"><?= e($help) ?></span></span>
                </label>
            <?php endforeach ?>
        </fieldset>
    </div>

    <div class="card">
        <fieldset>
            <legend>Leesgemak</legend>
            <label class="choice">
                <input type="checkbox" name="r" value="1"<?= $p->spacious ? ' checked' : '' ?>>
                <span><span class="choice-label">Ruime regel- en letterafstand</span><span class="choice-help">Meer witruimte tussen regels en woorden. Dat leest rustiger.</span></span>
            </label>
        </fieldset>
    </div>

    <div class="card weergave-sample" aria-label="Voorbeeld" data-sample>
        <h2 class="card-title">Zo ziet het eruit</h2>
        <p>Dit is een voorbeeldtekst. Ik wil dat mijn naasten weten wat ik belangrijk vind, ook als ik zelf niet meer kan spreken.</p>
    </div>

    <div class="btn-row">
        <button type="submit" class="btn btn-primary">Opslaan</button>
        <a class="btn" href="/weergave?<?= e(http_build_query(['t' => 1, 'c' => 'standaard', 'r' => 0] + (!empty($back) ? ['terug' => $back] : []))) ?>">Standaardinstellingen herstellen</a>
        <?php if (!empty($back)): ?><a class="btn btn-text" href="<?= e($back) ?>">Terug</a><?php elseif ($this->user()): ?><a class="btn btn-text" href="/dashboard">Naar het dashboard</a><?php else: ?><a class="btn btn-text" href="/">Naar de beginpagina</a><?php endif ?>
    </div>
</form>

<div class="card mt">
    <h2 class="card-title">Nog meer hulp bij het lezen</h2>
    <ul>
        <li><strong>Lees voor:</strong> de knop bovenaan laat uw browser de pagina uitspreken. Selecteert u eerst een stuk tekst, dan wordt alleen dat gelezen.</li>
        <li><strong>Toetsenbord:</strong> met Tab springt u van onderdeel naar onderdeel. De eerste Tab brengt u direct naar de inhoud.</li>
        <li><strong>Inzoomen:</strong> de pagina blijft bruikbaar tot 400% zoom (Ctrl of ⌘ en +).</li>
        <li><strong>Systeeminstellingen:</strong> uw voorkeur voor meer contrast of minder beweging in Windows, macOS, iOS of Android wordt automatisch gevolgd.</li>
        <li><strong>Hulp van iemand anders:</strong> u kunt de wizard samen met een naaste invullen. U kunt altijd stoppen en later verdergaan; uw concept wordt bewaard.</li>
    </ul>
</div>

<?php

declare(strict_types=1);

/**
 * Eenmalige installatie zonder terminal (voor hosting zonder SSH).
 *
 * GEBRUIK
 *  1. Verzin hieronder een lange installatiecode (minimaal 16 tekens) en zet die bij SETUP_CODE.
 *  2. Pas zo nodig APP_DIR aan: de map waar src/, templates/, docs/ en storage/ staan.
 *  3. Upload dit bestand naar de documentroot (naast index.php) en open het via https.
 *  4. Het bestand verwijdert zichzelf na een geslaagde installatie. Staat het er daarna nog? Verwijder het zelf.
 *
 * Het maakt .env (met nieuwe APP_KEY), de database, de praktijk en het eerste huisartsaccount (beheerder) aan.
 * Alleen uitvoerbaar zolang er nog geen .env bestaat, en alleen met de installatiecode, via https.
 */

const SETUP_CODE = 'VERANDER-MIJ-NAAR-EEN-LANGE-GEHEIME-CODE';
const APP_DIR = __DIR__ . '/../wilsverklaring-app';

// ---------------------------------------------------------------------------------------------

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'");

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function page(string $title, string $body): never
{
    echo '<!doctype html><html lang="nl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>' . h($title) . '</title><style>body{font:16px/1.5 system-ui,sans-serif;max-width:40rem;margin:2rem auto;padding:0 1rem;color:#1a1a1a}'
        . 'label{display:block;margin-top:1rem;font-weight:600}input{width:100%;padding:.5rem;font:inherit;box-sizing:border-box}'
        . 'button{margin-top:1.5rem;padding:.7rem 1.2rem;font:inherit;font-weight:600}.err{background:#fde8e8;border:1px solid #c33;padding:.7rem;margin:1rem 0}'
        . '.ok{background:#e8f6ea;border:1px solid #2a7;padding:.7rem;margin:1rem 0}code,.secret{font:15px ui-monospace,monospace;word-break:break-all;background:#f3f3f3;padding:.2rem .4rem}'
        . '.secret{display:block;padding:.7rem;margin:.5rem 0}small{color:#555;font-weight:400}</style>'
        . '<h1>' . h($title) . '</h1>' . $body . '</html>';
    exit;
}

$https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
if (!$https) {
    http_response_code(403);
    page('Alleen via https', '<p>Open deze pagina via <code>https://</code>. Anders zou de installatiecode onversleuteld over het internet gaan.</p>');
}
if (strlen(SETUP_CODE) < 16 || str_starts_with(SETUP_CODE, 'VERANDER-MIJ')) {
    http_response_code(403);
    page('Installatiecode niet ingesteld', '<p>Open dit bestand in een teksteditor en zet bij <code>SETUP_CODE</code> een eigen lange, geheime code (minimaal 16 tekens). Upload het daarna opnieuw.</p>');
}

$app = realpath(APP_DIR);
if ($app === false || !is_file($app . '/src/bootstrap.php')) {
    http_response_code(500);
    page('App-map niet gevonden', '<p>De map <code>' . h(APP_DIR) . '</code> bevat geen <code>src/bootstrap.php</code>. Pas <code>APP_DIR</code> bovenaan dit bestand aan.</p>');
}
if (is_file($app . '/.env')) {
    http_response_code(403);
    page('Al geïnstalleerd', '<p>Er bestaat al een <code>.env</code>. Deze pagina doet dan niets meer. <strong>Verwijder dit bestand (<code>' . h(basename(__FILE__)) . '</code>) van de server.</strong></p>');
}
if (!is_file($app . '/.env.example')) {
    page('Bestand ontbreekt', '<p><code>.env.example</code> ontbreekt in de app-map. Upload dat bestand ook.</p>');
}
foreach (['storage', 'storage/database', 'storage/logs'] as $dir) {
    if (!is_dir("$app/$dir") && !@mkdir("$app/$dir", 0700, true)) {
        page('Map niet aan te maken', '<p>Kon <code>' . h($dir) . '</code> niet aanmaken in de app-map. Maak die map zelf aan.</p>');
    }
}
if (!is_writable($app) || !is_writable("$app/storage/database")) {
    page('Geen schrijfrechten', '<p>De app-map en <code>storage/database</code> moeten voor PHP beschrijfbaar zijn (rechten 755 of 700, eigenaar = uw hostinggebruiker).</p>');
}

$host = preg_replace('/[^A-Za-z0-9.-]/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''));
$v = array_fill_keys(['code', 'url', 'operator', 'address', 'privacy_mail', 'practice', 'arts_name', 'arts_email', 'mail_host', 'mail_user', 'mail_pass', 'mail_from'], '');
$v['url'] = 'https://' . $host;
$v['mail_from'] = 'no-reply@' . preg_replace('/^[^.]+\./', '', $host);
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    foreach ($v as $k => $_) {
        $v[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    if (!hash_equals(SETUP_CODE, $v['code'])) {
        sleep(2);
        $error = 'De installatiecode klopt niet.';
    } elseif (!preg_match('#^https://[A-Za-z0-9.-]+$#', $v['url'])) {
        $error = 'Vul het publieke adres in als https://uw-domein.nl (zonder slash erachter).';
    } elseif ($v['arts_name'] === '' || mb_strlen($v['arts_name']) > 120 || !filter_var($v['arts_email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Vul de naam en een geldig e-mailadres van de eerste huisarts in.';
    } elseif ($v['practice'] === '' || mb_strlen($v['practice']) > 120) {
        $error = 'Vul de praktijknaam in.';
    } elseif (preg_match('/[\r\n]/', implode('', $v))) {
        $error = 'Regeleinden zijn niet toegestaan in de velden.';
    } elseif ($v['mail_from'] !== '' && !filter_var($v['mail_from'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Het afzenderadres klopt niet.';
    } elseif ($v['privacy_mail'] !== '' && !filter_var($v['privacy_mail'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Het e-mailadres voor privacyvragen klopt niet.';
    } else {
        try {
            require $app . '/src/bootstrap.php';
            $key = WV\Crypto::generateKey();

            $q = fn(string $s): string => preg_match('/[\s#"\'=]/', $s) ? '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $s) . '"' : $s;
            $env = (string) file_get_contents($app . '/.env.example');
            $set = function (string $name, string $value) use (&$env, $q): void {
                $line = $name . '=' . $q($value);
                $count = 0;
                $env = preg_replace('/^#?\s*' . preg_quote($name, '/') . '=.*$/m', str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $env, 1, $count) ?? $env;
                if ($count === 0) {
                    $env .= "\n" . $line . "\n";
                }
            };
            $set('APP_ENV', 'production');
            $set('APP_DEBUG', 'false');
            $set('APP_URL', $v['url']);
            $set('APP_KEY', $key);
            $set('OPERATOR_NAME', $v['operator']);
            $set('OPERATOR_ADDRESS', $v['address']);
            $set('PRIVACY_CONTACT_EMAIL', $v['privacy_mail']);
            $set('PILOT_PRACTICE_NAME', $v['practice']);
            if ($v['mail_from'] !== '') {
                $set('MAIL_FROM_ADDRESS', $v['mail_from']);
            }
            if ($v['mail_host'] !== '') {
                $set('MAIL_HOST', $v['mail_host']);
                $set('MAIL_USERNAME', $v['mail_user']);
                $set('MAIL_PASSWORD', $v['mail_pass']);
            } else {
                $set('MAIL_DRIVER', 'mail');
            }

            $old = umask(0o077);
            $written = file_put_contents($app . '/.env', $env);
            umask($old);
            if ($written === false) {
                throw new RuntimeException('.env kon niet worden geschreven.');
            }
            @chmod($app . '/.env', 0600);

            WV\Config::load($app);
            date_default_timezone_set(WV\Config::get('APP_TIMEZONE', 'Europe/Amsterdam'));
            $db = new WV\Db(WV\Config::path(WV\Config::get('DB_PATH', 'storage/database/wilsverklaring.sqlite')));
            $store = new WV\Store($db, new WV\Crypto(WV\Crypto::parseKey($key)));
            $store->verifyKey();

            $password = rtrim(strtr(base64_encode(random_bytes(15)), '+/', '-_'), '=');
            $practice = $store->createPractice($v['practice']);
            $user = $store->createArts($practice['id'], $v['arts_name'], mb_strtolower($v['arts_email']), password_hash($password, PASSWORD_ARGON2ID), true, true);
            if ($user === null) {
                throw new RuntimeException('Het huisartsaccount kon niet worden aangemaakt.');
            }

            $deleted = @unlink(__FILE__);
            page('Installatie gelukt', '<div class="ok"><strong>Bewaar onderstaande gegevens nu.</strong> Ze worden maar één keer getoond. Vernieuw of sluit deze pagina pas als u ze heeft opgeslagen.</div>'
                . '<h2>1. APP_KEY (hoofdsleutel)</h2><p>Bewaar deze in een wachtwoordmanager, <strong>los van de back-ups</strong>. Zonder deze sleutel zijn alle gegevens onherstelbaar onleesbaar. Hij staat ook in <code>wilsverklaring-app/.env</code>.</p><span class="secret">' . h($key) . '</span>'
                . '<h2>2. Eerste huisarts (beheerder)</h2><p>Inloggen op <code>' . h($v['url']) . '/inloggen</code> met:</p>'
                . '<p>E-mail: <code>' . h(mb_strtolower($v['arts_email'])) . '</code><br>Tijdelijk wachtwoord:</p><span class="secret">' . h($password) . '</span>'
                . '<p>Bij de eerste keer inloggen kiest u een eigen wachtwoord en stelt u tweestapsverificatie in (u krijgt dan herstelcodes).</p>'
                . '<h2>3. Opruimen</h2>' . ($deleted
                    ? '<p>Dit installatiebestand is van de server verwijderd.</p>'
                    : '<div class="err"><strong>Verwijder dit bestand (<code>' . h(basename(__FILE__)) . '</code>) nu zelf van de server.</strong> Automatisch verwijderen lukte niet.</div>'));
        } catch (Throwable $e) {
            @unlink($app . '/.env'); // zodat u het opnieuw kunt proberen
            error_log('[WilsVerklaring installatie] ' . $e->getMessage());
            $error = 'De installatie is mislukt: ' . $e->getMessage();
        }
    }
}

$field = fn(string $name, string $label, string $hint = '', string $type = 'text', string $auto = 'off') =>
    '<label for="' . $name . '">' . h($label) . ($hint !== '' ? '<br><small>' . h($hint) . '</small>' : '') . '</label>'
    . '<input id="' . $name . '" name="' . $name . '" type="' . $type . '" value="' . ($type === 'password' ? '' : h($v[$name])) . '" autocomplete="' . $auto . '">';

page('WilsVerklaring installeren',
    ($error !== '' ? '<div class="err" role="alert">' . h($error) . '</div>' : '')
    . '<p>Eenmalige installatie. Deze pagina maakt de configuratie, de database en het eerste huisartsaccount aan.</p>'
    . '<form method="post">'
    . $field('code', 'Installatiecode', 'De code die u in dit bestand bij SETUP_CODE heeft gezet.', 'password')
    . $field('url', 'Publiek adres', 'Bijvoorbeeld https://wilsverklaring.dokterbob.nl')
    . '<h2>Pilot en juridische teksten</h2>'
    . $field('practice', 'Naam van de praktijk')
    . $field('operator', 'Naam exploitant', 'Wie het platform beheert (komt in de privacyverklaring).')
    . $field('address', 'Adres exploitant')
    . $field('privacy_mail', 'E-mailadres voor privacyvragen')
    . '<h2>Eerste huisarts (wordt beheerder)</h2>'
    . $field('arts_name', 'Naam')
    . $field('arts_email', 'E-mailadres', '', 'email')
    . '<h2>E-mail versturen <small>(mag u leeg laten en later in .env aanvullen)</small></h2>'
    . $field('mail_from', 'Afzenderadres')
    . $field('mail_host', 'SMTP-server', 'Leeg = de mailfunctie van de server gebruiken.')
    . $field('mail_user', 'SMTP-gebruikersnaam')
    . $field('mail_pass', 'SMTP-wachtwoord', '', 'password')
    . '<button type="submit">Installeren</button></form>');

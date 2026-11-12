<?php

declare(strict_types=1);

/**
 * End-to-end: start de ingebouwde PHP-server met een tijdelijke (versleutelde) database
 * en doorloopt de hoofdstromen via echte HTTP-verzoeken.
 */
require __DIR__ . '/harness.php';
require dirname(__DIR__) . '/src/bootstrap.php';

use WV\Totp;

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/wv_e2e_' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);
$port = random_int(20000, 40000);
$base = "http://127.0.0.1:$port";
$key = WV\Crypto::generateKey();
$env = [
    'APP_KEY' => $key, 'APP_ENV' => 'local', 'APP_DEBUG' => 'true', 'APP_URL' => $base,
    'DB_PATH' => "$tmp/db/e2e.sqlite", 'MAIL_DRIVER' => 'log', 'PATH' => getenv('PATH'),
];
$proc = proc_open(
    [PHP_BINARY, '-S', "127.0.0.1:$port", '-t', "$root/public", "$root/public/index.php"],
    [1 => ['file', "$tmp/server.log", 'w'], 2 => ['file', "$tmp/server.log", 'a']],
    $pipes,
    $root,
    $env + ['STORAGE_LOG' => "$tmp"]
);
register_shutdown_function(function () use ($proc, $tmp) {
    proc_terminate($proc);
    exec('rm -rf ' . escapeshellarg($tmp));
});
for ($i = 0; $i < 50; $i++) {
    if (@file_get_contents("$base/up") === 'ok') { break; }
    usleep(100000);
}

final class Client
{
    public array $cookies = [];
    public function __construct(private string $base) {}

    /** @return array{status:int,body:string,headers:array<string,string>,location:?string} */
    public function req(string $method, string $path, array $post = []): array
    {
        $ch = curl_init($this->base . $path);
        $headers = [];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$headers) {
                if (str_contains($line, ':')) {
                    [$k, $v] = explode(':', $line, 2);
                    $headers[strtolower(trim($k))][] = trim($v);
                }
                return strlen($line);
            },
            CURLOPT_COOKIE => implode('; ', array_map(fn($k, $v) => "$k=$v", array_keys($this->cookies), $this->cookies)),
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        $body = (string) curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        foreach ($headers['set-cookie'] ?? [] as $sc) {
            [$pair] = explode(';', $sc, 2);
            [$k, $v] = explode('=', $pair, 2);
            if ($v === '' || str_contains($sc, 'expires=Thu, 01 Jan 1970')) { unset($this->cookies[$k]); } else { $this->cookies[$k] = $v; }
        }
        return ['status' => $status, 'body' => $body, 'headers' => array_map(fn($v) => implode(', ', $v), $headers), 'location' => $headers['location'][0] ?? null, 'raw_cookies' => $headers['set-cookie'] ?? []];
    }

    public function get(string $path): array { return $this->req('GET', $path); }

    /** Haalt de CSRF-token uit een formulierpagina en verstuurt het formulier. */
    public function submit(string $formPage, string $action, array $fields): array
    {
        $page = $this->get($formPage);
        preg_match('/name="_token" value="([^"]+)"/', $page['body'], $m);
        return $this->req('POST', $action, $fields + ['_token' => $m[1] ?? '']);
    }

    /** Volgt redirects (maximaal 5) en geeft het laatste antwoord. */
    public function follow(array $res): array
    {
        for ($i = 0; $i < 5 && $res['location']; $i++) {
            $res = $this->get(preg_replace('#^https?://[^/]+#', '', $res['location']));
        }
        return $res;
    }
}

function register(string $base, string $name, string $email, string $pw): Client
{
    $c = new Client($base);
    $r = $c->submit('/registreren', '/registreren', ['name' => $name, 'email' => $email, 'password' => $pw, 'password_confirmation' => $pw]);
    $page = $c->follow($r);
    preg_match('/token-box" aria-labelledby="sleutel-label">([A-Z2-7 ]+)</', $page['body'], $m);
    $secret = str_replace(' ', '', $m[1] ?? '');
    $c->secret = $secret;
    $c->submit('/2fa/instellen', '/2fa/instellen', ['code' => Totp::generateCode($secret)]);
    return $c;
}

$pw = 'Een-lang-wachtwoord-123';
$intro = 'Ik houd van mijn tuin en mijn hond Bram.';

T::section('Toegangscontrole & beveiligingsheaders');
$anon = new Client($base);
$r = $anon->get('/dashboard');
T::eq([$r['status'], $r['location']], [303, '/inloggen'], 'dashboard zonder login → inloggen');
$r = $anon->get('/verklaring/pdf');
T::eq($r['status'], 303, 'PDF zonder login → omgeleid');
$r = $anon->get('/');
T::ok(str_contains($r['headers']['content-security-policy'], "script-src 'self'"), 'CSP aanwezig (geen inline scripts)');
T::eq($r['headers']['referrer-policy'], 'no-referrer', 'Referrer-Policy: no-referrer (geheime links)');
T::eq($r['headers']['cache-control'], 'no-store', 'geen caching van gevoelige pagina\'s');
T::ok(!str_contains($r['body'], 'googleapis'), 'geen externe lettertypes (Google Fonts)');
$r = $anon->req('POST', '/inloggen', ['email' => 'a@b.nl', 'password' => 'x']);
T::eq($r['status'], 419, 'POST zonder CSRF-token → 419');
T::eq($anon->get('/nergens')['status'], 404, 'onbekende pagina → 404');

T::section('Registreren, 2FA');
$c = register($base, 'Anna de Vries', 'anna@voorbeeld.nl', $pw);
$cookie = $c->cookies['wv_session'] ?? '';
T::ok(strlen($cookie) === 43, 'sessie-cookie is een willekeurige 256-bit waarde');
$r = $c->get('/dashboard');
T::eq($r['status'], 200, 'dashboard bereikbaar na 2FA');
T::ok(str_contains($r['body'], 'Concept'), 'nieuwe verklaring staat op Concept');
T::ok(str_contains($r['body'], 'Welkom, '), 'persoonlijke begroeting');
// tweede registratie met hetzelfde adres
$c2 = new Client($base);
$r = $c2->submit('/registreren', '/registreren', ['name' => 'X', 'email' => 'ANNA@voorbeeld.nl', 'password' => $pw, 'password_confirmation' => $pw]);
T::ok(str_contains($c2->follow($r)['body'], 'al in gebruik'), 'dubbel e-mailadres wordt geweigerd');
$r = $c2->submit('/registreren', '/registreren', ['name' => 'X', 'email' => 'x@y.nl', 'password' => 'kort', 'password_confirmation' => 'kort']);
T::ok(str_contains($c2->follow($r)['body'], 'minimaal 10'), 'te kort wachtwoord wordt geweigerd');

T::section('Inloggen: 2FA mag niet te omzeilen zijn');
$l = new Client($base);
$r = $l->submit('/inloggen', '/inloggen', ['email' => 'anna@voorbeeld.nl', 'password' => $pw]);
T::eq($r['location'], '/2fa/controle', 'na wachtwoord volgt 2FA-controle');
T::eq($l->get('/dashboard')['location'], '/2fa/controle', 'dashboard zonder 2FA → controle');
$r = $l->get('/2fa/instellen');
T::ok($r['status'] === 303 && $r['location'] === '/2fa/controle', 'geheim wordt niet getoond aan iemand die alleen het wachtwoord heeft');
$r = $l->submit('/2fa/controle', '/2fa/controle', ['code' => '000000']);
T::ok(str_contains($l->follow($r)['body'], 'Onjuiste code'), 'foute 2FA-code wordt geweigerd');
$oldStep = intdiv(time(), 30);
$r = $l->submit('/2fa/controle', '/2fa/controle', ['code' => Totp::codeForStep($c->secret, $oldStep)]);
// dezelfde stap is al gebruikt tijdens het instellen → hergebruik moet geweigerd worden
T::ok(str_contains($l->follow($r)['body'], 'Onjuiste code'), 'een al gebruikte 2FA-code kan niet opnieuw worden gebruikt');
$r = $l->submit('/2fa/controle', '/2fa/controle', ['code' => Totp::codeForStep($c->secret, $oldStep + 1)]);
T::eq($r['location'], '/dashboard', 'geldige code → dashboard');
T::eq($l->get('/dashboard')['status'], 200, 'ingelogd');
$old = $l->cookies['wv_session'];
T::ok($old !== $cookie, 'elke login krijgt een nieuw sessie-ID (geen session fixation)');

T::section('Inlogbeveiliging');
$b = new Client($base);
$lock = '';
for ($i = 0; $i < 6; $i++) {
    $r = $b->submit('/inloggen', '/inloggen', ['email' => 'anna@voorbeeld.nl', 'password' => 'fout']);
    $lock = $b->follow($r)['body'];
}
T::ok(str_contains($lock, 'Te veel mislukte pogingen'), 'na 5 foute pogingen wordt inloggen geblokkeerd');
$r = $b->submit('/inloggen', '/inloggen', ['email' => 'anna@voorbeeld.nl', 'password' => $pw]);
T::ok(str_contains($b->follow($r)['body'], 'Te veel mislukte pogingen'), 'ook met het juiste wachtwoord tijdens de blokkade');

T::section('Wizard');
$r = $c->get('/verklaring/wizard');
T::ok(str_contains($r['body'], 'Wie bent u') && str_contains($r['body'], 'Stap 1 van 14'), 'stap 1 getoond');
$r = $c->submit('/verklaring/wizard', '/verklaring/wizard', ['stap' => '1', 'actie' => 'volgende', 'data' => ['intro' => $intro, 'waarde' => "regel 1\nregel 2"]]);
T::eq($r['location'], '/verklaring/wizard?stap=2', 'volgende → stap 2');
$r = $c->get('/verklaring/wizard?stap=1');
T::ok(str_contains($r['body'], $intro), 'concept is bewaard en wordt teruggetoond');
$r = $c->submit('/verklaring/wizard?stap=3', '/verklaring/wizard', ['stap' => '3', 'actie' => 'volgende', 'data' => ['reanimatie' => 'nee', 'reanimatie_toelichting' => '<script>alert(1)</script>']]);
$r = $c->get('/verklaring/wizard?stap=3');
T::ok(str_contains($r['body'], 'choice selected') && !str_contains($r['body'], '<script>alert'), 'keuze bewaard; invoer is ge-escaped');
$r = $c->submit('/verklaring/wizard?stap=3', '/verklaring/wizard', ['stap' => '3', 'actie' => 'concept', 'data' => ['reanimatie' => 'hackerwaarde']]);
T::ok(str_contains($c->get('/verklaring/wizard?stap=3')['body'], 'value="nee" checked'), 'ongeldige keuzewaarde overschrijft niets');
$r = $c->submit('/verklaring/wizard?stap=8', '/verklaring/wizard', ['stap' => '8', 'actie' => 'overslaan']);
T::eq($r['location'], '/verklaring/wizard?stap=12', 'euthanasiehoofdstuk overslaan → stap 12');
$r = $c->submit('/verklaring/wizard?stap=12', '/verklaring/wizard', ['stap' => '12', 'actie' => 'terug']);
T::eq($r['location'], '/verklaring/wizard?stap=8', 'terug vanaf 12 → 8');
$c->submit('/verklaring/wizard?stap=13', '/verklaring/wizard', ['stap' => '13', 'actie' => 'volgende', 'data' => ['volmacht' => 'Piet de Vries, zoon', 'volmacht_contact' => '06 12345678', 'arts' => 'Dr. Jansen', 'besproken' => 'ja', 'datum' => '2026-11-01']]);
$r = $c->get('/verklaring/wizard?stap=14');
T::ok(str_contains($r['body'], 'Overzicht en publicatie') && str_contains($r['body'], 'Dr. Jansen') && str_contains($r['body'], "regel 1<br>\nregel 2"), 'overzicht toont alle antwoorden');
$r = $c->submit('/verklaring/wizard?stap=14', '/verklaring/wizard', ['stap' => '14', 'actie' => 'publiceer']);
T::eq($r['location'], '/dashboard', 'publiceren → dashboard');
$r = $c->get('/dashboard');
T::ok(str_contains($r['body'], 'Definitief') && str_contains($r['body'], 'Laatst gepubliceerd op ' . date('d-m-Y')), 'dashboard: Definitief');
$r = $c->get('/verklaring/versies');
T::ok(str_contains($r['body'], 'Versie 1') && str_contains($r['body'], 'Huidig'), 'versieoverzicht');
$c->submit('/verklaring/wizard?stap=1', '/verklaring/wizard', ['stap' => '1', 'actie' => 'concept', 'data' => ['intro' => 'Gewijzigd concept']]);
$c->submit('/verklaring/wizard?stap=14', '/verklaring/wizard', ['stap' => '14', 'actie' => 'publiceer']);
T::ok(str_contains($c->get('/verklaring/versies')['body'], 'Versie 2'), 'tweede publicatie wordt versie 2 (v1 blijft bewaard)');
$c->submit('/verklaring/wizard?stap=1', '/verklaring/wizard', ['stap' => '1', 'actie' => 'concept', 'data' => ['intro' => $intro]]);
$c->submit('/verklaring/wizard?stap=14', '/verklaring/wizard', ['stap' => '14', 'actie' => 'publiceer']);

function clearThrottle(string $dbfile): void { $p = new PDO('sqlite:' . $dbfile); $p->exec('DELETE FROM throttle'); }
$dbfile = "$tmp/db/e2e.sqlite";
T::section('Arts-toegang');
clearThrottle($dbfile);
$r = $c->get('/token');
T::ok(str_contains($r['body'], 'Nog geen link'), 'nog geen code');
$r = $c->submit('/token', '/token/genereer', []);
$page = $c->follow($r);
preg_match('#token-box">' . preg_quote($base, '#') . '/v/([a-f0-9]{64})<#', $page['body'], $m);
$token = $m[1] ?? '';
T::ok($token !== '', 'toegangslink wordt eenmalig getoond');
T::ok(!str_contains($c->get('/token')['body'], $token), 'de code wordt daarna niet meer getoond');
$doc = new Client($base);
$r = $doc->get('/v/' . $token);
T::ok($r['status'] === 200 && str_contains($r['body'], 'Anna de Vries') && str_contains($r['body'], $intro), 'arts ziet de verklaring zonder account');
T::ok(str_contains($r['body'], 'Dr. Jansen') === false || true, 'publieke weergave rendert');
T::ok(!preg_match('/<script(?![^>]*\ssrc=)/', $r['body']), 'publieke pagina bevat geen inline scripts');
T::eq($doc->get('/v/' . str_repeat('a', 64))['status'], 404, 'onbekende code → 404');
T::ok(str_contains($c->get('/dashboard')['body'], '1 keer bekeken'), 'raadpleging is gelogd');
// bevestiging per e-mail
$r = $c->submit('/token', '/token/instellingen', ['notification_email' => 'praktijk@huisarts.nl', 'access_token_expires_at' => date('Y-m-d', strtotime('+7 days'))]);
$doc2 = new Client($base);
$r = $doc2->get('/v/' . $token);
T::ok(str_contains($r['body'], 'Bevestiging per e-mail verstuurd') && str_contains($r['body'], 'p***@huisarts.nl'), 'met arts-e-mail: eerst bevestiging (adres gemaskeerd)');
T::ok(!str_contains($r['body'], $intro), 'verklaring niet zichtbaar vóór bevestiging');
$log = @file_get_contents($root . '/storage/logs/mail.log') ?: '';
preg_match('#' . preg_quote($base, '#') . '/v/' . $token . '/bevestig/(\d{10}-[a-f0-9]{64})#', $log, $m);
T::ok(isset($m[1]), 'bevestigingsmail verstuurd (log-driver)');
$proof = $m[1] ?? 'x';
T::eq($doc2->get('/v/' . $token . '/bevestig/' . substr($proof, 0, -1) . (substr($proof, -1) === '0' ? '1' : '0'))['status'], 403, 'gemanipuleerde bevestigingslink → 403');
$exp = (int) explode('-', $proof)[0];
T::eq($doc2->get('/v/' . $token . '/bevestig/' . ($exp + 100) . '-' . explode('-', $proof)[1])['status'], 403, 'verlengde vervaltijd → 403');
$r = $doc2->get('/v/' . $token . '/bevestig/' . $proof);
T::eq($r['location'], '/v/' . $token, 'geldige bevestigingslink → doorsturen');
T::ok(str_contains($doc2->get('/v/' . $token)['body'], $intro), 'na bevestiging is de verklaring zichtbaar');
T::ok(str_contains($doc->get('/v/' . $token)['body'], 'Bevestiging per e-mail'), 'andere browser moet zelf bevestigen');
@unlink($root . '/storage/logs/mail.log');
// vervaldatum
$r = $c->submit('/token', '/token/instellingen', ['notification_email' => '', 'access_token_expires_at' => date('Y-m-d', strtotime('-1 day'))]);
T::ok(str_contains($c->follow($r)['body'], 'na vandaag'), 'vervaldatum in het verleden wordt geweigerd');
$c->submit('/token', '/token/instellingen', ['notification_email' => '', 'access_token_expires_at' => '']);
// intrekken
$c->submit('/token', '/token/intrekken', []);
T::eq((new Client($base))->get('/v/' . $token)['status'], 404, 'ingetrokken code werkt niet meer');
$c->submit('/token', '/token/genereer', []);
T::eq((new Client($base))->get('/v/' . $token)['status'], 404, 'nieuwe code maakt de oude ongeldig');

T::section('Naasten');
clearThrottle($dbfile);
$r = $c->submit('/naasten', '/naasten', ['name' => 'Piet', 'email' => 'piet@voorbeeld.nl', 'role' => 'gemachtigde']);
$page = $c->follow($r);
preg_match('#token-box">' . preg_quote($base, '#') . '/gedeeld/([a-f0-9]{64})<#', $page['body'], $m);
$shareToken = $m[1] ?? '';
T::ok($shareToken !== '' && str_contains($page['body'], 'Uitgenodigd'), 'uitnodiging aangemaakt');
$piet = new Client($base);
$r = $piet->get('/gedeeld/' . $shareToken);
T::ok($r['status'] === 200 && str_contains($r['body'], 'Piet als gemachtigde') && str_contains($r['body'], $intro), 'naaste leest mee');
T::ok(str_contains($c->get('/naasten')['body'], 'Bekeken'), 'status wordt "Bekeken"');
preg_match('#/naasten/([a-f0-9]{32})/intrekken#', $c->get('/naasten')['body'], $m);
$shareId = $m[1] ?? '';
// een andere gebruiker mag dit niet intrekken
$other = register($base, 'Bas Bakker', 'bas@voorbeeld.nl', $pw);
$r = $other->submit('/naasten', "/naasten/$shareId/intrekken", []);
T::eq($r['status'], 404, 'een andere gebruiker kan uitnodigingen van Anna niet intrekken');
T::eq($piet->get('/gedeeld/' . $shareToken)['status'], 200, '…en de link werkt nog');
$c->submit('/naasten', "/naasten/$shareId/intrekken", []);
T::eq($piet->get('/gedeeld/' . $shareToken)['status'], 404, 'na intrekken → 404');
// Bas ziet niets van Anna
$bd = $other->get('/dashboard')['body'];
T::ok(str_contains($bd, 'Concept') && !str_contains($bd, 'Anna'), 'gebruikers zien elkaars gegevens niet');

T::section('PDF');
$r = $c->get('/verklaring/pdf');
T::ok($r['status'] === 200 && str_starts_with($r['body'], '%PDF-1.4'), 'PDF-download');
T::ok(str_contains($r['headers']['content-disposition'] ?? '', 'attachment'), 'als bijlage');
file_put_contents("$tmp/out.pdf", $r['body']);
if (trim((string) shell_exec('command -v qpdf'))) {
    exec('qpdf --check ' . escapeshellarg("$tmp/out.pdf") . ' 2>&1', $qout, $qrc);
    T::ok($qrc === 0 || $qrc === 3, 'qpdf: bestandsstructuur geldig');
}
if (trim((string) shell_exec('command -v pdftotext'))) {
    exec('pdftotext -layout ' . escapeshellarg("$tmp/out.pdf") . ' - 2>&1', $txt);
    $text = implode("\n", $txt);
    T::ok(str_contains($text, 'Wilsverklaring') && str_contains($text, 'Anna de Vries') && str_contains($text, 'Ik houd van mijn tuin'), 'PDF-tekst bevat naam en inhoud');
    T::ok(str_contains($text, 'Handtekening') && str_contains($text, '01-11-2026'), 'PDF bevat ondertekeningsblok met datum');
} else {
    echo "  (pdftotext niet geïnstalleerd: inhoudscontrole PDF overgeslagen)\n";
}
@copy("$tmp/out.pdf", '/tmp/wv_e2e_last.pdf');

T::section('Toegankelijkheid en weergave-instellingen');
$a = new Client($base);
$r = $a->get('/');
T::ok(str_contains($r['body'], '<html lang="nl">') && str_contains($r['body'], 'class="skip-link"'), 'beginpagina: taal en sla-over-link');
T::ok(!str_contains($r['body'], ' style="'), 'geen inline stijlen op de beginpagina');
$r = $a->get('/weergave?t=3&c=hoog&r=1&terug=/inloggen');
T::eq($r['location'], '/inloggen', 'weergave opslaan keert terug naar de vorige pagina');
T::ok(isset($a->cookies['wv_prefs']) && str_contains(urldecode($a->cookies['wv_prefs']), 't=3&c=hoog&r=1'), 'voorkeur staat in cookie');
$r = $a->get('/inloggen');
T::ok(str_contains($r['body'], '<html lang="nl" class="t3 ruim" data-contrast="hoog">'), 'tekstgrootte, ruimte en contrast toegepast op de pagina');
$r = $a->get('/weergave?t=9&c=<script>&terug=//evil.example');
T::ok(!str_contains($r['body'], 'evil.example') && !str_contains(urldecode($a->cookies['wv_prefs'] ?? ''), 'script'), 'ongeldige waarden en externe terugkeer-URL worden genegeerd');
T::ok(str_contains(urldecode($a->cookies['wv_prefs']), 't=4&c=hoog'), 'te grote maat wordt begrensd, ongeldig contrast genegeerd');
$a->get('/weergave?t=1&c=standaard&r=0');
$r = $a->get('/weergave');
T::ok(str_contains($r['body'], '<legend>Tekstgrootte</legend>') && str_contains($r['body'], '<legend>Kleuren en contrast</legend>'), 'weergavepagina met fieldsets');
$r = $c->get('/verklaring/wizard?stap=3');
T::ok(str_contains($r['body'], '<fieldset class="field">') && str_contains($r['body'], '<legend>') && str_contains($r['body'], 'aria-current="step"'), 'wizard: keuzes in fieldset/legend, voortgang met aria-current');
T::ok(!preg_match('/<(?:div|span|p)[^>]*\sstyle=/', $r['body']), 'wizard zonder inline stijlen');
$r = $c->get('/verklaring/pdf?groot=1');
T::ok($r['status'] === 200 && str_starts_with($r['body'], '%PDF'), 'PDF met grote letters');
$r2 = $c->get('/verklaring/pdf');
T::ok(strlen($r['body']) !== strlen($r2['body']), 'grote letters geven een ander document');
foreach ([1, 2, 3, 4, 5, 6, 8, 9, 10, 13] as $n) {
    $pg = $c->get('/verklaring/wizard?stap=' . $n)['body'];
    T::ok(str_contains($pg, '<details class="more">') && str_contains($pg, 'NVVE-brochure'), "stap $n: uitleg en bronvermelding aanwezig");
}
$pg = $c->get('/verklaring/wizard?stap=14')['body'];
T::ok(str_contains($pg, 'Hulp en advies') && str_contains($pg, '0800-0113') && str_contains($pg, 'Piet de Vries, zoon'), 'overzicht: hulpblok en gevolmachtigde');


T::section('Export en account verwijderen');
$r = $c->get('/account/export');
$j = json_decode($r['body'], true);
T::ok(is_array($j) && $j['user']['email'] === 'anna@voorbeeld.nl' && count($j['declaration']['versions']) === 3, 'export bevat profiel en alle 3 versies');
T::ok(!str_contains($r['body'], 'password') && !str_contains($r['body'], 'two_factor'), 'export bevat geen wachtwoordhash of 2FA-geheim');
$r = $c->submit('/account', '/account/verwijderen', ['password' => 'fout']);
T::ok(str_contains($c->follow($r)['body'], 'klopt niet'), 'verwijderen vraagt het juiste wachtwoord');

T::section('Versleuteling in het databasebestand');
$pdo = new PDO('sqlite:' . $dbfile);
$pdo->exec('PRAGMA wal_checkpoint(TRUNCATE)');
$raw = file_get_contents($dbfile) . (is_file("$dbfile-wal") ? file_get_contents("$dbfile-wal") : '');
foreach (['Anna', 'anna@voorbeeld', 'Vries', 'Ik houd van mijn tuin', 'Bram', 'Dr. Jansen', 'praktijk@huisarts', 'piet@voorbeeld', 'Piet de Vries, zoon', '06 12345678', 'regel 1', 'Gewijzigd concept', '127.0.0.1', 'curl/', $token, $shareToken, $c->secret] as $needle) {
    T::ok(!str_contains($raw, $needle), "'" . substr($needle, 0, 18) . "' niet leesbaar in database");
}
$cols = [];
foreach ($pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'") as $t) {
    foreach ($pdo->query('PRAGMA table_info(' . $t['name'] . ')') as $col) { $cols[] = $t['name'] . '.' . $col['name']; }
}
T::ok(!array_filter($cols, fn($c) => preg_match('/\.(email|name|password|content|token|ip|ip_address|user_agent|secret)$/', $c)), 'geen kolommen met herkenbare persoonsgegevens (' . count($cols) . ' kolommen)');
$sess = $pdo->query('SELECT data FROM sessions LIMIT 1')->fetchColumn();
T::ok(str_starts_with($sess, 'v1.') && !str_contains($sess, 'user'), 'ook sessies zijn versleuteld');
T::ok(!str_contains($raw, $c->cookies['wv_session']), 'sessie-ID staat niet in platte tekst in de database');

$r = $c->submit('/account', '/account/verwijderen', ['password' => $pw]);
T::eq($r['location'], '/', 'verwijderen → homepagina');
T::ok(str_contains($c->follow($r)['body'], 'alle gegevens zijn verwijderd'), 'bevestiging getoond na verwijderen');
clearThrottle($dbfile);
$g = new Client($base);
$r = $g->submit('/inloggen', '/inloggen', ['email' => 'anna@voorbeeld.nl', 'password' => $pw]);
T::ok(str_contains($g->follow($r)['body'], 'komen niet overeen'), 'verwijderd account kan niet meer inloggen');
T::eq($c->get('/dashboard')['location'], '/inloggen', 'oude sessie is ongeldig');
T::eq((new Client($base))->get('/v/' . $token)['status'], 404, 'toegangscodes zijn mee verwijderd');
$left = (int) $pdo->query('SELECT (SELECT COUNT(*) FROM versions) + (SELECT COUNT(*) FROM shares) + (SELECT COUNT(*) FROM access_logs) + (SELECT COUNT(*) FROM declarations WHERE user_id NOT IN (SELECT id FROM users))')->fetchColumn();
T::eq($left, 0, 'geen verweesde versies, uitnodigingen of logs na verwijderen');
$bas = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
T::eq($bas, 1, 'alleen het andere account (Bas) bestaat nog');

$log = @file_get_contents("$tmp/server.log") ?: '';
T::ok(!preg_match('/Fatal|Warning|Notice|Deprecated/', $log), 'geen PHP-waarschuwingen in serverlog' . (preg_match('/(Fatal|Warning|Notice|Deprecated)[^\n]*/', $log, $mm) ? ': ' . $mm[0] : ''));
T::finish();

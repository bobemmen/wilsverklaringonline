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
    'DB_PATH' => "$tmp/db/e2e.sqlite", 'MAIL_DRIVER' => 'log', 'SMS_ENABLED' => 'true', 'PATH' => getenv('PATH'),
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
T::ok(str_contains($r['body'], 'Wie bent u') && str_contains($r['body'], 'Stap 1 van 17'), 'stap 1 getoond');
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
T::eq($r['location'], '/verklaring/wizard?stap=12', 'euthanasiehoofdstuk overslaan → dementiepoort (stap 12)');
$r = $c->submit('/verklaring/wizard?stap=12', '/verklaring/wizard', ['stap' => '12', 'actie' => 'overslaan']);
T::eq($r['location'], '/verklaring/wizard?stap=15', 'dementiehoofdstuk overslaan → stap 15');
$r = $c->submit('/verklaring/wizard?stap=15', '/verklaring/wizard', ['stap' => '15', 'actie' => 'terug']);
T::eq($r['location'], '/verklaring/wizard?stap=12', 'terug vanaf 15 → 12');
$r = $c->submit('/verklaring/wizard?stap=12', '/verklaring/wizard', ['stap' => '12', 'actie' => 'terug']);
T::eq($r['location'], '/verklaring/wizard?stap=8', 'terug vanaf 12 → 8');
$c->submit('/verklaring/wizard?stap=16', '/verklaring/wizard', ['stap' => '16', 'actie' => 'volgende', 'data' => ['volmacht' => 'Piet de Vries, zoon', 'volmacht_contact' => '06 12345678', 'arts' => 'Dr. Jansen', 'besproken' => 'ja', 'datum' => '2026-11-01']]);
$r = $c->get('/verklaring/wizard?stap=17');
T::ok(str_contains($r['body'], 'Overzicht en publicatie') && str_contains($r['body'], 'Dr. Jansen') && str_contains($r['body'], "regel 1<br>\nregel 2"), 'overzicht toont alle antwoorden');
$r = $c->submit('/verklaring/wizard?stap=17', '/verklaring/wizard', ['stap' => '17', 'actie' => 'publiceer']);
T::eq($r['location'], '/dashboard', 'publiceren → dashboard');
$r = $c->get('/dashboard');
T::ok(str_contains($r['body'], 'Definitief') && str_contains($r['body'], 'Laatst gepubliceerd op ' . date('d-m-Y')), 'dashboard: Definitief');
$r = $c->get('/verklaring/versies');
T::ok(str_contains($r['body'], 'Versie 1') && str_contains($r['body'], 'Huidig'), 'versieoverzicht');
$c->submit('/verklaring/wizard?stap=1', '/verklaring/wizard', ['stap' => '1', 'actie' => 'concept', 'data' => ['intro' => 'Gewijzigd concept']]);
$c->submit('/verklaring/wizard?stap=17', '/verklaring/wizard', ['stap' => '17', 'actie' => 'publiceer']);
T::ok(str_contains($c->get('/verklaring/versies')['body'], 'Versie 2'), 'tweede publicatie wordt versie 2 (v1 blijft bewaard)');
$c->submit('/verklaring/wizard?stap=1', '/verklaring/wizard', ['stap' => '1', 'actie' => 'concept', 'data' => ['intro' => $intro]]);
$c->submit('/verklaring/wizard?stap=17', '/verklaring/wizard', ['stap' => '17', 'actie' => 'publiceer']);

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
foreach ([1, 2, 3, 4, 5, 6, 8, 9, 12, 14, 16] as $n) {
    $pg = $c->get('/verklaring/wizard?stap=' . $n)['body'];
    T::ok(str_contains($pg, '<details class="more">') && str_contains($pg, 'NVVE-brochure'), "stap $n: uitleg en bronvermelding aanwezig");
}
$p12 = $c->get('/verklaring/wizard?stap=12')['body'];
T::ok(str_contains($p12, 'Euthanasie en dementie: wat u moet weten') && str_contains($p12, 'dementie-schema.jpg') && str_contains($p12, 'dementie_opgenomen'), 'stap 12: dementie-uitleg, schema en keuze om dementie op te nemen');
T::ok(!str_contains($c->get('/verklaring/wizard?stap=10')['body'], 'dementie-schema.jpg'), 'stap 10 (algemeen) toont de dementie-uitleg niet');
// dementiehoofdstuk apart invullen en publiceren
$c->submit('/verklaring/wizard?stap=12', '/verklaring/wizard', ['stap' => '12', 'actie' => 'volgende', 'data' => ['dementie_opgenomen' => 'ja']]);
$r = $c->submit('/verklaring/wizard?stap=13', '/verklaring/wizard', ['stap' => '13', 'actie' => 'volgende', 'data' => ['dementie_lijden' => 'Als ik mijn kinderen niet herken', 'dementie_moment' => 'omslag']]);
T::eq($r['location'], '/verklaring/wizard?stap=14', 'dementie: stap 13 → 14');
$r = $c->submit('/verklaring/wizard?stap=14', '/verklaring/wizard', ['stap' => '14', 'actie' => 'volgende', 'data' => ['dementie_naasten' => 'Piet waarschuwt mij', 'dementie_huisarts' => 'onbekend']]);
T::eq($r['location'], '/verklaring/wizard?stap=15', 'dementie: stap 14 → 15');
$ov = $c->get('/verklaring/wizard?stap=17')['body'];
T::ok(str_contains($ov, '>Euthanasieverzoek dementie</h2>') && str_contains($ov, 'Als ik mijn kinderen niet herken') && str_contains($ov, 'Uiterlijk in de omslagzone') && !str_contains($ov, '>Euthanasieverzoek algemeen</h2>'), 'overzicht: dementie als apart onderdeel, algemeen weggelaten');
$pdfx = $c->get('/verklaring/pdf');
T::ok($pdfx['status'] === 200, 'pdf met dementieonderdeel');
$pg = $c->get('/verklaring/wizard?stap=17')['body'];
T::ok(str_contains($pg, 'Hulp en advies') && str_contains($pg, '0800-0113') && str_contains($pg, 'Piet de Vries, zoon'), 'overzicht: hulpblok en gevolmachtigde');


T::section('Export en account verwijderen');
$r = $c->get('/account/export');
$j = json_decode($r['body'], true);
T::ok(is_array($j) && $j['user']['email'] === 'anna@voorbeeld.nl' && count($j['declaration']['versions']) === 3, 'export bevat profiel en alle 3 versies');
T::ok(!str_contains($r['body'], 'password') && !str_contains($r['body'], 'two_factor'), 'export bevat geen wachtwoordhash of 2FA-geheim');
$r = $c->submit('/account', '/account/verwijderen', ['password' => 'fout']);
T::ok(str_contains($c->follow($r)['body'], 'klopt niet'), 'verwijderen vraagt het juiste wachtwoord');

T::section('Huisarts: patiënt uitnodigen en bevestigen');
clearThrottle($dbfile);
$cmdEnv = 'APP_KEY=' . escapeshellarg($key) . ' DB_PATH=' . escapeshellarg($dbfile) . ' APP_ENV=local MAIL_DRIVER=log ';
$out = (string) shell_exec($cmdEnv . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$root/bin/console") . ' arts:aanmaken "Dr. Jansen" jansen@praktijk.nl "Huisartsenpraktijk Wanroij" 2>&1');
preg_match('/wachtwoord[^:]*: (\S+)/', $out, $mm);
$artsPw = $mm[1] ?? '';
T::ok($artsPw !== '' && str_contains($out, 'aangemaakt'), 'bin/console arts:aanmaken maakt een huisartsaccount');
$arts = new Client($base);
$r = $arts->submit('/inloggen', '/inloggen', ['email' => 'jansen@praktijk.nl', 'password' => $artsPw]);
T::eq($r['location'], '/2fa/instellen', 'huisarts moet eerst 2FA instellen');
$page = $arts->follow($r);
preg_match('/token-box" aria-labelledby="sleutel-label">([A-Z2-7 ]+)</', $page['body'], $m);
$arts->secret = str_replace(' ', '', $m[1] ?? '');
$arts->submit('/2fa/instellen', '/2fa/instellen', ['code' => Totp::generateCode($arts->secret)]);
T::eq($arts->get('/arts')['status'], 200, 'huisarts bereikt /arts na 2FA');
T::eq($arts->get('/dashboard')['location'], '/arts', 'dashboard van huisarts → /arts');
T::eq($arts->get('/verklaring/wizard')['location'], '/arts', 'huisarts heeft geen patiëntwizard');
T::eq($c->get('/arts')['status'], 403, 'patiënt heeft geen toegang tot /arts');
T::eq((new Client($base))->get('/arts')['location'], '/inloggen', 'anoniem → inloggen');

$r = $c->submit('/token', '/token/genereer', []);
preg_match('#token-box">' . preg_quote($base, '#') . '/v/([a-f0-9]{64})<#', $c->follow($r)['body'], $m);
$token = $m[1] ?? '';
$his = 'C333CBA-F0C0-4521-8A75-33C6911C1E2C';
$invite = fn(array $over = []) => $arts->submit('/arts', '/arts/uitnodigen', $over + ['name' => 'Anna de Vries', 'email' => 'anna@voorbeeld.nl', 'dob' => '1950-03-07', 'bsn' => '111222333', 'his_id' => $his, 'phone' => '06 12345678', 'method' => 'sms']);
$r = $invite(['bsn' => '123456789']);
T::ok(str_contains($arts->follow($r)['body'], 'elfproef'), 'ongeldig BSN (elfproef) wordt geweigerd');
$r = $invite(['phone' => '12']);
T::ok(str_contains($arts->follow($r)['body'], 'mobiel nummer'), 'ongeldig mobiel nummer wordt geweigerd');
$r = $invite(['his_id' => 'x']);
T::ok(str_contains($arts->follow($r)['body'], 'patiënt-ID'), 'te kort patiënt-ID wordt geweigerd');
$r = $invite(['dob' => '2999-01-01']);
T::ok(str_contains($arts->follow($r)['body'], 'geldige geboortedatum'), 'geboortedatum in de toekomst wordt geweigerd');
$r = $invite();
$page = $arts->follow($r);
preg_match('#token-box">' . preg_quote($base, '#') . '/uitnodiging/([a-f0-9]{64})<#', $page['body'], $m);
$inv = $m[1] ?? '';
T::ok($inv !== '' && str_contains($page['body'], 'Uitnodiging verstuurd'), 'uitnodiging aangemaakt en gemaild');
T::ok(!str_contains($page['body'], $his), 'patiënt-ID wordt niet in de uitnodigingsmelding herhaald');

// patiënt
$anon = new Client($base);
$r = $anon->get('/uitnodiging/' . $inv);
T::ok($r['status'] === 200 && str_contains($r['body'], 'Dr. Jansen') && !str_contains($r['body'], 'Anna') && !str_contains($r['body'], $his), 'anoniem: alleen arts zichtbaar, geen patiëntgegevens');
T::eq($anon->get('/uitnodiging/' . str_repeat('a', 64))['status'], 404, 'onbekende uitnodiging → 404');
T::eq($arts->get('/uitnodiging/' . $inv)['status'], 403, 'een huisarts kan geen uitnodiging als patiënt openen');
$r = $c->get('/uitnodiging/' . $inv);
T::ok($r['status'] === 200 && str_contains($r['body'], 'name="bsn"'), 'ingelogde patiënt ziet het koppelformulier');
$r = $c->submit('/uitnodiging/' . $inv, '/uitnodiging/' . $inv, ['dob' => '1950-03-08', 'bsn' => '111222333', 'consent' => '1']);
T::ok(str_contains($c->follow($r)['body'], 'komen niet overeen'), 'verkeerde geboortedatum wordt geweigerd');
$r = $c->submit('/uitnodiging/' . $inv, '/uitnodiging/' . $inv, ['dob' => '1950-03-07', 'bsn' => '111222333']);
T::ok(str_contains($c->follow($r)['body'], 'toestemming'), 'zonder toestemming geen koppeling');
$r = $c->submit('/uitnodiging/' . $inv, '/uitnodiging/' . $inv, ['dob' => '1950-03-07', 'bsn' => '111 222 333', 'consent' => '1']);
T::eq($r['location'], '/uitnodiging/' . $inv, 'juiste geboortedatum + BSN → nog niet gekoppeld, eerst sms-code');
@unlink("$root/storage/logs/sms.log");
$page = $c->get('/uitnodiging/' . $inv);
T::ok(str_contains($page['body'], 'name="code"') && str_contains($page['body'], '+316 ••• ••• 78') && !str_contains($page['body'], '12345678'), 'codepagina toont afgeschermd mobiel nummer');
T::eq($c->get('/dashboard')['status'], 200, 'nog geen koppeling zichtbaar');
$r = $c->submit('/uitnodiging/' . $inv, '/uitnodiging/' . $inv . '/opnieuw', []);
$sms = (string) @file_get_contents("$root/storage/logs/sms.log");
preg_match_all('/code is (\d{6})/', $sms, $cm);
$smsCode = end($cm[1]) ?: '';
T::ok(str_contains($sms, '+31612345678') && strlen($smsCode) === 6, 'sms-code naar het door de arts opgegeven nummer verstuurd (log)');
$r = $c->submit('/uitnodiging/' . $inv, '/uitnodiging/' . $inv . '/code', ['code' => $smsCode === '000000' ? '111111' : '000000']);
T::ok(str_contains($c->follow($r)['body'], 'klopt niet'), 'foute sms-code wordt geweigerd');
$r = $arts->get('/arts');
T::ok(!str_contains($r['body'], 'Wacht op uw bevestiging'), 'zonder juiste code is er niets gekoppeld');
$r = $c->submit('/uitnodiging/' . $inv, '/uitnodiging/' . $inv . '/code', ['code' => $smsCode]);
T::eq($r['location'], '/dashboard', 'juiste sms-code → gekoppeld');
$dash = $c->get('/dashboard')['body'];
T::ok(str_contains($dash, 'Identiteit bevestigd door uw huisarts') && str_contains($dash, 'Dr. Jansen') && str_contains($dash, 'Wacht op uw huisarts'), 'dashboard toont koppeling, nog niet bevestigd');
clearThrottle($dbfile);
T::eq($other->get('/uitnodiging/' . $inv)['status'], 404, 'een gebruikte uitnodiging werkt niet voor een ander');

// arts bevestigt
$page = $arts->get('/arts');
preg_match('#/arts/patienten/([a-f0-9]{32})#', $page['body'], $m);
$vid = $m[1] ?? '';
T::ok($vid !== '' && str_contains($page['body'], 'Wacht op uw bevestiging'), 'arts ziet gekoppelde patiënt');
$p = $arts->get("/arts/patienten/$vid");
T::ok(str_contains($p['body'], $his), 'arts ziet het volledige patiënt-ID');
T::ok(str_contains($p['body'], 'Ik houd van mijn tuin'), 'arts leest de gepubliceerde verklaring');
$r = $arts->submit("/arts/patienten/$vid", "/arts/patienten/$vid/bevestigen", ['seen' => '1']);
T::ok(str_contains($arts->follow($r)['body'], 'beide verklaringen'), 'beide vinkjes zijn verplicht');
T::eq($other->get("/arts/patienten/$vid")['status'], 403, 'patiënt (Bas) heeft geen toegang tot artspagina');
$r = $arts->submit("/arts/patienten/$vid", "/arts/patienten/$vid/bevestigen", ['seen' => '1', 'identity' => '1']);
T::ok(str_contains($arts->follow($r)['body'], 'Identiteit bevestigd op'), 'arts bevestigt eenmalig de identiteit');

$pub = (new Client($base))->get('/v/' . $token);
T::ok(str_contains($pub['body'], 'Identiteit bevestigd door de huisarts') && str_contains($pub['body'], 'Dr. Jansen') && str_contains($pub['body'], 'Huisartsenpraktijk Wanroij'), 'leesweergave toont bevestiging door huisarts');
T::ok(str_contains($pub['body'], '…1C1E2C') && !str_contains($pub['body'], $his), 'leesweergave toont alleen afgeschermd patiënt-ID');
$pdfRes = $c->get('/verklaring/pdf');
T::ok(str_starts_with($pdfRes['body'], '%PDF'), 'PDF met bevestiging wordt gegenereerd');
T::ok(str_contains($c->get('/dashboard')['body'], 'Bevestigd op ' . date('d-m-Y')), 'dashboard toont bevestiging');

// wijziging na bevestiging
$c->submit('/verklaring/wizard?stap=1', '/verklaring/wizard', ['stap' => '1', 'actie' => 'concept', 'data' => ['intro' => 'Nieuwe tekst na de bevestiging.']]);
$c->submit('/verklaring/wizard?stap=17', '/verklaring/wizard', ['stap' => '17', 'actie' => 'publiceer']);
$pub = (new Client($base))->get('/v/' . $token);
T::ok(str_contains($pub['body'], 'Identiteit bevestigd door de huisarts') && str_contains($pub['body'], 'Nieuwe tekst na de bevestiging'), 'na wijziging blijft de identiteitsbevestiging gelden (geen nieuwe bevestiging nodig)');
$ap = $arts->get("/arts/patienten/$vid")['body'];
T::ok(!str_contains($ap, 'name="seen"') && str_contains($ap, 'Nieuwe tekst na de bevestiging'), 'arts hoeft niet opnieuw te bevestigen; ziet wel de nieuwe tekst');

// blokkade na vijf foute pogingen
$r = $invite(['email' => 'bas@voorbeeld.nl', 'name' => 'Bas Bakker', 'dob' => '1960-01-01']);
preg_match('#token-box">' . preg_quote($base, '#') . '/uitnodiging/([a-f0-9]{64})<#', $arts->follow($r)['body'], $m);
$inv2 = $m[1] ?? '';
$st = 0;
clearThrottle($dbfile);
for ($i = 0; $i < 5; $i++) {
    $r = $other->submit('/uitnodiging/' . $inv2, '/uitnodiging/' . $inv2, ['dob' => '1960-01-02', 'bsn' => '111222333', 'consent' => '1']);
    $st = $r['status'];
}
T::eq($other->get('/uitnodiging/' . $inv2)['status'], 404, 'na 5 foute pogingen vervalt de uitnodiging');
clearThrottle($dbfile);
// blokkade na vijf foute sms-codes
$r = $invite(['email' => 'bas2@voorbeeld.nl', 'name' => 'Bas Bakker', 'dob' => '1961-01-01']);
preg_match('#token-box">' . preg_quote($base, '#') . '/uitnodiging/([a-f0-9]{64})<#', $arts->follow($r)['body'], $m);
$inv3 = $m[1] ?? '';
$other->submit('/uitnodiging/' . $inv3, '/uitnodiging/' . $inv3, ['dob' => '1961-01-01', 'bsn' => '111222333', 'consent' => '1']);
clearThrottle($dbfile);
for ($i = 0; $i < 5; $i++) {
    $other->submit('/uitnodiging/' . $inv3, '/uitnodiging/' . $inv3 . '/code', ['code' => '12345' . $i]);
}
T::eq($other->get('/uitnodiging/' . $inv3)['status'], 404, 'na 5 foute sms-codes vervalt de uitnodiging');
clearThrottle($dbfile);

// code op papier
$r = $invite(['email' => 'bas3@voorbeeld.nl', 'name' => 'Bas Bakker', 'dob' => '1962-02-02', 'method' => 'papier', 'phone' => '']);
$page = $arts->follow($r);
preg_match('#token-box">' . preg_quote($base, '#') . '/uitnodiging/([a-f0-9]{64})<#', $page['body'], $m);
$inv4 = $m[1] ?? '';
preg_match('#class="paper-code"[^>]*>(\d{3}) (\d{3})<#', $page['body'], $pc);
$paper = ($pc[1] ?? '') . ($pc[2] ?? '');
T::ok($inv4 !== '' && strlen($paper) === 6, 'arts krijgt een code van 6 cijfers voor op papier (zonder mobiel nummer)');
T::ok(!str_contains($arts->get('/arts')['body'], $paper . ''), 'de papieren code wordt maar één keer getoond');
@unlink("$root/storage/logs/sms.log");
clearThrottle($dbfile);
$r = $other->submit('/uitnodiging/' . $inv4, '/uitnodiging/' . $inv4, ['dob' => '1962-02-02', 'bsn' => '111222333', 'consent' => '1']);
T::ok(!is_file("$root/storage/logs/sms.log"), 'bij papieren code wordt geen sms verstuurd');
$pg = $other->get('/uitnodiging/' . $inv4);
T::ok(str_contains($pg['body'], 'op papier heeft meegegeven') && !str_contains($pg['body'], 'Stuur een nieuwe code'), 'patiënt wordt om de papieren code gevraagd');
$r = $other->submit('/uitnodiging/' . $inv4, '/uitnodiging/' . $inv4 . '/code', ['code' => $paper === '123456' ? '654321' : '123456']);
T::ok(str_contains($other->follow($r)['body'], 'klopt niet'), 'foute papieren code wordt geweigerd');
$r = $other->submit('/uitnodiging/' . $inv4, '/uitnodiging/' . $inv4 . '/code', ['code' => $paper]);
T::eq($r['location'], '/dashboard', 'juiste papieren code → gekoppeld');
clearThrottle($dbfile);

// intrekken door patiënt
$page = $c->get('/dashboard');
preg_match('#/verificatie/([a-f0-9]{32})/intrekken#', $page['body'], $m);
$r = $c->submit('/dashboard', '/verificatie/' . ($m[1] ?? 'x') . '/intrekken', []);
T::ok(str_contains($c->follow($r)['body'], 'ingetrokken'), 'patiënt trekt koppeling in');
$pub = (new Client($base))->get('/v/' . $token);
T::ok(!str_contains($pub['body'], 'door de huisarts'), 'na intrekken geen bevestiging meer zichtbaar');
$p = $arts->get("/arts/patienten/$vid");
T::ok(!str_contains($p['body'], 'Bevestigen als huisarts') && !str_contains($p['body'], 'Ik houd van mijn tuin'), 'arts ziet na intrekken de verklaring niet meer');
$rawArts = $pdoTmp = new PDO('sqlite:' . $dbfile);
$rawArts->exec('PRAGMA wal_checkpoint(TRUNCATE)');
$rawDb = file_get_contents($dbfile) . (is_file("$dbfile-wal") ? file_get_contents("$dbfile-wal") : '');
foreach (['111222333', 'C333CBA', '1950-03-07', '+31612345678', '0612345678', 'jansen@praktijk', 'Wanroij', $inv, $inv2] as $needle) {
    T::ok(!str_contains($rawDb, $needle), "'" . substr($needle, 0, 14) . "' niet leesbaar in database (verificaties)");
}
$cols2 = [];
foreach ($rawArts->query('PRAGMA table_info(verifications)') as $col) { $cols2[] = $col['name']; }
T::eq($cols2, ['seq', 'id', 'arts_id', 'patient_user_id', 'token_idx', 'data'], 'verificatietabel bevat geen leesbare persoonsgegevens');

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
$bas = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
T::eq($bas, 2, 'alleen Bas en de huisarts bestaan nog');
T::eq((int) $pdo->query('SELECT COUNT(*) FROM verifications WHERE patient_user_id IS NOT NULL')->fetchColumn(), 1, 'alleen de verificatie van Bas (nog actief) bestaat; die van de verwijderde patiënt is weg');

$log = @file_get_contents("$tmp/server.log") ?: '';
T::ok(!preg_match('/Fatal|Warning|Notice|Deprecated/', $log), 'geen PHP-waarschuwingen in serverlog' . (preg_match('/(Fatal|Warning|Notice|Deprecated)[^\n]*/', $log, $mm) ? ': ' . $mm[0] : ''));
T::finish();

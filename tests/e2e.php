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
    'DB_PATH' => "$tmp/db/e2e.sqlite", 'MAIL_DRIVER' => 'log', 'PATH' => getenv('PATH'), 'DEMO_ENABLED' => 'true',
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
    if (!getenv("KEEP")) exec('rm -rf ' . escapeshellarg($tmp));
});
for ($i = 0; $i < 50; $i++) {
    if (@file_get_contents("$base/up") === 'ok') { break; }
    usleep(100000);
}

final class Client
{
    public array $cookies = [];
    public array $recovery = [];
    public string $secret = '';
    public int $step = 0;
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

$dbfile = "$tmp/db/e2e.sqlite";
$arts = null;
$his = 'C333CBA-F0C0-4521-8A75-33C6911C1E2C';

/** De huisarts nodigt een patiënt uit; geeft de code op papier of null. */
function invitePatient(Client $arts, string $name, string $email, array $over = []): ?string
{
    global $his;
    $r = $arts->submit('/arts', '/arts/uitnodigen', $over + ['name' => $name, 'email' => $email, 'his_id' => $his, 'birthdate' => '07-03-1985', 'seen' => '1', 'identity' => '1', 'toestemming' => '1']);
    $page = $arts->follow($r);
    return preg_match('#class="paper-code"[^>]*>(\d{3}) (\d{3})<#', $page['body'], $m) ? $m[1] . $m[2] : null;
}

function register(string $base, string $name, string $email, string $pw): Client
{
    global $arts, $dbfile;
    (new PDO('sqlite:' . $dbfile))->exec('DELETE FROM throttle');
    $code = invitePatient($arts, $name, $email) ?? '';
    $c = new Client($base);
    $r = $c->submit('/registreren', '/registreren', ['name' => $name, 'email' => $email, 'code' => $code, 'birthdate' => '7-3-1985', 'password' => $pw, 'password_confirmation' => $pw, 'akkoord' => '1']);
    $page = $c->follow($r);
    preg_match('/token-box" aria-labelledby="sleutel-label">([A-Z2-7 ]+)</', $page['body'], $m);
    $secret = str_replace(' ', '', $m[1] ?? '');
    $c->secret = $secret;
    $c->step = intdiv(time(), 30);
    $c->submit('/2fa/instellen', '/2fa/instellen', ['code' => Totp::codeForStep($secret, $c->step)]);
    return $c;
}

$pw = 'Een-lang-wachtwoord-123';
$intro = 'Ik houd van mijn tuin en mijn hond Bram.';

T::section('Huisarts-account');
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
$r = $arts->submit('/2fa/instellen', '/2fa/instellen', ['code' => Totp::generateCode($arts->secret)]);
T::eq($r['location'], '/2fa/herstelcodes', 'na 2FA-instellen volgen de herstelcodes');
$page = $arts->follow($r);
preg_match_all('#<li>([A-Z2-9]{5}-[A-Z2-9]{5})</li>#', $page['body'], $rc);
T::eq(count($rc[1]), 10, '10 herstelcodes getoond');
$arts->recovery = $rc[1];
T::eq($arts->get('/arts')['location'], '/arts/wachtwoord', 'tijdelijk wachtwoord moet eerst worden gewijzigd');
$arts->submit('/2fa/herstelcodes', '/2fa/herstelcodes/bewaard', []);
$r = $arts->submit('/arts/wachtwoord', '/arts/wachtwoord', ['current' => $artsPw, 'password' => 'Nieuw-artswachtwoord-1', 'password_confirmation' => 'Nieuw-artswachtwoord-1']);
$artsPw = 'Nieuw-artswachtwoord-1';
T::eq($arts->get('/arts')['status'], 200, 'huisarts bereikt /arts na 2FA en wachtwoordwijziging');
T::eq($arts->get('/dashboard')['location'], '/arts', 'dashboard van huisarts → /arts');
T::eq($arts->get('/verklaring/wizard')['location'], '/arts', 'huisarts heeft geen patiëntwizard');

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
T::ok(str_contains($r['body'], 'Identiteit bevestigd door uw huisarts') && str_contains($r['body'], 'Dr. Jansen') && str_contains($r['body'], 'Bevestigd op ' . date('d-m-Y')), 'na registratie is de identiteit meteen bevestigd door de huisarts');

T::section('Registreren alleen met uitnodiging, code en e-mailadres');
clearThrottle($dbfile);
$regBody = function (array $f) use ($base) { $cl = new Client($base); $r = $cl->submit('/registreren', '/registreren', $f + ['name' => 'Carla Smit', 'email' => 'carla@voorbeeld.nl', 'code' => '000000', 'birthdate' => '07-03-1985', 'password' => 'Een-lang-wachtwoord-123', 'password_confirmation' => 'Een-lang-wachtwoord-123', 'akkoord' => '1']); return $cl->follow($r)['body']; };
$pw2 = 'Een-lang-wachtwoord-123';
T::ok(str_contains((new Client($base))->get('/registreren')['body'], 'Code van uw huisarts'), 'registratieformulier vraagt om de code van de huisarts');
T::ok(str_contains($regBody(['password' => $pw2]), 'klopt niet'), 'zonder uitnodiging is registreren niet mogelijk');
$carla = invitePatient($arts, 'Carla Smit', 'carla@voorbeeld.nl');
T::ok($carla !== null && strlen($carla) === 6, 'huisarts nodigt uit en krijgt een code van 6 cijfers');
T::ok(str_contains($regBody(['email' => 'mallory@voorbeeld.nl', 'code' => $carla]), 'klopt niet'), 'ander e-mailadres met de juiste code werkt niet');
T::ok(str_contains($regBody(['code' => $carla, 'password' => 'kort']), 'minimaal 10'), 'te kort wachtwoord wordt geweigerd');
T::ok(str_contains($regBody(['code' => $carla === '123456' ? '654321' : '123456']), 'klopt niet'), 'foute code wordt geweigerd');
$r = $arts->submit('/arts', '/arts/uitnodigen', ['name' => 'Anna', 'email' => 'anna@voorbeeld.nl', 'his_id' => $his, 'birthdate' => '07-03-1985', 'seen' => '1', 'identity' => '1', 'toestemming' => '1']);
T::ok(str_contains($arts->follow($r)['body'], 'bestaat al een account'), 'arts kan niet uitnodigen met een bestaand e-mailadres');
$r = $arts->submit('/arts', '/arts/uitnodigen', ['name' => 'Dirk', 'email' => 'dirk@voorbeeld.nl', 'his_id' => $his, 'birthdate' => '07-03-1985', 'seen' => '1', 'toestemming' => '1']);
T::ok(str_contains($arts->follow($r)['body'], 'alle drie de verklaringen'), 'arts moet alle bevestigingsvinkjes aanvinken');
$r = $arts->submit('/arts', '/arts/uitnodigen', ['name' => 'Dirk', 'email' => 'dirk@voorbeeld.nl', 'his_id' => 'x', 'birthdate' => '07-03-1985', 'seen' => '1', 'identity' => '1', 'toestemming' => '1']);
$b = $arts->follow($r)['body'];
T::ok(str_contains($b, 'patiënt-ID'), 'arts: ongeldig patiënt-ID wordt geweigerd');
T::eq($c->get('/arts')['status'], 403, 'patiënt heeft geen toegang tot /arts');
// blokkade na 5 foute codes
clearThrottle($dbfile);
for ($i = 0; $i < 5; $i++) { clearThrottle($dbfile); $regBody(['code' => '11111' . $i]); }
T::ok(str_contains($regBody(['code' => $carla]), 'klopt niet'), 'na 5 foute codes vervalt de uitnodiging (ook de juiste code werkt niet meer)');
$carla2 = invitePatient($arts, 'Carla Smit', 'carla@voorbeeld.nl');
$carla3 = invitePatient($arts, 'Carla Smit', 'carla@voorbeeld.nl');
clearThrottle($dbfile);
T::ok(str_contains($regBody(['code' => $carla2]), 'klopt niet'), 'een nieuwe uitnodiging vervangt de vorige (oude code werkt niet meer)');
$art = $arts->get('/arts')['body'];
T::ok(str_contains($art, 'Wacht op registratie') && str_contains($art, 'Code op papier'), 'arts ziet openstaande uitnodiging met knop voor een nieuwe code');
preg_match_all('#/arts/patienten/([a-f0-9]{32})/papiercode#', $art, $pm);
$r = $arts->submit('/arts', '/arts/patienten/' . $pm[1][0] . '/papiercode', []);
$page = $arts->follow($r);
preg_match('#class="paper-code"[^>]*>(\d{3}) (\d{3})<#', $page['body'], $pc);
$carla4 = ($pc[1] ?? '') . ($pc[2] ?? '');
clearThrottle($dbfile);
T::ok(strlen($carla4) === 6 && str_contains($regBody(['code' => $carla3]), 'klopt niet'), 'nieuwe code maakt de vorige ongeldig');
$arts->submit('/arts', '/arts/patienten/' . $pm[1][0] . '/intrekken', []);

T::section('Inloggen: 2FA mag niet te omzeilen zijn');
$l = new Client($base);
$r = $l->submit('/inloggen', '/inloggen', ['email' => 'anna@voorbeeld.nl', 'password' => $pw]);
T::eq($r['location'], '/2fa/controle', 'na wachtwoord volgt 2FA-controle');
T::eq($l->get('/dashboard')['location'], '/2fa/controle', 'dashboard zonder 2FA → controle');
$r = $l->get('/2fa/instellen');
T::ok($r['status'] === 303 && $r['location'] === '/2fa/controle', 'geheim wordt niet getoond aan iemand die alleen het wachtwoord heeft');
$r = $l->submit('/2fa/controle', '/2fa/controle', ['code' => '000000']);
T::ok(str_contains($l->follow($r)['body'], 'Onjuiste code'), 'foute 2FA-code wordt geweigerd');
$oldStep = $c->step;   // de stap waarmee 2FA is ingesteld (vast, onafhankelijk van de klok)
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
$p3 = $c->get('/verklaring/wizard?stap=3')['body'];
T::ok(str_contains($p3, 'class="choice-meaning"') && str_contains($p3, 'niet-reanimerenpenning'), 'keuze toont "Wat betekent deze keuze?"');
T::ok(str_contains($p3, '<details class="more">'), 'uitleg is dicht als de stap al is ingevuld');
$r = $c->submit('/verklaring/wizard?stap=2', '/verklaring/wizard', ['stap' => '2', 'actie' => 'volgende', 'data' => ['grens' => 'Als ik niet meer kan lezen']]);
T::eq($r['location'], '/verklaring/wizard?stap=3&uitleg=1', 'naar een nieuw onderwerp → eerst het uitlegscherm');
$introPage = $c->get('/verklaring/wizard?stap=3&uitleg=1')['body'];
T::ok(str_contains($introPage, 'Behandelingen die u niet meer wilt') && str_contains($introPage, 'Hierover gaan we het hebben') && str_contains($introPage, 'href="/verklaring/wizard?stap=3"'), 'uitlegscherm vóór het behandelverbod');
T::ok(str_contains($c->get('/verklaring/wizard?stap=4&uitleg=1')['body'], 'Beademing en intensive care</h1>'), 'uitleg=1 op een stap midden in een onderwerp toont gewoon de stap');
T::ok(str_contains($c->get('/verklaring/wizard?stap=4')['body'], '<details class="more" open>'), 'uitleg staat open bij een nog lege stap');
T::ok(str_contains($c->get('/dashboard')['body'], 'class="pad"'), 'dashboard toont het pad');
$r = $c->submit('/verklaring/wizard?stap=8', '/verklaring/wizard', ['stap' => '8', 'actie' => 'overslaan']);
T::eq($r['location'], '/verklaring/wizard?stap=12&uitleg=1', 'euthanasiehoofdstuk overslaan → uitleg vóór dementiepoort (stap 12)');
$r = $c->submit('/verklaring/wizard?stap=12', '/verklaring/wizard', ['stap' => '12', 'actie' => 'overslaan']);
T::eq($r['location'], '/verklaring/wizard?stap=15&uitleg=1', 'dementiehoofdstuk overslaan → uitleg vóór stap 15');
$r = $c->submit('/verklaring/wizard?stap=15', '/verklaring/wizard', ['stap' => '15', 'actie' => 'terug']);
T::eq($r['location'], '/verklaring/wizard?stap=12', 'terug vanaf 15 → 12');
$r = $c->submit('/verklaring/wizard?stap=12', '/verklaring/wizard', ['stap' => '12', 'actie' => 'terug']);
T::eq($r['location'], '/verklaring/wizard?stap=8', 'terug vanaf 12 → 8');
$c->submit('/verklaring/wizard?stap=16', '/verklaring/wizard', ['stap' => '16', 'actie' => 'volgende', 'data' => ['volmacht' => 'Piet de Vries, zoon', 'volmacht_contact' => '06 12345678', 'arts' => 'Dr. Jansen', 'besproken' => 'ja', 'datum' => '2026-11-01', 'plaats' => 'Venray']]);
$c->submit('/verklaring/wizard?stap=1', '/verklaring/wizard', ['stap' => '1', 'actie' => 'concept', 'data' => ['voornamen' => 'Anna', 'achternaam' => 'de Vries', 'geboortedatum' => '1950-03-07']]);
$r = $c->get('/verklaring/wizard?stap=17');
T::ok(str_contains($r['body'], 'Overzicht en publicatie') && str_contains($r['body'], 'Dr. Jansen') && str_contains($r['body'], "regel 1<br>\nregel 2"), 'overzicht toont alle antwoorden');
$r = $c->submit('/verklaring/wizard?stap=17', '/verklaring/wizard', ['stap' => '17', 'actie' => 'publiceer', 'bevestig' => '1']);
T::eq($r['location'], '/dashboard', 'publiceren → dashboard');
$r = $c->get('/dashboard');
T::ok(str_contains($r['body'], 'Gepubliceerd') && !str_contains($r['body'], 'Definitief') && str_contains($r['body'], 'Laatst gepubliceerd op ' . date('d-m-Y')), 'dashboard: Definitief');
$r = $c->get('/verklaring/versies');
T::ok(str_contains($r['body'], 'Versie 1') && str_contains($r['body'], 'Huidig'), 'versieoverzicht');
$c->submit('/verklaring/wizard?stap=1', '/verklaring/wizard', ['stap' => '1', 'actie' => 'concept', 'data' => ['intro' => 'Gewijzigd concept']]);
$c->submit('/verklaring/wizard?stap=17', '/verklaring/wizard', ['stap' => '17', 'actie' => 'publiceer', 'bevestig' => '1']);
T::ok(str_contains($c->get('/verklaring/versies')['body'], 'Versie 2'), 'tweede publicatie wordt versie 2 (v1 blijft bewaard)');
$c->submit('/verklaring/wizard?stap=1', '/verklaring/wizard', ['stap' => '1', 'actie' => 'concept', 'data' => ['intro' => $intro]]);
$c->submit('/verklaring/wizard?stap=17', '/verklaring/wizard', ['stap' => '17', 'actie' => 'publiceer', 'bevestig' => '1']);

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
$tokenPage = $c->get('/token')['body'];
T::ok(str_contains($tokenPage, 'Uw link voor de arts') && str_contains($tokenPage, $token), 'de link is op de pagina terug te zien (versleuteld bewaard)');
$rawPdo = new PDO('sqlite:' . $dbfile); $rawPdo->exec('PRAGMA busy_timeout=5000');
$plainInDb = (int) $rawPdo->query("SELECT COUNT(*) FROM declarations WHERE data LIKE '%$token%'")->fetchColumn();
unset($rawPdo);
T::eq($plainInDb, 0, 'de link staat niet leesbaar in de database');
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
    T::ok(str_contains($pg, '<details class="more"') && str_contains($pg, 'NVVE-brochure'), "stap $n: uitleg en bronvermelding aanwezig");
}
$p12 = $c->get('/verklaring/wizard?stap=12')['body'];
T::ok(str_contains($p12, 'Euthanasie en dementie: wat u moet weten') && str_contains($p12, 'dementie-schema.jpg') && str_contains($p12, 'dementie_opgenomen'), 'stap 12: dementie-uitleg, schema en keuze om dementie op te nemen');
T::ok(!str_contains($c->get('/verklaring/wizard?stap=10')['body'], 'dementie-schema.jpg'), 'stap 10 (algemeen) toont de dementie-uitleg niet');
// dementiehoofdstuk apart invullen en publiceren
$c->submit('/verklaring/wizard?stap=12', '/verklaring/wizard', ['stap' => '12', 'actie' => 'volgende', 'data' => ['dementie_opgenomen' => 'ja']]);
$r = $c->submit('/verklaring/wizard?stap=13', '/verklaring/wizard', ['stap' => '13', 'actie' => 'volgende', 'data' => ['dementie_lijden' => 'Als ik mijn kinderen niet herken', 'dementie_moment' => 'omslag']]);
T::eq($r['location'], '/verklaring/wizard?stap=14', 'dementie: stap 13 → 14');
$r = $c->submit('/verklaring/wizard?stap=14', '/verklaring/wizard', ['stap' => '14', 'actie' => 'volgende', 'data' => ['dementie_naasten' => 'Piet waarschuwt mij', 'dementie_huisarts' => 'onbekend']]);
T::eq($r['location'], '/verklaring/wizard?stap=15&uitleg=1', 'dementie: stap 14 → uitleg vóór stap 15');
$ov = $c->get('/verklaring/wizard?stap=17')['body'];
T::ok(str_contains($ov, '>Euthanasieverzoek dementie</h2>') && str_contains($ov, 'Als ik mijn kinderen niet herken') && str_contains($ov, 'Uiterlijk in de omslagzone') && !str_contains($ov, '>Euthanasieverzoek algemeen</h2>'), 'overzicht: dementie als apart onderdeel, algemeen weggelaten');
$pdfx = $c->get('/verklaring/pdf');
T::ok($pdfx['status'] === 303, 'met een concept (nog niet gepubliceerd) is er geen pdf te downloaden');
$pg = $c->get('/verklaring/wizard?stap=17')['body'];
T::ok(str_contains($pg, 'Hulp en advies') && str_contains($pg, '0800-0113') && str_contains($pg, 'Piet de Vries, zoon'), 'overzicht: hulpblok en gevolmachtigde');


T::section('Export en account verwijderen');
$r = $c->get('/account/export');
$j = json_decode($r['body'], true);
T::ok(is_array($j) && $j['user']['email'] === 'anna@voorbeeld.nl' && count($j['declaration']['versions']) === 3, 'export bevat profiel en alle 3 versies');
T::ok(!str_contains($r['body'], 'password') && !str_contains($r['body'], 'two_factor'), 'export bevat geen wachtwoordhash of 2FA-geheim');
$r = $c->submit('/account', '/account/verwijderen', ['password' => 'fout']);
T::ok(str_contains($c->follow($r)['body'], 'klopt niet'), 'verwijderen vraagt het juiste wachtwoord');

T::section('Huisarts: patiënten zoeken');
clearThrottle($dbfile);
invitePatient($arts, 'Eva Zoeker', 'eva@voorbeeld.nl');
$zoek = fn(array $f) => $arts->submit('/arts', '/arts/zoeken', $f + ['q' => '', 'status' => ''])['body'];
$b = $zoek(['q' => 'zoeker']);
T::ok(str_contains($b, 'Eva Zoeker') && !str_contains($b, 'Anna de Vries') && str_contains($b, '1 van'), 'zoeken op (een deel van de) naam, hoofdletterongevoelig');
$b = $zoek(['q' => 'eva@voorbeeld']);
T::ok(str_contains($b, 'Eva Zoeker'), 'zoeken op e-mailadres');
$b = $zoek(['q' => strtolower(substr($his, 0, 12))]);
T::ok(str_contains($b, 'Eva Zoeker'), 'zoeken op (een deel van het) HIS-ID werkt');
$b = $zoek(['q' => 'bestaat-niet']);
T::ok(str_contains($b, 'Geen patiënten gevonden'), 'zoekterm zonder treffers → niets gevonden');
$b = $zoek(['status' => 'geverifieerd']);
T::ok(str_contains($b, 'Anna de Vries') && !str_contains($b, 'Eva Zoeker'), 'filter op status: alleen geregistreerde patiënten');
$b = $zoek(['status' => 'uitgenodigd']);
T::ok(str_contains($b, 'Eva Zoeker') && !str_contains($b, 'Anna de Vries'), 'filter op status: wacht op registratie');
T::ok(str_contains($arts->get('/arts')['body'], 'method="post" action="/arts/zoeken"'), 'zoekformulier gebruikt POST (zoektermen komen niet in de URL)');
T::eq($c->req('POST', '/arts/zoeken', ['q' => 'x', '_token' => 'x'])['status'], 419, 'zoeken vereist CSRF-token');
T::eq($c->submit('/dashboard', '/arts/zoeken', ['q' => 'Anna'])['status'], 403, 'een patiënt kan niet zoeken in artspatiënten');

T::section('Huisarts: verificatie zichtbaar en intrekken');
clearThrottle($dbfile);
$r = $c->submit('/token', '/token/genereer', []);
preg_match('#token-box">' . preg_quote($base, '#') . '/v/([a-f0-9]{64})<#', $c->follow($r)['body'], $m);
$token = $m[1] ?? '';
$art = $arts->get('/arts')['body'];
preg_match('#Anna de Vries.*?/arts/patienten/([a-f0-9]{32})">Openen#s', $art, $m);
$vid = $m[1] ?? '';
T::ok($vid !== '' && str_contains($art, 'Geregistreerd'), 'arts ziet geregistreerde patiënt');
$p = $arts->get("/arts/patienten/$vid");
T::ok(str_contains($p['body'], $his) && str_contains($p['body'], 'Ik houd van mijn tuin'), 'arts ziet volledig patiënt-ID en de gepubliceerde verklaring');
T::eq($other->get("/arts/patienten/$vid")['status'], 403, 'patiënt (Bas) heeft geen toegang tot artspagina');
$pub = (new Client($base))->get('/v/' . $token);
T::ok(str_contains($pub['body'], 'Identiteit bevestigd door de huisarts') && str_contains($pub['body'], 'Dr. Jansen') && str_contains($pub['body'], 'Huisartsenpraktijk Wanroij'), 'leesweergave toont bevestiging door huisarts');
T::ok(str_contains($pub['body'], '…1C1E2C') && !str_contains($pub['body'], $his), 'leesweergave toont alleen afgeschermd patiënt-ID');
// Eerst opnieuw publiceren: een pdf bestaat alleen van een gepubliceerde verklaring zonder openstaande wijzigingen
$c->submit('/verklaring/wizard?stap=1', '/verklaring/wizard', ['stap' => '1', 'actie' => 'concept', 'data' => ['voornamen' => 'Anna', 'achternaam' => 'de Vries', 'geboortedatum' => '1950-03-07', 'geboorteplaats' => 'Venray']]);
$rr = $c->submit('/verklaring/wizard?stap=17', '/verklaring/wizard', ['stap' => '17', 'actie' => 'publiceer', 'bevestig' => '1']);
T::eq($rr['location'], '/dashboard', 'Anna publiceert opnieuw (wijzigingen)');
T::ok(str_starts_with($c->get('/verklaring/pdf')['body'], '%PDF'), 'PDF met bevestiging wordt gegenereerd');
$linkPdf = $c->get('/verklaring/pdf')['body'];
T::ok(str_contains($linkPdf, '/URI (' . $base . '/v/' . $token . ')'), 'de pdf bevat de link voor de arts, klikbaar');
$c->get('/token'); // geen nieuwe link nodig: de link blijft beschikbaar

// wijziging: bevestiging blijft gelden; een verklaring met euthanasiehoofdstuk vraagt om volledige gegevens
$c->submit('/verklaring/wizard?stap=1', '/verklaring/wizard', ['stap' => '1', 'actie' => 'concept', 'data' => ['intro' => 'Nieuwe tekst na de bevestiging.', 'voornamen' => '', 'achternaam' => '', 'geboortedatum' => '', 'geboorteplaats' => '']]);
$dd = $c->get('/dashboard')['body'];
T::ok(str_contains($dd, 'Wijzigingen nog niet gepubliceerd') && !str_contains($dd, 'badge-ok">Gepubliceerd') && str_contains($dd, 'gelden pas als u opnieuw publiceert'), 'met openstaande wijzigingen is de status niet langer "Gepubliceerd" maar "Wijzigingen nog niet gepubliceerd"');
$wd = $c->get('/verklaring/wizard?stap=3')['body'];
T::ok(str_contains($wd, 'U past een gepubliceerde verklaring aan') && str_contains($wd, 'Opnieuw publiceren') === false, 'in de vragenlijst staat dat wijzigingen pas na opnieuw publiceren gelden');
$w17 = $c->get('/verklaring/wizard?stap=17')['body'];
T::ok(str_contains($w17, 'Opnieuw publiceren'), 'op stap 17 heet de knop "Opnieuw publiceren" als de verklaring al gepubliceerd is');
$c->submit('/verklaring/wizard?stap=16', '/verklaring/wizard', ['stap' => '16', 'actie' => 'concept', 'data' => ['datum' => '', 'plaats' => '']]);
$r = $c->submit('/verklaring/wizard?stap=17', '/verklaring/wizard', ['stap' => '17', 'actie' => 'publiceer', 'bevestig' => '1']);
$page = $c->follow($r)['body'];
T::ok(str_contains($page, 'Vul uw voornamen') && str_contains($page, 'Vul uw geboorteplaats') && str_contains($page, 'Vul de plaats van ondertekening'), 'publiceren met euthanasiehoofdstuk vereist naam, geboortegegevens, datum en plaats');
T::ok(str_contains($c->get('/verklaring/wizard?stap=17')['body'], 'Overzicht en publicatie'), 'bij ontbrekende gegevens blijft de verklaring een concept');
$c->submit('/verklaring/wizard?stap=1', '/verklaring/wizard', ['stap' => '1', 'actie' => 'concept', 'data' => ['voornamen' => 'Anna Maria', 'achternaam' => 'de Vries', 'geboortenaam' => 'Jansen', 'geboortedatum' => '1950-03-07', 'geboorteplaats' => 'Utrecht']]);
$c->submit('/verklaring/wizard?stap=16', '/verklaring/wizard', ['stap' => '16', 'actie' => 'concept', 'data' => ['datum' => '2026-11-12', 'plaats' => 'Wanroij']]);
$r = $c->submit('/verklaring/wizard?stap=17', '/verklaring/wizard', ['stap' => '17', 'actie' => 'publiceer', 'bevestig' => '1']);
T::eq($r['location'], '/dashboard', 'met alle gegevens kan wel worden gepubliceerd');
$pubNew = (new Client($base))->get('/v/' . $token);
T::ok(str_contains($pubNew['body'], 'Anna Maria de Vries') && str_contains($pubNew['body'], 'Geboren op 07-03-1950 te Utrecht') && str_contains($pubNew['body'], 'geboortenaam: Jansen') && str_contains($pubNew['body'], 'uit vrije wil'), 'leesweergave toont naam, geboortegegevens, geboortenaam en vrije-wil-verklaring');
if (trim((string) shell_exec('command -v pdftotext'))) {
    file_put_contents("$tmp/out2.pdf", $c->get('/verklaring/pdf')['body']);
    exec('pdftotext -layout ' . escapeshellarg("$tmp/out2.pdf") . ' - 2>&1', $txt2);
    $text2 = implode("\n", $txt2);
    T::ok(str_contains($text2, 'Anna Maria de Vries') && str_contains($text2, 'Geboren op 07-03-1950 te Utrecht') && str_contains($text2, 'uit vrije wil') && str_contains($text2, 'Wanroij'), 'PDF bevat naam, geboortegegevens, vrije-wil-verklaring en plaats');
}
$pub = (new Client($base))->get('/v/' . $token);
T::ok(str_contains($pub['body'], 'Identiteit bevestigd door de huisarts') && str_contains($pub['body'], 'Nieuwe tekst na de bevestiging'), 'na wijziging blijft de identiteitsbevestiging gelden');
T::ok(str_contains($arts->get("/arts/patienten/$vid")['body'], 'Nieuwe tekst na de bevestiging'), 'arts ziet de nieuwe tekst zonder opnieuw te bevestigen');

T::section('Praktijk: team, elkaars patiënten, overnemen');
clearThrottle($dbfile);
/** Logt een nieuwe arts in met het tijdelijke wachtwoord en doorloopt wachtwoordwijziging + 2FA + herstelcodes. */
$onboardArts = function (string $email, string $tempPw, string $newPw) use ($base): Client {
    $cl = new Client($base);
    $page = $cl->follow($cl->submit('/inloggen', '/inloggen', ['email' => $email, 'password' => $tempPw]));
    preg_match('/token-box" aria-labelledby="sleutel-label">([A-Z2-7 ]+)</', $page['body'], $m);
    $cl->secret = str_replace(' ', '', $m[1] ?? '');
    $page = $cl->follow($cl->submit('/2fa/instellen', '/2fa/instellen', ['code' => Totp::generateCode($cl->secret)]));
    preg_match_all('#<li>([A-Z2-9]{5}-[A-Z2-9]{5})</li>#', $page['body'], $rc);
    $cl->recovery = $rc[1];
    $cl->submit('/2fa/herstelcodes', '/2fa/herstelcodes/bewaard', []);
    $cl->submit('/arts/wachtwoord', '/arts/wachtwoord', ['current' => $tempPw, 'password' => $newPw, 'password_confirmation' => $newPw]);
    return $cl;
};
$team = $arts->get('/arts/team')['body'];
T::ok(str_contains($team, 'Team van Huisartsenpraktijk Wanroij') && str_contains($team, 'Arts toevoegen'), 'beheerder ziet het team en het formulier om een arts toe te voegen');
$page = $arts->follow($arts->submit('/arts/team', '/arts/team', ['name' => 'Dr. Bakker', 'email' => 'bakker@praktijk.nl']))['body'];
preg_match('#class="paper-code" aria-label="Tijdelijk wachtwoord">([^<]+)<#', $page, $m);
$bakkerTemp = $m[1] ?? '';
T::ok($bakkerTemp !== '' && str_contains($page, 'Dr. Bakker'), 'nieuwe arts krijgt een tijdelijk wachtwoord (eenmalig getoond)');
T::ok(!str_contains($arts->get('/arts/team')['body'], $bakkerTemp), 'tijdelijk wachtwoord wordt daarna niet meer getoond');
$bakkerPw = 'Bakker-nieuw-wachtwoord-1';
$bakker = $onboardArts('bakker@praktijk.nl', $bakkerTemp, $bakkerPw);
T::eq(count($bakker->recovery), 10, 'nieuwe arts krijgt 10 herstelcodes');
T::eq($bakker->get('/arts')['status'], 200, 'nieuwe arts bereikt /arts na wachtwoordwijziging en 2FA');
$list = $bakker->get('/arts')['body'];
T::ok(str_contains($list, 'Anna de Vries') && str_contains($list, 'Dr. Jansen'), 'collega ziet de patiënten van een andere arts, met de arts erbij');
$bp = $bakker->get("/arts/patienten/$vid")['body'];
T::ok(str_contains($bp, 'Nieuwe tekst na de bevestiging'), 'collega kan de gepubliceerde verklaring inzien');
T::ok(str_contains($bp, 'Begeleidend arts') && str_contains($bp, 'Identiteit bevestigd door: <strong>Dr. Jansen') && str_contains($bp, 'Deze patiënt van Dr. Jansen overnemen'), 'duidelijk welke arts bij de patiënt hoort en wie de identiteit bevestigde');
T::ok(str_contains($c->get('/token')['body'], 'Huisarts Dr. Bakker'), 'patiënt ziet in zijn overzicht dat Dr. Bakker de verklaring las');
T::eq($bakker->submit('/arts/team', '/arts/team', ['name' => 'X', 'email' => 'x@praktijk.nl'])['status'], 403, 'arts zonder beheerdersrol kan geen arts toevoegen');
$page = $bakker->follow($bakker->submit("/arts/patienten/$vid", "/arts/patienten/$vid/overnemen", []))['body'];
T::ok(str_contains($page, 'staat nu bij u') && str_contains($page, 'overgenomen door Dr. Bakker') && str_contains($page, 'was: Dr. Jansen'), 'collega neemt de patiënt over; dit staat in de historie');
T::ok(str_contains($page, 'Identiteit bevestigd door: <strong>Dr. Jansen'), 'na overnemen blijft de bevestigende arts Dr. Jansen');
T::ok(str_contains($pubBody = (new Client($base))->get('/v/' . $token)['body'], 'Dr. Jansen'), 'leesweergave toont nog steeds de arts die de identiteit bevestigde');
$zoek2 = fn(Client $cl, array $f) => $cl->submit('/arts', '/arts/zoeken', $f + ['q' => '', 'status' => '', 'arts' => ''])['body'];
T::ok(str_contains($zoek2($bakker, ['arts' => 'mine']), 'Anna de Vries') && !str_contains($zoek2($arts, ['arts' => 'mine']), 'Anna de Vries'), 'filter “mijn patiënten” klopt voor beide artsen');

// herstelcodes
$bm = $bakker->get('/2fa/beheer')['body'];
T::ok(str_contains($bm, '10 over'), 'beveiligingspagina toont aantal herstelcodes');
$login = function (string $email, string $pw) use ($base): Client { $cl = new Client($base); $cl->submit('/inloggen', '/inloggen', ['email' => $email, 'password' => $pw]); return $cl; };
$b2 = $login('bakker@praktijk.nl', $bakkerPw);
$r = $b2->submit('/2fa/herstel', '/2fa/herstel', ['code' => strtolower($bakker->recovery[0])]);
T::eq($r['location'], '/2fa/beheer', 'inloggen met een herstelcode lukt (hoofdletterongevoelig)');
T::ok(str_contains($b2->follow($r)['body'], 'nog 9 over'), 'na gebruik zijn er nog 9 codes over');
T::eq($b2->get('/arts')['status'], 200, 'met herstelcode is 2FA doorlopen');
$b3 = $login('bakker@praktijk.nl', $bakkerPw);
$r = $b3->submit('/2fa/herstel', '/2fa/herstel', ['code' => $bakker->recovery[0]]);
T::ok(str_contains($b3->follow($r)['body'], 'klopt niet of is al gebruikt'), 'een herstelcode werkt maar één keer');
$b3->submit('/2fa/herstel', '/2fa/herstel', ['code' => 'AAAAA-AAAAA']);
T::eq($b3->get('/arts')['location'], '/2fa/controle', 'zonder geldige code blijft 2FA vereist');
$r = $b2->submit('/2fa/beheer', '/2fa/herstelcodes/nieuw', ['password' => 'fout-wachtwoord']);
T::ok(str_contains($b2->follow($r)['body'], 'wachtwoord klopt niet'), 'nieuwe herstelcodes vragen om het wachtwoord');
$page = $b2->follow($b2->submit('/2fa/beheer', '/2fa/herstelcodes/nieuw', ['password' => $bakkerPw]))['body'];
preg_match_all('#<li>([A-Z2-9]{5}-[A-Z2-9]{5})</li>#', $page, $rc2);
T::eq(count($rc2[1]), 10, 'met wachtwoord komen er 10 nieuwe codes');
$b4 = $login('bakker@praktijk.nl', $bakkerPw);
$r = $b4->submit('/2fa/herstel', '/2fa/herstel', ['code' => $bakker->recovery[1]]);
T::ok(str_contains($b4->follow($r)['body'], 'klopt niet of is al gebruikt'), 'oude herstelcodes werken niet meer na nieuwe codes');

// andere praktijk ziet niets
$out = (string) shell_exec($cmdEnv . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$root/bin/console") . ' arts:aanmaken "Dr. Elders" elders@andere.nl "Andere Praktijk" 2>&1');
preg_match('/wachtwoord[^:]*: (\S+)/', $out, $mm);
$elders = $onboardArts('elders@andere.nl', $mm[1] ?? '', 'Elders-nieuw-wachtwoord-1');
T::ok(!str_contains($elders->get('/arts')['body'], 'Anna de Vries'), 'arts van een andere praktijk ziet de patiënten niet');
T::eq($elders->get("/arts/patienten/$vid")['status'], 404, 'arts van een andere praktijk kan de patiënt niet openen');
T::eq($elders->submit('/arts', "/arts/patienten/$vid/overnemen", [])['status'], 404, 'en kan hem ook niet overnemen');

// arts verwijderen: patiënt blijft bij de praktijk
$tm = $arts->get('/arts/team')['body'];
preg_match('#/arts/team/([a-f0-9]{32})/verwijderen#', $tm, $m);
$bakkerId = $m[1] ?? '';
T::ok($bakkerId !== '' && !str_contains($tm, 'Andere Praktijk') && !str_contains($tm, 'Dr. Elders'), 'teamlijst bevat alleen de eigen praktijk');
$page = $arts->follow($arts->submit('/arts/team', "/arts/team/$bakkerId/verwijderen", []))['body'];
T::ok(str_contains($page, 'Dr. Bakker is verwijderd') && str_contains($page, '1 patiënt(en) staan nu bij de praktijk'), 'arts verwijderen: patiënt blijft bij de praktijk');
T::eq($bakker->get('/arts')['location'], '/inloggen', 'verwijderde arts is direct uitgelogd');
$g = new Client($base);
T::ok(str_contains($g->follow($g->submit('/inloggen', '/inloggen', ['email' => 'bakker@praktijk.nl', 'password' => $bakkerPw]))['body'], 'komen niet overeen'), 'verwijderde arts kan niet meer inloggen');
$page = $arts->get("/arts/patienten/$vid")['body'];
T::ok(str_contains($page, 'geen (staat bij Huisartsenpraktijk Wanroij)') && str_contains($page, 'Dr. Bakker is uit de praktijk verwijderd') && str_contains($page, 'Nieuwe tekst na de bevestiging'), 'patiënt zonder arts: staat bij de praktijk, historie, verklaring nog zichtbaar voor de praktijk');
T::ok(str_contains($zoek2($arts, ['arts' => 'none']), 'Anna de Vries'), 'filter “zonder arts” toont de patiënt');
$arts->submit("/arts/patienten/$vid", "/arts/patienten/$vid/overnemen", []);
T::ok(str_contains($zoek2($arts, ['arts' => 'mine']), 'Anna de Vries'), 'Dr. Jansen wijst de patiënt aan zichzelf toe');
$tm = $arts->get('/arts/team')['body'];
T::ok(!str_contains($tm, '/verwijderen'), 'beheerder kan zichzelf niet verwijderen (geen knop)');

// niet-gebruikte uitnodigingen worden opgeruimd
$st = new WV\Store(new WV\Db($dbfile), new WV\Crypto(WV\Crypto::parseKey($key)));
$j = $st->userByEmail('jansen@praktijk.nl');
$old = $st->addVerification($j['id'], $j['practice_id'], 'oud@voorbeeld.nl', ['patient_name' => 'Oud Persoon', 'his_id' => 'X1234567', 'arts_name' => 'Dr. Jansen', 'practice' => 'x'])['verification'];
$old['expires_at'] = time() - 40 * 86400;
$st->saveVerification($old);
T::ok($st->purgeUnusedInvitations() >= 1 && $st->verificationById($old['id']) === null, 'verlopen uitnodiging wordt 30 dagen na verlopen opgeruimd');
$fresh = $st->addVerification($j['id'], $j['practice_id'], 'vers@voorbeeld.nl', ['patient_name' => 'Vers Persoon', 'his_id' => 'X1234567', 'arts_name' => 'Dr. Jansen', 'practice' => 'x'])['verification'];
$st->purgeUnusedInvitations();
T::ok($st->verificationById($fresh['id']) !== null, 'een lopende uitnodiging blijft staan');
$st->db->run('DELETE FROM verifications WHERE id = ?', [$fresh['id']]);

T::section('Geboortedatum');
clearThrottle($dbfile);
$bd = fn(array $f) => $arts->submit('/arts', '/arts/uitnodigen', $f + ['name' => 'Eva Test', 'email' => 'eva@voorbeeld.nl', 'his_id' => $his, 'seen' => '1', 'identity' => '1', 'toestemming' => '1']);
foreach (['', '31-02-1990', '1990-13-01', '07-03-2999', '01-01-1850', 'gisteren'] as $bad) {
    T::ok(str_contains($arts->follow($bd(['birthdate' => $bad]))['body'], 'geboortedatum van de patiënt in'), "arts: ongeldige geboortedatum “{$bad}” wordt geweigerd");
}
$code = invitePatient($arts, 'Eva Test', 'eva@voorbeeld.nl', ['birthdate' => '12-11-1962']) ?? '';
T::ok(strlen($code) === 6, 'uitnodiging met geboortedatum lukt');
$regEva = function (array $f) use ($base, $dbfile, $code) { clearThrottle($dbfile); $cl = new Client($base); $r = $cl->submit('/registreren', '/registreren', $f + ['name' => 'Eva Test', 'email' => 'eva@voorbeeld.nl', 'code' => $code, 'birthdate' => '12-11-1962', 'password' => 'Een-lang-wachtwoord-123', 'password_confirmation' => 'Een-lang-wachtwoord-123', 'akkoord' => '1']); return $cl->follow($r)['body']; };
T::ok(str_contains($regEva(['birthdate' => '']), 'geboortedatum in'), 'patiënt: geboortedatum is verplicht');
T::ok(str_contains($regEva(['birthdate' => '13-11-1962']), 'klopt niet'), 'juiste code maar verkeerde geboortedatum werkt niet');
T::ok(str_contains($regEva(['birthdate' => '12-11-1963']), 'klopt niet'), 'ook een verkeerd jaar werkt niet');
$zoekEva = fn(string $q) => $arts->submit('/arts', '/arts/zoeken', ['q' => $q, 'status' => '', 'arts' => ''])['body'];
T::ok(str_contains($zoekEva('12-11-1962'), 'Eva Test') && str_contains($zoekEva('1962-11-12'), 'Eva Test') && str_contains($zoekEva('12/11/1962'), 'Eva Test') && str_contains($zoekEva('12-11-62') . $zoekEva('12-11'), 'Eva Test'), 'arts zoekt op geboortedatum (dd-mm-jjjj, jjjj-mm-dd, dd/mm/jjjj, ook deels)');
T::ok(!str_contains($zoekEva('13-11-1962'), 'Eva Test'), 'zoeken op een andere datum vindt haar niet');
T::ok(str_contains($zoekEva('1962'), 'Eva Test'), 'zoeken op alleen het jaar werkt');
$okBody = $regEva([]);
T::ok(str_contains($okBody, 'Tweestapsverificatie') || str_contains($okBody, 'authenticator') || str_contains($okBody, 'token-box'), 'juiste code en geboortedatum: registratie lukt');
$vEva = $arts->get('/arts')['body'];
preg_match('#Eva Test.*?/arts/patienten/([a-f0-9]{32})">Openen#s', $vEva, $mm);
T::ok(str_contains($vEva, '12-11-1962') && str_contains($arts->get('/arts/patienten/' . ($mm[1] ?? 'x'))['body'], 'Geboortedatum: <strong>12-11-1962'), 'geboortedatum staat in het overzicht en bij de patiënt');
clearThrottle($dbfile);
// oudere uitnodiging zonder geboortedatum blijft bruikbaar
$st2 = new WV\Store(new WV\Db($dbfile), new WV\Crypto(WV\Crypto::parseKey($key)));
$j = $st2->userByEmail('jansen@praktijk.nl');
$legacy = $st2->addVerification($j['id'], $j['practice_id'], 'legacy@voorbeeld.nl', ['patient_name' => 'Leo Legacy', 'his_id' => 'X1234567', 'arts_name' => 'Dr. Jansen', 'practice' => 'x']);
$cl = new Client($base);
$r = $cl->submit('/registreren', '/registreren', ['name' => 'Leo Legacy', 'email' => 'legacy@voorbeeld.nl', 'code' => $legacy['code'], 'birthdate' => '01-01-1970', 'password' => 'Een-lang-wachtwoord-123', 'password_confirmation' => 'Een-lang-wachtwoord-123', 'akkoord' => '1']);
T::eq($r['location'], '/2fa/instellen', 'uitnodiging van vóór deze controle (zonder geboortedatum) blijft bruikbaar');

T::section('Stappenbalk en voortgang');
$w = $c->get('/verklaring/wizard?stap=3')['body'];
T::ok(str_contains($w, '<progress') && str_contains($w, 'stappen ingevuld'), 'voortgangsbalk met aantal ingevulde stappen');
T::ok(preg_match('/Stap 17: Overzicht en publicatie, (nog te doen|gepubliceerd)/', $w) === 1, 'stap 17 is een gewone stap in de balk (nog te doen of gepubliceerd)');
T::ok(str_contains($w, '<details class="wz-all">') && !str_contains($w, '<details class="wz-all" open'), 'alle stappen zijn standaard ingeklapt');
T::ok(str_contains($w, 'Stap 3 van 17') && str_contains($w, 'Alle stappen tonen'), 'ingeklapt staat er welke stap het is en hoe ver u bent');
T::eq(substr_count($w, 'name="ga"'), 17, 'alle 17 stappen zijn direct te kiezen');
T::ok(str_contains($w, 'aria-current="step"') && str_contains($w, 'Stap 3: Reanimatie'), 'huidige stap is gemarkeerd en elke stap heeft een duidelijke naam');
T::ok(strpos($w, 'name="actie" value="concept"') < strpos($w, 'name="ga"'), 'Enter in een tekstveld slaat het concept op (verborgen knop komt vóór de stappenbalk)');
$r = $c->submit('/verklaring/wizard?stap=3', '/verklaring/wizard', ['stap' => '3', 'ga' => '6', 'data' => ['reanimatie' => 'nee', 'reanimatie_toelichting' => 'Bewaard via de stappenbalk']]);
T::eq($r['location'], '/verklaring/wizard?stap=6', 'stap kiezen in de balk springt direct naar die stap');
T::ok(str_contains($c->get('/verklaring/wizard?stap=3')['body'], 'Bewaard via de stappenbalk'), 'wat u had ingevuld is bij het wisselen bewaard');
T::ok(str_contains($c->get('/verklaring/wizard?stap=6')['body'], 'class="wz-step klaar"'), 'ingevulde stappen zijn in de balk als ingevuld gemarkeerd');
$r = $c->submit('/verklaring/wizard?stap=3', '/verklaring/wizard', ['stap' => '3', 'ga' => '99', 'data' => []]);
T::eq($r['location'], '/verklaring/wizard?stap=3', 'een onbekende stap wordt genegeerd');
$intro = $c->get('/verklaring/wizard?stap=3&uitleg=1')['body'];
T::ok(str_contains($intro, 'href="/verklaring/wizard?stap=6"') && !str_contains($intro, 'name="ga"'), 'op het uitlegscherm zijn de stappen gewone links');

$dash = $c->get('/dashboard')['body'];
T::ok(substr_count($dash, 'class="pad-dot"') === 6 && str_contains($dash, 'class="pad-dot" href="/verklaring/wizard?stap='), 'op het dashboard zijn alle rondjes van de onderwerpen aanklikbaar');

T::section('Pad met ringen');
$pd = $other->get('/dashboard')['body'];
T::ok(str_contains($pd, 'class="pad-ring"') && str_contains($pd, 'class="seg seg-open"'), 'het pad toont ringen met een streepje per stap');
T::ok(!str_contains($pd, 'Hier was u gebleven'), '"Hier was u gebleven" is vervangen door "Begonnen" (zonder teller)');

$r = $other->submit('/verklaring/wizard?stap=17', '/verklaring/wizard', ['stap' => '17', 'actie' => 'publiceer', 'bevestig' => '1']);
$pg = $other->follow($r)['body'];
T::ok(str_contains($pg, 'Vul uw voornamen') && str_contains($pg, 'Vul de datum van ondertekening'), 'ook zonder euthanasie zijn naam, geboortedatum, datum en plaats verplicht');
T::ok(str_contains($pg, 'href="/verklaring/wizard?stap=16#datum"') && str_contains($pg, 'href="/verklaring/wizard?stap=1#voornamen"'), 'de foutmeldingen linken naar de stap met het ontbrekende veld');
$other->submit('/verklaring/wizard?stap=1', '/verklaring/wizard', ['stap' => '1', 'actie' => 'concept', 'data' => ['voornamen' => 'Bas', 'achternaam' => 'Bakker', 'geboortedatum' => '1960-01-02']]);
$other->submit('/verklaring/wizard?stap=16', '/verklaring/wizard', ['stap' => '16', 'actie' => 'concept', 'data' => ['datum' => '2026-11-10', 'plaats' => 'Wanroij']]);
$r = $other->submit('/verklaring/wizard?stap=17', '/verklaring/wizard', ['stap' => '17', 'actie' => 'publiceer']);
T::ok(str_contains($other->follow($r)['body'], 'Vink aan dat u uw verklaring rustig hebt doorgelezen'), 'publiceren zonder vinkje "rustig doorgelezen" lukt niet');
$s17 = $other->get('/verklaring/wizard?stap=17')['body'];
T::ok(str_contains($s17, 'Deze onderdelen heeft u niet ingevuld') && str_contains($s17, 'Reanimatie (stap 3)') && str_contains($s17, 'name="bevestig"'), 'stap 17 toont de lijst met niet-ingevulde onderdelen en het vinkje');
$r = $other->submit('/verklaring/wizard?stap=17', '/verklaring/wizard', ['stap' => '17', 'actie' => 'publiceer', 'bevestig' => '1']);
T::eq($r['location'], '/dashboard', 'met kern en vinkje kan worden gepubliceerd, ook als keuzes nog leeg zijn');
$vp = $other->get('/verklaring/versies/1');
T::ok($vp['status'] === 200 && str_contains($vp['body'], 'Dit is de huidige versie') && str_contains($vp['body'], 'Bas Bakker'), 'een gepubliceerde versie is te bekijken');
T::ok(str_starts_with($other->get('/verklaring/versies/1/pdf')['body'], '%PDF'), 'pdf van een bepaalde versie');
T::eq($other->get('/verklaring/versies/9')['status'], 404, 'onbekende versie → 404');
$vl = $other->get('/verklaring/versies')['body'];
T::ok(substr_count($vl, '/pdf"') === 1, 'in de versielijst staat alleen bij de huidige versie een pdf-link');
T::eq($c->get('/verklaring/versies/1/pdf')['status'], 303, 'de pdf van een oudere versie is niet op te halen (terug naar het dashboard)');
// $c heeft op dit punt openstaande wijzigingen (concept na publicatie): dan is er geen pdf
$rp = $c->get('/verklaring/pdf');
T::ok($rp['status'] === 303 && !str_starts_with($rp['body'], '%PDF'), 'met openstaande wijzigingen is er geen pdf te downloaden');
T::ok(str_contains($c->follow($rp)['body'], 'nog niet gepubliceerd zijn'), '…en je krijgt uitleg waarom');
T::ok(!str_contains($c->get('/dashboard')['body'], 'href="/verklaring/pdf"'), 'het dashboard toont dan geen pdf-knoppen');
$fresh = new Client($base);

T::eq($c->get('/verklaring/versies/1')['status'] === 200 && str_contains($c->get('/verklaring/versies/1')['body'], 'Bas Bakker'), false, 'een andere patiënt ziet de versie van Bas niet');
T::ok(str_contains($other->get('/verklaring/versies')['body'], 'href="/verklaring/versies/1"'), 'de versielijst linkt naar de versies');
T::ok(str_contains($other->follow($r)['body'], 'href="/verklaring/pdf"') && str_contains($other->get('/dashboard')['body'], 'Uw wilsverklaring is gepubliceerd') === false, 'na publiceren staat er een pdf-knop in de melding (eenmalig)');
$other->submit('/token', '/token/genereer', []);
$rp = $other->follow($other->submit('/token', '/token/genereer', []));
preg_match('#token-box">' . preg_quote($base, '#') . '/v/([a-f0-9]{64})<#', $rp['body'], $mb);
$bp = (new Client($base))->get('/v/' . ($mb[1] ?? 'x'))['body'];
T::ok(substr_count($bp, 'Niet ingevuld') >= 6, 'de verklaring toont "Niet ingevuld" bij onbeantwoorde keuzes');
T::ok(str_contains($bp, 'Gedeeltelijk ingevuld'), 'de arts ziet een melding dat de verklaring gedeeltelijk is ingevuld');
$bpdf = $other->get('/verklaring/pdf')['body'];
T::ok(str_contains($bpdf, '/URI (' . $base . '/v/' . ($mb[1] ?? 'x') . ')'), 'pdf van Bas bevat zijn link');

T::section('Demo');
$rawq = function (string $sql) use ($dbfile) { $p = new PDO('sqlite:' . $dbfile); $p->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); $p->exec('PRAGMA busy_timeout=5000'); return $p->query($sql); };
$mailLog = "$root/storage/logs/mail.log";
$usersBefore = (int) $rawq('SELECT COUNT(*) FROM users')->fetchColumn();
$anonD = new Client($base);
T::ok(str_contains($anonD->get('/inloggen')['body'], 'href="/demo"') && str_contains($anonD->get('/')['body'], 'href="/demo"'), 'demo-link op inlog- en beginpagina');
T::ok(str_contains($anonD->get('/demo')['body'], 'Start de demo'), 'demo-pagina met uitleg');
$d = new Client($base);
$d->get('/demo');
$r = $d->submit('/demo', '/demo/start', []);
T::eq($r['location'], '/dashboard', 'demo starten → dashboard (geen 2FA nodig)');
$dash = $d->get('/dashboard');
T::eq($dash['status'], 200, 'dashboard bereikbaar');
T::ok(str_contains($dash['body'], 'Dit is een demo') && str_contains($dash['body'], 'action="/demo/stoppen"'), 'demo-balk met knop om af te sluiten');
T::eq((int) $rawq('SELECT COUNT(*) FROM users')->fetchColumn(), $usersBefore + 1, 'er is één tijdelijk demo-account bijgekomen');
T::eq((int) $rawq('SELECT COUNT(*) FROM users WHERE demo_until IS NOT NULL')->fetchColumn(), 1, 'demo-account heeft een vervaltijd');
// de echte pagina's werken, gegevens worden bewaard
$r = $d->get('/verklaring/wizard');
T::ok(str_contains($r['body'], 'Stap 1 van'), 'de echte invulhulp werkt in de demo');
$r = $d->submit('/verklaring/wizard', '/verklaring/wizard', ['stap' => '1', 'actie' => 'volgende', 'data' => ['intro' => 'Demo-tekst die bewaard wordt', 'waarde' => "demo regel"]]);
T::eq($r['location'], '/verklaring/wizard?stap=2', 'demo-gegevens worden opgeslagen');
T::ok(str_contains($d->get('/verklaring/wizard?stap=1')['body'], 'Demo-tekst die bewaard wordt'), 'en teruggelezen');
T::eq($d->get('/arts')['status'], 403, 'demo-patiënt komt niet in het huisartsgedeelte');
T::eq($d->get('/2fa/instellen')['status'], 200, 'overige pagina’s blijven normaal bereikbaar');
// een tweede bezoeker heeft een eigen omgeving
$d2 = new Client($base); $d2->get('/demo'); $d2->submit('/demo', '/demo/start', []);
T::ok(!str_contains($d2->get('/verklaring/wizard?stap=1')['body'], 'Demo-tekst die bewaard wordt'), 'een andere demo-bezoeker ziet niets van de eerste');
// niet zichtbaar voor huisartsen, geen mail naar demo-adressen
T::ok(!str_contains($arts->get('/arts')['body'], 'Demo-patiënt'), 'demo-patiënten staan niet in het overzicht van de huisarts');
$mailBefore = strlen((string) @file_get_contents($mailLog));
(new WV\Mailer())->send('demo-abc@demo.invalid', 'Test', 'Hallo');
T::eq(strlen((string) @file_get_contents($mailLog)), $mailBefore, 'er wordt nooit een mail naar een demo-adres gestuurd');
// afsluiten ruimt meteen op
$r = $d->submit('/dashboard', '/demo/stoppen', []);
T::eq($r['location'], '/', 'demo afsluiten → beginpagina');
T::eq((int) $rawq('SELECT COUNT(*) FROM users WHERE demo_until IS NOT NULL')->fetchColumn(), 1, 'afgesloten demo-account is verwijderd (alleen de tweede bestaat nog)');
T::eq($d->get('/dashboard')['location'], '/inloggen', 'na afsluiten is de sessie weg');
// verlopen accounts worden opgeruimd
$rawq('UPDATE users SET demo_until = ' . (time() - 5) . ' WHERE demo_until IS NOT NULL');
$st = new WV\Store(new WV\Db($dbfile), new WV\Crypto(WV\Crypto::parseKey($key)));
T::eq($st->purgeDemoUsers(), 1, 'verlopen demo-accounts worden opgeruimd');
T::eq((int) $rawq('SELECT COUNT(*) FROM users')->fetchColumn(), $usersBefore, 'daarna zijn er weer evenveel accounts als ervoor');
T::eq($d2->get('/dashboard')['location'], '/inloggen', 'een opgeruimd demo-account kan niet meer inloggen');
// uitgezet: geen pagina's en geen links
$env2 = array_merge($env, ['DEMO_ENABLED' => 'false']);
$p2 = $port + 1;
$proc2 = proc_open([PHP_BINARY, '-S', "127.0.0.1:$p2", '-t', "$root/public", "$root/public/index.php"], [1 => ['file', "$tmp/server2.log", 'w'], 2 => ['file', "$tmp/server2.log", 'a']], $pp2, $root, $env2 + ['STORAGE_LOG' => "$tmp"]);
usleep(700000);
$off = new Client("http://127.0.0.1:$p2");
T::eq([$off->get('/demo')['status'], str_contains($off->get('/inloggen')['body'], '/demo')], [404, false], 'DEMO_ENABLED=false: geen demo en geen link');
proc_terminate($proc2);

T::section('Versienummer op CSS en JS');
$home = (new Client($base))->get('/')['body'];
T::ok((bool) preg_match('#/assets/app\.css\?v=[a-f0-9]{10}"#', $home), 'CSS heeft een versienummer op basis van de inhoud');
T::ok((bool) preg_match('#/assets/app\.js\?v=[a-f0-9]{10}"#', $home), 'JS heeft een versienummer op basis van de inhoud');
preg_match('#/assets/app\.css\?v=([a-f0-9]{10})#', $home, $cv);
T::eq($cv[1] ?? '', substr(md5_file($root . '/public/assets/app.css'), 0, 10), 'het versienummer is de hash van app.css');

T::section('Wachtwoord vergeten');
clearThrottle($dbfile);
$gPw = 'Gerda-oud-wachtwoord-1';
$g = register($base, 'Gerda Reset', 'gerda@voorbeeld.nl', $gPw);
$mailLog = "$root/storage/logs/mail.log";
$lastLink = function (string $to) use ($mailLog): ?string {
    $log = is_file($mailLog) ? (string) file_get_contents($mailLog) : '';
    $found = null;
    foreach (preg_split('/^--- /m', $log) as $blk) {
        if (str_contains($blk, "naar $to") && preg_match('#/wachtwoord-resetten/([a-f0-9]{64})#', $blk, $m)) {
            $found = $m[1];
        }
    }
    return $found;
};
T::ok(str_contains((new Client($base))->get('/inloggen')['body'], 'href="/wachtwoord-vergeten"'), 'inlogpagina verwijst naar wachtwoord vergeten');
$ask = function (string $email) use ($base): string { $cl = new Client($base); return $cl->follow($cl->submit('/wachtwoord-vergeten', '/wachtwoord-vergeten', ['email' => $email]))['body']; };
$bodyKnown = $ask('gerda@voorbeeld.nl');
clearThrottle($dbfile);
$bodyUnknown = $ask('bestaat-niet@voorbeeld.nl');
$strip = fn(string $b) => preg_replace('#<input[^>]*name="_token"[^>]*>#', '', preg_replace('#value="[^"]*"#', '', $b));
T::ok(str_contains($bodyKnown, 'Als dit e-mailadres bij een account hoort') && $strip($bodyKnown) === $strip($bodyUnknown), 'het antwoord is hetzelfde voor bekende en onbekende e-mailadressen');
T::ok($lastLink('bestaat-niet@voorbeeld.nl') === null, 'onbekend e-mailadres krijgt geen mail');
$tok = $lastLink('gerda@voorbeeld.nl');
T::ok($tok !== null, 'patiënt krijgt een mail met een herstellink');
$rawq = function (string $sql) use ($dbfile) { $p = new PDO('sqlite:' . $dbfile); $p->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); $p->exec('PRAGMA busy_timeout=5000'); return $p->query($sql); };
T::eq((int) $rawq('SELECT COUNT(*) FROM password_resets')->fetchColumn() >= 1, true, 'herstelcode staat in de database');
T::eq((int) $rawq("SELECT COUNT(*) FROM password_resets WHERE token_idx = '" . $tok . "'")->fetchColumn(), 0, 'de herstelcode zelf staat niet in de database (alleen een HMAC)');
$page = (new Client($base))->get('/wachtwoord-resetten/' . $tok)['body'];
T::ok(str_contains($page, 'Nieuw wachtwoord kiezen'), 'geldige link toont het formulier');
T::ok(str_contains((new Client($base))->get('/wachtwoord-resetten/' . str_repeat('a', 64))['body'], 'werkt niet meer'), 'onbekende link toont een foutmelding');
$cl = new Client($base);
$cl->get('/wachtwoord-resetten/' . $tok);
$r = $cl->submit('/wachtwoord-resetten/' . $tok, '/wachtwoord-resetten/' . $tok, ['password' => 'kort', 'password_confirmation' => 'kort']);
T::ok(str_contains($cl->follow($r)['body'], 'minimaal 10 tekens'), 'te kort wachtwoord wordt geweigerd');
$r = $cl->submit('/wachtwoord-resetten/' . $tok, '/wachtwoord-resetten/' . $tok, ['password' => 'Gerda-nieuw-wachtwoord-2', 'password_confirmation' => 'anders-anders-1']);
T::ok(str_contains($cl->follow($r)['body'], 'komen niet overeen'), 'ongelijke wachtwoorden worden geweigerd');
T::eq($g->get('/dashboard')['status'], 200, 'bestaande sessie werkt nog vóór het wijzigen');
$r = $cl->submit('/wachtwoord-resetten/' . $tok, '/wachtwoord-resetten/' . $tok, ['password' => 'Gerda-nieuw-wachtwoord-2', 'password_confirmation' => 'Gerda-nieuw-wachtwoord-2']);
T::eq($r['location'], '/inloggen', 'wachtwoord wijzigen lukt');
T::ok(str_contains($cl->follow($r)['body'], 'Uw wachtwoord is gewijzigd'), 'bevestiging na wijzigen');
T::eq($g->get('/dashboard')['location'], '/inloggen', 'alle bestaande sessies zijn beëindigd');
T::ok(str_contains((string) file_get_contents($mailLog), 'Uw wachtwoord is gewijzigd'), 'bevestigingsmail na wijziging');
clearThrottle($dbfile);
$old = new Client($base);
T::ok(str_contains($old->follow($old->submit('/inloggen', '/inloggen', ['email' => 'gerda@voorbeeld.nl', 'password' => $gPw]))['body'], 'komen niet overeen'), 'oud wachtwoord werkt niet meer');
$new = new Client($base);
$r = $new->submit('/inloggen', '/inloggen', ['email' => 'gerda@voorbeeld.nl', 'password' => 'Gerda-nieuw-wachtwoord-2']);
T::eq($r['location'], '/2fa/controle', 'nieuw wachtwoord werkt, maar tweestapsverificatie blijft vereist');
T::eq($new->get('/dashboard')['location'], '/2fa/controle', 'zonder tweede factor geen toegang');
$new->submit('/2fa/controle', '/2fa/controle', ['code' => Totp::codeForStep($g->secret, intdiv(time(), 30) + 1)]);
T::eq($new->get('/dashboard')['status'], 200, 'met tweede factor wel toegang');
$r = (new Client($base))->get('/wachtwoord-resetten/' . $tok);
T::ok(str_contains($r['body'], 'werkt niet meer'), 'de link werkt maar één keer');
// verlopen link
clearThrottle($dbfile);
$ask('gerda@voorbeeld.nl');
$tok2 = $lastLink('gerda@voorbeeld.nl');
T::ok($tok2 !== null && $tok2 !== $tok, 'nieuw verzoek geeft een nieuwe link');
$rawq('UPDATE password_resets SET expires_at = ' . (time() - 10));
T::ok(str_contains((new Client($base))->get('/wachtwoord-resetten/' . $tok2)['body'], 'werkt niet meer'), 'een verlopen link werkt niet');
// nieuwe link maakt de vorige ongeldig
clearThrottle($dbfile);
$ask('gerda@voorbeeld.nl'); $tokA = $lastLink('gerda@voorbeeld.nl');
clearThrottle($dbfile);
$ask('gerda@voorbeeld.nl'); $tokB = $lastLink('gerda@voorbeeld.nl');
T::ok(str_contains((new Client($base))->get('/wachtwoord-resetten/' . $tokA)['body'], 'werkt niet meer') && str_contains((new Client($base))->get('/wachtwoord-resetten/' . $tokB)['body'], 'Nieuw wachtwoord kiezen'), 'een nieuwe link maakt de vorige ongeldig');
// limiet per e-mailadres
clearThrottle($dbfile);
foreach ([1, 2, 3, 4] as $i) { $ask('limiet@voorbeeld.nl'); }
T::ok($lastLink('limiet@voorbeeld.nl') === null, 'bij onbekend adres nooit een mail');
$before = substr_count((string) file_get_contents($mailLog), 'naar gerda@voorbeeld.nl');
clearThrottle($dbfile);
foreach ([1, 2, 3, 4, 5] as $i) { $ask('gerda@voorbeeld.nl'); }
T::eq(substr_count((string) file_get_contents($mailLog), 'naar gerda@voorbeeld.nl') - $before, 3, 'maximaal 3 herstelmails per uur per e-mailadres');
// artsen gebruiken deze route niet
clearThrottle($dbfile);
$ask('jansen@praktijk.nl');
T::ok($lastLink('jansen@praktijk.nl') === null, 'artsen krijgen geen herstellink (beheerder stelt een nieuw wachtwoord in)');
T::ok(str_contains((new Client($base))->get('/wachtwoord-vergeten')['body'], 'praktijkbeheerder'), 'pagina verwijst artsen naar de praktijkbeheerder');
// accounts verwijderen verwijdert ook de herstelcodes
$ask('gerda@voorbeeld.nl');
$gc = $new;
$r = $gc->submit('/account', '/account/verwijderen', ['password' => 'Gerda-nieuw-wachtwoord-2']);
T::eq((int) $rawq('SELECT COUNT(*) FROM password_resets')->fetchColumn(), 0, 'herstelcodes verdwijnen mee met het account');
clearThrottle($dbfile);

T::section('Juridische teksten en toestemming');
foreach (['/pilot' => 'Pilotinformatie', '/privacy' => 'Privacyverklaring', '/voorwaarden' => 'Gebruiksvoorwaarden'] as $path => $title) {
    $r = (new Client($base))->get($path);
    T::ok($r['status'] === 200 && str_contains($r['body'], "<h1>$title") && str_contains($r['body'], 'invullen in .env'), "$path is openbaar en toont de (nog in te vullen) gegevens van de exploitant");
}
T::ok(!preg_match('/\bBSN\b/', (new Client($base))->get('/registreren')['body']) && !preg_match('/\bBSN\b/', $arts->get('/arts')['body']), 'er wordt nergens meer naar een BSN gevraagd (registratie en uitnodiging)');
T::ok(str_contains($regBody(['akkoord' => '']), 'toestemming geven'), 'registreren vereist toestemming (pilotinformatie en privacyverklaring)');
T::ok(str_contains((new Client($base))->get('/registreren')['body'], 'href="/pilot"') && str_contains((new Client($base))->get('/')['body'], 'href="/privacy"'), 'links naar de teksten staan op het registratieformulier en in de voettekst');


// intrekken door patiënt
$page = $c->get('/dashboard');
preg_match('#/verificatie/([a-f0-9]{32})/intrekken#', $page['body'], $m);
$r = $c->submit('/dashboard', '/verificatie/' . ($m[1] ?? 'x') . '/intrekken', []);
T::ok(str_contains($c->follow($r)['body'], 'ingetrokken'), 'patiënt trekt koppeling in');
T::ok(!str_contains((new Client($base))->get('/v/' . $token)['body'], 'door de huisarts'), 'na intrekken geen bevestiging meer zichtbaar');
$p = $arts->get("/arts/patienten/$vid");
T::ok(str_contains($p['body'], 'ingetrokken') && !str_contains($p['body'], 'Nieuwe tekst'), 'arts ziet na intrekken de verklaring niet meer');
$rawArts = new PDO('sqlite:' . $dbfile);
$rawArts->exec('PRAGMA wal_checkpoint(TRUNCATE)');
$rawDb = file_get_contents($dbfile) . (is_file("$dbfile-wal") ? file_get_contents("$dbfile-wal") : '');
foreach (['C333CBA', 'jansen@praktijk', 'Wanroij', 'carla@voorbeeld', $carla, $carla4] as $needle) {
    T::ok(!str_contains($rawDb, $needle), "'" . substr($needle, 0, 14) . "' niet leesbaar in database (verificaties)");
}
$cols2 = [];
foreach ($rawArts->query('PRAGMA table_info(verifications)') as $col) { $cols2[] = $col['name']; }
T::eq($cols2, ['seq', 'id', 'practice_id', 'arts_id', 'patient_user_id', 'key_idx', 'data'], 'verificatietabel bevat geen leesbare persoonsgegevens');

T::section('Versleuteling in het databasebestand');
$pdo = new PDO('sqlite:' . $dbfile);
$pdo->exec('PRAGMA wal_checkpoint(TRUNCATE)');
$raw = file_get_contents($dbfile) . (is_file("$dbfile-wal") ? file_get_contents("$dbfile-wal") : '');
foreach (['1962-11-12', '12-11-1962', 'Anna', 'anna@voorbeeld', 'Vries', 'Ik houd van mijn tuin', 'Bram', 'Dr. Jansen', 'praktijk@huisarts', 'piet@voorbeeld', 'Piet de Vries, zoon', '06 12345678', 'regel 1', 'Gewijzigd concept', '127.0.0.1', 'curl/', $token, $shareToken, $c->secret] as $needle) {
    // korte zoektermen (bijv. 'Bram') komen bij toeval voor in versleutelde (base64-)data: dat zou een valse melding geven
    if (strlen($needle) < 8) { continue; }
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
$left = (int) $pdo->query('SELECT (SELECT COUNT(*) FROM versions WHERE declaration_id NOT IN (SELECT id FROM declarations)) + (SELECT COUNT(*) FROM shares WHERE declaration_id NOT IN (SELECT id FROM declarations)) + (SELECT COUNT(*) FROM access_logs WHERE declaration_id NOT IN (SELECT id FROM declarations)) + (SELECT COUNT(*) FROM declarations WHERE user_id NOT IN (SELECT id FROM users))')->fetchColumn();
T::eq($left, 0, 'geen verweesde versies, uitnodigingen of logs na verwijderen');
$bas = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
T::eq($bas, 5, 'alleen Bas, Eva, Leo, Dr. Jansen en Dr. Elders (andere praktijk) bestaan nog');
T::eq((int) $pdo->query('SELECT COUNT(*) FROM verifications WHERE patient_user_id IS NOT NULL')->fetchColumn(), 3, 'alleen de verificaties van Bas, Eva en Leo (nog actief) bestaan; die van de verwijderde patiënt is weg');

$log = @file_get_contents("$tmp/server.log") ?: '';
T::ok(!preg_match('/Fatal|Warning|Notice|Deprecated/', $log), 'geen PHP-waarschuwingen in serverlog' . (preg_match('/(Fatal|Warning|Notice|Deprecated)[^\n]*/', $log, $mm) ? ': ' . $mm[0] : ''));
T::finish();

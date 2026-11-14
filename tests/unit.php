<?php

declare(strict_types=1);

require __DIR__ . '/harness.php';
require dirname(__DIR__) . '/src/bootstrap.php';

use WV\Config;
use WV\Content;
use WV\Crypto;
use WV\DecryptException;
use WV\Db;
use WV\HttpException;
use WV\Pdf;
use WV\RateLimiter;
use WV\Router;
use WV\Store;
use WV\Totp;

T::section('Crypto');
$key = random_bytes(32);
$c = new Crypto($key);
$sealed = $c->seal('geheim', 'users:abc');
T::ok(!str_contains($sealed, 'geheim'), 'ciphertext bevat de platte tekst niet');
T::eq($c->open($sealed, 'users:abc'), 'geheim', 'roundtrip');
T::ok($c->seal('geheim', 'users:abc') !== $sealed, 'elke versleuteling gebruikt een nieuwe nonce');
T::throws(fn() => $c->open($sealed, 'users:andere'), DecryptException::class, 'ciphertext is gebonden aan de rij (AAD)');
T::throws(fn() => (new Crypto(random_bytes(32)))->open($sealed, 'users:abc'), DecryptException::class, 'verkeerde sleutel wordt geweigerd');
$tampered = substr($sealed, 0, -3) . (substr($sealed, -3) === 'AAA' ? 'BBB' : 'AAA');
T::throws(fn() => $c->open($tampered, 'users:abc'), DecryptException::class, 'gemanipuleerde data wordt geweigerd');
T::eq(strlen($c->seal(str_repeat('x', 10), 'a')), strlen($c->seal(str_repeat('y', 200), 'a')), 'lengte van korte waarden lekt niet (opvulling)');
T::eq($c->openJson($c->sealJson(['a' => 'é€', 'b' => [1, 2]], 'x'), 'x'), ['a' => 'é€', 'b' => [1, 2]], 'JSON roundtrip met unicode');
T::eq($c->index('email', 'a@b.nl'), $c->index('email', 'a@b.nl'), 'blind index is deterministisch');
T::ok($c->index('email', 'a@b.nl') !== $c->index('token', 'a@b.nl'), 'blind index is per doel gescheiden');
T::ok($c->index('email', 'a@b.nl') !== (new Crypto(random_bytes(32)))->index('email', 'a@b.nl'), 'blind index hangt van de sleutel af');
T::eq(Crypto::parseKey(Crypto::generateKey()) !== '', true, 'sleutel genereren en inlezen');
T::throws(fn() => Crypto::parseKey('base64:kort'), RuntimeException::class, 'ongeldige sleutel wordt geweigerd');

T::section('TOTP (RFC 6238 testvectoren, SHA-1)');
$secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // "12345678901234567890"
T::eq(Totp::generateCode($secret, 59), '287082', 't=59');
T::eq(Totp::generateCode($secret, 1111111109), '081804', 't=1111111109');
T::eq(Totp::generateCode($secret, 1234567890), '005924', 't=1234567890');
T::eq(Totp::verify($secret, '287082', 1, 59), intdiv(59, 30), 'verify geeft tijdstap terug');
T::eq(Totp::verify($secret, '287082', 1, 59 + 30), intdiv(59, 30), 'venster ±1 stap');
T::eq(Totp::verify($secret, '287082', 1, 59 + 90), null, 'te oude code wordt geweigerd');
T::eq(Totp::verify($secret, '12345', 1, 59), null, 'ongeldig formaat wordt geweigerd');
T::eq(strlen(Totp::generateSecret()), 32, 'geheim is 160 bit (32 base32-tekens)');

T::section('Content');
$d = Content::defaults();
$m = Content::mergeStep(3, ['reanimatie' => 'nee', 'reanimatie_toelichting' => "a\r\nb", 'intro' => 'hack'], $d);
T::eq($m['reanimatie'], 'nee', 'toegestane keuze wordt overgenomen');
T::eq($m['reanimatie_toelichting'], "a\nb", 'regeleinden genormaliseerd');
T::eq($m['intro'], '', 'veld van een andere stap wordt genegeerd');
T::eq(Content::mergeStep(3, ['reanimatie' => 'misschien'], $d)['reanimatie'], '', 'ongeldige keuzewaarde wordt genegeerd');
T::eq(Content::mergeStep(16, ['datum' => 'morgen'], $d)['datum'], '', 'ongeldige datum wordt genegeerd');
T::eq(Content::mergeStep(16, ['datum' => '2026-05-01'], $d)['datum'], '2026-05-01', 'geldige datum');
T::eq(strlen(Content::mergeStep(1, ['intro' => str_repeat('x', 50000)], $d)['intro']), Content::MAX_FIELD_LENGTH, 'maximale veldlengte');
T::eq(Content::next(8, $d), 12, 'stap 8 zonder euthanasie → 12');
T::eq(Content::next(8, ['euthanasie_opgenomen' => 'ja'] + $d), 9, 'stap 8 met euthanasie → 9');
T::eq(Content::previous(12, $d), 8, 'stap 12 terug zonder euthanasie → 8');
T::eq(Content::next(17, $d), 17, 'niet voorbij de laatste stap');
T::eq(Content::next(11, ['euthanasie_opgenomen' => 'ja'] + $d), 12, 'na de algemene brief volgt de dementiepoort');
T::eq(Content::next(12, $d), 15, 'stap 12 zonder dementie → 15');
T::eq(Content::next(12, ['dementie_opgenomen' => 'ja'] + $d), 13, 'stap 12 met dementie → 13');
T::eq(Content::next(14, ['dementie_opgenomen' => 'ja'] + $d), 15, 'na dementiehoofdstuk → afsluiting');
T::eq(Content::previous(15, $d), 12, 'stap 15 terug zonder dementie → 12');
T::eq(Content::previous(15, ['dementie_opgenomen' => 'ja'] + $d), 14, 'stap 15 terug met dementie → 14');
T::eq(Content::previous(12, ['euthanasie_opgenomen' => 'ja'] + $d), 11, 'stap 12 terug met euthanasie → 11');
T::ok(Content::dementieIncluded(['dementie_opgenomen' => 'ja']) && !Content::dementieIncluded(['dementie_opgenomen' => 'nee']), 'dementieIncluded');
T::eq(count(Content::TITLES), Content::TOTAL_STEPS, 'elke stap heeft een titel');
T::eq(count(Content::STEP_CHAPTER), Content::TOTAL_STEPS, 'elke stap hoort bij een hoofdstuk');
T::eq(array_keys(Content::normalize(['onbekend' => 1])), array_keys(Content::defaults()), 'normalize gooit onbekende sleutels weg');

T::section('Voortgang en weergave');
$st0 = Content::stepStatuses(Content::defaults());
T::eq([$st0[1], $st0[17], count($st0)], ['nieuw', 'overzicht', 17], 'lege verklaring: stappen nog te doen');
T::eq(Content::completion(Content::defaults())['done'], 0, 'lege verklaring: 0 stappen ingevuld');
$dd = Content::normalize(['voornamen' => 'Anna', 'reanimatie' => 'nee', 'euthanasie_opgenomen' => 'nee']);
$ss = Content::stepStatuses($dd);
T::eq([$ss[1], $ss[3], $ss[9], $ss[12]], ['klaar', 'klaar', 'overgeslagen', 'nieuw'], 'ingevulde stap klaar; uitgezet onderwerp overgeslagen');
$cc = Content::completion($dd);
T::ok($cc['done'] === 2 && $cc['total'] < 16 && $cc['percent'] > 0 && $cc['percent'] <= 100, 'voortgang telt overgeslagen onderwerpen niet mee');
T::eq(WV\Prefs::fromArray(['t' => '0'])->size, 0, 'tekstgrootte kan een maat kleiner dan normaal');
T::eq(WV\Prefs::fromArray(['t' => '9'])->size, 4, 'tekstgrootte blijft binnen 0 t/m 4');
T::ok(str_contains((new WV\Prefs(0))->htmlAttrs(), 'class="t0"') && !str_contains((new WV\Prefs(1))->htmlAttrs(), 'class='), 'html-klasse t0 bij kleine tekst, niets bij normaal');

T::section('Router');
$r = new Router();
$r->get('/v/{token}', ['X', 'a']);
$r->post('/v/{token}', ['X', 'b']);
$r->get('/x', ['X', 'c'], ['auth']);
T::eq($r->match('GET', '/v/abc-DEF_1')['params'], ['token' => 'abc-DEF_1'], 'route-parameter');
T::eq($r->match('POST', '/v/abc')['handler'], ['X', 'b'], 'methode-specifiek');
T::eq($r->match('GET', '/x')['middleware'], ['auth'], 'middleware');
T::throws(fn() => $r->match('GET', '/niets'), HttpException::class, '404');
try { $r->match('POST', '/x'); } catch (HttpException $e) { T::eq($e->status, 405, '405 bij verkeerde methode'); }
T::throws(fn() => $r->match('GET', '/v/a/../b'), HttpException::class, 'geen path traversal in parameters');

T::section('Database + Store (versleuteld bestand)');
$dir = sys_get_temp_dir() . '/wv_unit_' . bin2hex(random_bytes(4));
$db = new Db($dir . '/t.sqlite');
$store = new Store($db, $c);
$store->verifyKey();
T::eq(decoct(fileperms($dir . '/t.sqlite') & 0777), '600', 'databasebestand is alleen voor de eigenaar leesbaar (0600)');
T::eq(decoct(fileperms($dir) & 0777), '700', 'databasemap is 0700');
$u = $store->createUser('Jan Jansen', 'Jan@Voorbeeld.nl', 'hash');
T::ok($u !== null, 'gebruiker aangemaakt');
T::eq($store->createUser('Ander', 'jan@voorbeeld.NL', 'x'), null, 'e-mailadres is uniek (hoofdletterongevoelig)');
T::eq($store->userByEmail(' JAN@voorbeeld.nl ')['name'], 'Jan Jansen', 'zoeken op e-mail via blind index');
$decl = $store->declarationForUser($u['id']);
$store->addVersion($decl['id'], ['intro' => 'Zeer vertrouwelijke tekst']);
$share = $store->addShare($decl['id'], 'naaste@voorbeeld.nl', 'Piet', 'naaste');
$store->addLog($decl['id'], 'arts', null, '203.0.113.9', 'TestAgent/1.0');
$db->pdo->exec('PRAGMA wal_checkpoint(TRUNCATE)');
$raw = file_get_contents($dir . '/t.sqlite') . (is_file($dir . '/t.sqlite-wal') ? file_get_contents($dir . '/t.sqlite-wal') : '');
foreach (['Jan Jansen', 'voorbeeld.nl', 'Zeer vertrouwelijke', 'Piet', '203.0.113.9', 'TestAgent'] as $needle) {
    T::ok(!str_contains($raw, $needle), "'$needle' staat niet in leesbare vorm in het databasebestand");
}
T::eq($store->shareByToken($share['token'])['email'], 'naaste@voorbeeld.nl', 'delen via token (blind index)');
T::eq($store->shareByToken('verkeerd'), null, 'onbekende token');
// kopiëren van versleutelde waarde naar een andere rij moet mislukken
$row = $db->one('SELECT data FROM versions LIMIT 1');
$db->run("UPDATE shares SET data = ? WHERE id = ?", [$row['data'], $share['share']['id']]);
T::throws(fn() => $store->shares($decl['id']), DecryptException::class, 'versleutelde waarde verplaatsen naar andere rij wordt gedetecteerd');
// verkeerde sleutel
T::throws(function () use ($db) { (new Store($db, new Crypto(random_bytes(32))))->verifyKey(); }, RuntimeException::class, 'verkeerde APP_KEY wordt bij opstarten herkend');
// cascade
$store->deleteUser($u['id']);
T::eq((int) $db->scalar('SELECT COUNT(*) FROM versions'), 0, 'verwijderen wist versies (cascade)');
T::eq((int) $db->scalar('SELECT COUNT(*) FROM shares'), 0, 'verwijderen wist uitnodigingen');
T::eq((int) $db->scalar('SELECT COUNT(*) FROM access_logs'), 0, 'verwijderen wist logs');

T::section('RateLimiter');
$rl = new RateLimiter($db, $c);
$res = [];
for ($i = 0; $i < 5; $i++) { $res[] = $rl->attempt('k', 3, 60); }
T::eq($res, [true, true, true, false, false], 'max 3 verzoeken per venster');
$rl->clear('k');
T::eq($rl->attempt('k', 3, 60), true, 'clear zet de teller terug');

T::section('PDF');
$pdf = new Pdf('Test');
$pdf->title('Wilsverklaring');
$pdf->heading('Kop');
for ($i = 0; $i < 60; $i++) { $pdf->field('Label ' . $i, str_repeat('Dit is een lange alinea met é, ë, ï en € tekens. ', 6)); }
$out = $pdf->output();
T::ok(str_starts_with($out, '%PDF-1.4'), 'PDF-header');
T::ok(str_ends_with(trim($out), '%%EOF'), 'PDF-einde');
T::ok(preg_match_all('#/Type /Page /#', $out) >= 2, 'tekst loopt over meerdere pagina\'s');

$t0 = microtime(true);
$long = new Pdf('Lang');
$long->field('Lang woord', str_repeat('x', 20000));
$longOut = $long->output();
T::ok(microtime(true) - $t0 < 2.0 && strlen($longOut) > 500, 'een woord van 20.000 tekens zonder spaties wordt snel afgebroken');

// opruimen
array_map('unlink', glob($dir . '/*'));
rmdir($dir);
T::finish();

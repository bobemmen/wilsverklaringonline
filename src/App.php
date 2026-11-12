<?php

declare(strict_types=1);

namespace WV;

use WV\Controllers as C;

final class App
{
    public readonly string $root;
    public readonly Db $db;
    public readonly Crypto $crypto;
    public readonly Store $store;
    public readonly Request $request;
    public readonly Session $session;
    public readonly Auth $auth;
    public readonly RateLimiter $limiter;
    public readonly Mailer $mailer;
    public readonly View $view;
    public readonly Router $router;

    private function __construct(string $root, ?Request $request)
    {
        $this->root = rtrim($root, '/');
        Config::load($this->root);
        date_default_timezone_set(Config::get('APP_TIMEZONE', 'Europe/Amsterdam'));

        if (Config::isProduction() && Config::get('APP_URL') === null) {
            throw new \RuntimeException('APP_URL ontbreekt in .env (verplicht in productie; links in e-mails worden daarvan afgeleid).');
        }
        $keyValue = Config::get('APP_KEY');
        if ($keyValue === null) {
            throw new \RuntimeException('APP_KEY ontbreekt. Voer uit: php bin/console install');
        }
        $this->crypto = new Crypto(Crypto::parseKey($keyValue));
        $this->db = new Db(Config::path(Config::get('DB_PATH', 'storage/database/wilsverklaring.sqlite')));
        $this->store = new Store($this->db, $this->crypto);
        $this->store->verifyKey();

        $this->request = $request ?? Request::capture();
        $this->session = new Session($this->db, $this->crypto, $this->request, Config::int('SESSION_LIFETIME', 120) * 60);
        $this->auth = new Auth($this->store, $this->session);
        $this->limiter = new RateLimiter($this->db, $this->crypto);
        $this->mailer = new Mailer();
        $this->view = new View($this, $this->root . '/templates');
        $this->router = new Router();
        $this->routes();
    }

    public static function boot(string $root, ?Request $request = null): self
    {
        return new self($root, $request);
    }

    /** Absolute URL. In productie altijd op basis van APP_URL (nooit van de Host-header). */
    public function url(string $path = '/'): string
    {
        $base = Config::get('APP_URL');
        if ($base === null) {
            $scheme = $this->request->isSecure() ? 'https' : 'http';
            $host = (string) ($this->request->server['HTTP_HOST'] ?? 'localhost');
            $base = $scheme . '://' . preg_replace('/[^A-Za-z0-9.:\[\]-]/', '', $host);
        }
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    // ----------------------------------------------------------------- routes

    private function routes(): void
    {
        $r = $this->router;
        $auth = ['auth', 'twofa'];
        $patient = ['auth', 'twofa', 'role:patient'];
        $arts = ['auth', 'twofa', 'role:arts'];

        $r->get('/', [C\HomeController::class, 'index']);
        $r->get('/weergave', [C\PreferencesController::class, 'show']);
        $r->get('/up', [C\HomeController::class, 'health']);

        $r->get('/inloggen', [C\AuthController::class, 'loginForm'], ['guest']);
        $r->post('/inloggen', [C\AuthController::class, 'login'], ['guest']);
        $r->get('/registreren', [C\AuthController::class, 'registerForm'], ['guest']);
        $r->post('/registreren', [C\AuthController::class, 'register'], ['guest']);
        $r->post('/uitloggen', [C\AuthController::class, 'logout'], ['auth']);

        $r->get('/2fa/instellen', [C\TwoFactorController::class, 'setup'], ['auth']);
        $r->post('/2fa/instellen', [C\TwoFactorController::class, 'confirm'], ['auth']);
        $r->get('/2fa/controle', [C\TwoFactorController::class, 'challenge'], ['auth']);
        $r->post('/2fa/controle', [C\TwoFactorController::class, 'verify'], ['auth']);

        $r->get('/dashboard', [C\DashboardController::class, 'index'], $patient);

        $r->get('/verklaring/wizard', [C\WizardController::class, 'show'], $patient);
        $r->post('/verklaring/wizard', [C\WizardController::class, 'save'], $patient);
        $r->get('/verklaring/versies', [C\WizardController::class, 'versions'], $patient);
        $r->get('/verklaring/pdf', [C\PdfController::class, 'export'], $patient);

        $r->get('/token', [C\TokenController::class, 'index'], $patient);
        $r->post('/token/genereer', [C\TokenController::class, 'regenerate'], $patient);
        $r->post('/token/intrekken', [C\TokenController::class, 'revoke'], $patient);
        $r->post('/token/instellingen', [C\TokenController::class, 'settings'], $patient);

        $r->get('/naasten', [C\ShareController::class, 'index'], $patient);
        $r->post('/naasten', [C\ShareController::class, 'invite'], $patient);
        $r->post('/naasten/{id}/intrekken', [C\ShareController::class, 'revoke'], $patient);

        $r->get('/account', [C\AccountController::class, 'index'], $patient);
        $r->get('/account/export', [C\AccountController::class, 'export'], $patient);
        $r->post('/account/verwijderen', [C\AccountController::class, 'destroy'], $patient);

        $r->post('/verificatie/{id}/intrekken', [C\VerificationController::class, 'patientRevoke'], $patient);

        $r->get('/arts', [C\ArtsController::class, 'index'], $arts);
        $r->post('/arts/zoeken', [C\ArtsController::class, 'search'], $arts);
        $r->post('/arts/uitnodigen', [C\ArtsController::class, 'invite'], $arts);
        $r->get('/arts/patienten/{id}', [C\ArtsController::class, 'patient'], $arts);
        $r->post('/arts/patienten/{id}/papiercode', [C\ArtsController::class, 'paperCode'], $arts);
        $r->post('/arts/patienten/{id}/intrekken', [C\ArtsController::class, 'revoke'], $arts);

        $r->get('/v/{token}', [C\PublicController::class, 'show'], ['throttle:public,10,60']);
        $r->get('/v/{token}/bevestig/{proof}', [C\PublicController::class, 'confirm'], ['throttle:public,10,60']);
        $r->get('/gedeeld/{token}', [C\SharedController::class, 'show'], ['throttle:shared,20,60']);
    }

    // ------------------------------------------------------------- verwerking

    public function handle(): Response
    {
        try {
            $match = $this->router->match($this->request->method, $this->request->path);

            if ($this->request->method === 'POST') {
                $this->verifyCsrf();
            }
            foreach ($match['middleware'] as $spec) {
                if (($early = $this->middleware($spec)) !== null) {
                    return $this->finalize($early);
                }
            }

            [$class, $method] = $match['handler'];
            $response = (new $class($this))->$method(...$match['params']);
        } catch (HttpException $e) {
            $response = $this->errorPage($e->status);
        } catch (\Throwable $e) {
            error_log('[WilsVerklaring] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $response = $this->errorPage(500, Config::bool('APP_DEBUG') ? $e->getMessage() : null);
        }
        return $this->finalize($response);
    }

    public function run(): void
    {
        $response = $this->handle();
        try {
            $this->session->commit();
            if (random_int(1, 100) === 1) {
                $this->session->purgeExpired();
            }
        } catch (\Throwable $e) {
            error_log('[WilsVerklaring] sessie opslaan mislukt: ' . $e->getMessage());
        }
        $response->send();
    }

    private function verifyCsrf(): void
    {
        if (($this->request->server['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') {
            throw new HttpException(403);
        }
        $given = $this->request->input('_token');
        if ($given === '' || !hash_equals($this->session->csrfToken(), $given)) {
            throw new HttpException(419);
        }
    }

    /** @return Response|null een response als het verzoek hier al eindigt */
    private function middleware(string $spec): ?Response
    {
        [$name, $arg] = array_pad(explode(':', $spec, 2), 2, '');

        switch ($name) {
            case 'guest':
                return $this->auth->check() ? Response::redirect('/dashboard') : null;

            case 'auth':
                if ($this->auth->check()) {
                    return null;
                }
                if ($this->request->method === 'GET') {
                    $this->session->put('intended', $this->request->path);
                }
                return Response::redirect('/inloggen');

            case 'twofa':
                if (!$this->auth->twoFactorEnabled()) {
                    return Response::redirect('/2fa/instellen');
                }
                return $this->auth->twoFactorVerified() ? null : Response::redirect('/2fa/controle');

            case 'role':
                // 'role:arts' alleen huisartsen; 'role:patient' alleen patiënten (huisartsen gaan naar hun eigen omgeving)
                $isArts = $this->store->isArts($this->auth->user());
                if ($arg === 'arts') {
                    return $isArts ? null : throw new HttpException(403);
                }
                return $isArts ? Response::redirect('/arts') : null;

            case 'throttle':
                [$bucket, $max, $seconds] = explode(',', $arg) + [2 => '60'];
                if (!$this->limiter->attempt($bucket . '|' . $this->request->ip(), (int) $max, (int) $seconds)) {
                    throw new HttpException(429);
                }
                return null;
        }
        throw new \LogicException("Onbekende middleware: $spec");
    }

    private function errorPage(int $status, ?string $detail = null): Response
    {
        $messages = [
            403 => ['Geen toegang', 'U heeft geen toegang tot deze pagina.'],
            404 => ['Pagina niet gevonden', 'Deze pagina bestaat niet, of de link is niet meer geldig.'],
            405 => ['Niet toegestaan', 'Deze actie is niet toegestaan.'],
            419 => ['Sessie verlopen', 'Uw sessie is verlopen of het formulier is niet meer geldig. Ga terug, laad de pagina opnieuw en probeer het nog eens.'],
            429 => ['Te veel verzoeken', 'U heeft te veel verzoeken gedaan. Probeer het over een minuut opnieuw.'],
            500 => ['Er ging iets mis', 'Er is een fout opgetreden. Probeer het later opnieuw.'],
        ];
        [$title, $text] = $messages[$status] ?? $messages[500];
        $html = $this->view->render('errors/error', ['status' => $status, 'title' => $title, 'text' => $text, 'detail' => $detail]);
        return Response::html($html, $status);
    }

    private function finalize(Response $response): Response
    {
        $csp = "default-src 'none'; style-src 'self' 'unsafe-inline'; script-src 'self'; img-src 'self' data:; "
            . "font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'";
        $defaults = [
            'Content-Security-Policy' => $csp,
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'no-referrer', // links bevatten geheime codes: nooit doorsturen
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cache-Control' => 'no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ];
        if ($this->request->isSecure() || str_starts_with((string) Config::get('APP_URL', ''), 'https://')) {
            $defaults['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }
        $response->headers += $defaults;
        return $response;
    }
}

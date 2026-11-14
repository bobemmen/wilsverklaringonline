<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Config;
use WV\HttpException;
use WV\Response;

/**
 * Demo van de patiëntomgeving. Gebruikt de echte pagina's en een echt (tijdelijk) patiëntaccount per bezoeker.
 * Aan te zetten met DEMO_ENABLED=true in .env; accounts verdwijnen na DEMO_TTL_MINUTES (standaard 120) of bij afsluiten.
 */
final class DemoController extends Controller
{
    public function show(): Response
    {
        $this->guard();
        return $this->view('auth/demo', ['minutes' => $this->ttl() / 60]);
    }

    public function start(): Response
    {
        $this->guard();
        $store = $this->app->store;
        $store->purgeDemoUsers();
        if ($store->activeDemoCount() >= max(1, (int) Config::get('DEMO_MAX', '50'))) {
            return $this->fail('/demo', ['demo' => 'De demo is op dit moment vol. Probeer het over een kwartier opnieuw.']);
        }
        $user = $store->createDemoPatient($this->ttl());
        $this->app->auth->login($user);
        $this->app->session->put('twofa_verified', true);
        $this->flash('U bekijkt de demo. Alles werkt zoals in de echte omgeving; uw gegevens worden na afloop automatisch verwijderd.');
        return $this->redirect('/dashboard');
    }

    public function stop(): Response
    {
        $u = $this->app->auth->user();
        if ($this->app->store->isDemo($u)) {
            $this->app->store->deleteUser($u['id']);
            $this->app->auth->logout();
            $this->flash('De demo is afgesloten en de gegevens zijn verwijderd.');
        }
        return $this->redirect('/');
    }

    private function guard(): void
    {
        if (!Config::bool('DEMO_ENABLED', false)) {
            throw new HttpException(404);
        }
    }

    private function ttl(): int
    {
        return max(10, min(24 * 60, (int) Config::get('DEMO_TTL_MINUTES', '120'))) * 60;
    }
}

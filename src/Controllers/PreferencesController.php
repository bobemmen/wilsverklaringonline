<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Prefs;
use WV\Response;

/**
 * Weergave-instellingen. Bewust via GET-links/-formulier (geen JavaScript of sessie nodig):
 * het enige effect is een niet-gevoelige cookie voor de eigen browser.
 */
final class PreferencesController extends Controller
{
    public function show(): Response
    {
        $q = $this->app->request->query;
        $changed = isset($q['t']) || isset($q['c']) || isset($q['opslaan']);

        if ($changed) {
            $current = Prefs::fromRequest($this->app->request);
            // een niet-aangevinkt vakje wordt niet verstuurd: bij het formulier ('opslaan') betekent geen 'r' dus uit
            if (isset($q['opslaan']) && !isset($q['r'])) {
                $q['r'] = '0';
            }
            $new = Prefs::fromArray($q, $current);
            $this->save($new);
            $this->app->view->setPrefs($new);

            $back = $this->safeBack($q['terug'] ?? '');
            if ($back !== null) {
                return $this->redirect($back);
            }
            $this->flash('Uw weergave-instellingen zijn opgeslagen.');
            return $this->redirect('/weergave');
        }

        return $this->view('pages/weergave', ['back' => $this->safeBack($q['terug'] ?? '')]);
    }

    private function save(Prefs $prefs): void
    {
        $secure = $this->app->request->isSecure();
        setcookie(Prefs::COOKIE, $prefs->cookieValue(), [
            'expires' => time() + 365 * 86400,
            'path' => '/',
            'secure' => $secure,
            'httponly' => false, // JavaScript past dezelfde cookie aan voor een directe voorbeeldweergave
            'samesite' => 'Lax',
        ]);
    }

    private function safeBack(mixed $path): ?string
    {
        return (is_string($path) && preg_match('#^/(?!/)[A-Za-z0-9/_\-.?=&]*$#', $path) && !str_starts_with($path, '/weergave')) ? $path : null;
    }
}

<?php

declare(strict_types=1);

namespace WV;

/**
 * Serverside sessies in het (versleutelde) SQLite-bestand.
 *  - Het sessie-ID in de cookie is willekeurig; in de database staat alleen een HMAC ervan.
 *  - De sessie-inhoud is versleuteld, net als alle andere gegevens.
 *  - De cookie is HttpOnly, SameSite=Lax en Secure zodra HTTPS wordt gebruikt.
 */
final class Session
{
    public const COOKIE = 'wv_session';

    private string $id = '';
    private bool $loaded = false;
    private bool $dirty = false;
    private bool $cookieNeeded = false;
    private array $data = [];
    private array $flashed = [];
    private ?string $userId = null;
    private int $lastWrite = 0;

    public function __construct(
        private readonly Db $db,
        private readonly Crypto $crypto,
        private readonly Request $request,
        private readonly int $lifetime = 7200,
    ) {
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        $cookie = $this->request->cookies[self::COOKIE] ?? null;
        if (!is_string($cookie) || !preg_match('/^[A-Za-z0-9_-]{43}$/', $cookie)) {
            return;
        }
        $idx = $this->crypto->index('session', $cookie);
        $row = $this->db->one('SELECT user_idx, expires_at, data FROM sessions WHERE id_idx = ?', [$idx]);
        if (!$row || (int) $row['expires_at'] < time()) {
            if ($row) {
                $this->db->run('DELETE FROM sessions WHERE id_idx = ?', [$idx]);
            }
            return;
        }
        try {
            $data = $this->crypto->openJson($row['data'], 'sessions:' . $idx);
        } catch (DecryptException) {
            return;
        }
        $this->id = $cookie;
        $this->data = $data['d'] ?? [];
        $this->userId = $data['u'] ?? null;
        $this->lastWrite = (int) ($data['w'] ?? 0);

        // flash-gegevens gelden voor precies één verzoek
        if (!empty($this->data['_flash'])) {
            $this->flashed = $this->data['_flash'];
            unset($this->data['_flash']);
            $this->dirty = true;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        $this->load();
        return array_key_exists($key, $this->data);
    }

    public function put(string $key, mixed $value): void
    {
        $this->load();
        $this->data[$key] = $value;
        $this->dirty = true;
    }

    public function forget(string $key): void
    {
        $this->load();
        if (array_key_exists($key, $this->data)) {
            unset($this->data[$key]);
            $this->dirty = true;
        }
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $v = $this->get($key, $default);
        $this->forget($key);
        return $v;
    }

    /** Bewaar iets voor het eerstvolgende verzoek (meldingen, fouten, oude invoer). */
    public function flash(string $key, mixed $value): void
    {
        $this->load();
        $this->data['_flash'][$key] = $value;
        $this->dirty = true;
    }

    /** Lees een flash-waarde die tijdens het vorige verzoek is gezet. */
    public function flashed(string $key, mixed $default = null): mixed
    {
        $this->load();
        return $this->flashed[$key] ?? $default;
    }

    /** Koppelt de sessie aan een gebruiker (nodig om bij verwijderen alle sessies te kunnen wissen). */
    public function setUser(?string $userId): void
    {
        $this->load();
        $this->userId = $userId;
        $this->dirty = true;
    }

    public function userId(): ?string
    {
        $this->load();
        return $this->userId;
    }

    public function csrfToken(): string
    {
        $token = $this->get('_csrf');
        if (!is_string($token) || $token === '') {
            $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $this->put('_csrf', $token);
        }
        return $token;
    }

    /** Nieuw sessie-ID met behoud van de inhoud (bij inloggen, tegen session fixation). */
    public function regenerate(): void
    {
        $this->load();
        if ($this->id !== '') {
            $this->db->run('DELETE FROM sessions WHERE id_idx = ?', [$this->crypto->index('session', $this->id)]);
        }
        $this->id = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->cookieNeeded = true;
        $this->dirty = true;
        $this->put('_csrf', rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='));
    }

    /** Wist de sessie volledig (uitloggen). */
    public function destroy(): void
    {
        $this->load();
        if ($this->id !== '') {
            $this->db->run('DELETE FROM sessions WHERE id_idx = ?', [$this->crypto->index('session', $this->id)]);
        }
        $this->id = '';
        $this->data = [];
        $this->flashed = [];
        $this->userId = null;
        $this->dirty = false;
        $this->cookieNeeded = false;
        $this->setCookie('', time() - 3600);
    }

    /** Schrijft wijzigingen weg; roep aan aan het eind van elk verzoek. */
    public function commit(): void
    {
        if (!$this->loaded) {
            return;
        }
        $now = time();
        $stale = $this->id !== '' && ($now - $this->lastWrite) > 300; // glijdende verlooptijd
        if (!$this->dirty && !$stale) {
            return;
        }
        if ($this->id === '') {
            if (!$this->dirty) {
                return;
            }
            $this->id = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $this->cookieNeeded = true;
        }

        $idx = $this->crypto->index('session', $this->id);
        $payload = $this->crypto->sealJson(['d' => $this->data, 'u' => $this->userId, 'w' => $now], 'sessions:' . $idx);
        $userIdx = $this->userId ? $this->crypto->index('session-user', $this->userId) : null;
        $this->db->run(
            'INSERT INTO sessions (id_idx, user_idx, expires_at, data) VALUES (?, ?, ?, ?)
             ON CONFLICT(id_idx) DO UPDATE SET user_idx = excluded.user_idx, expires_at = excluded.expires_at, data = excluded.data',
            [$idx, $userIdx, $now + $this->lifetime, $payload]
        );
        if ($this->cookieNeeded) {
            $this->setCookie($this->id, 0);
        }
    }

    private function isSecureContext(): bool
    {
        return $this->request->isSecure() || str_starts_with((string) Config::get('APP_URL', ''), 'https://');
    }

    private function setCookie(string $value, int $expires): void
    {
        if (headers_sent() || PHP_SAPI === 'cli') {
            return;
        }
        setcookie(self::COOKIE, $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => $this->isSecureContext(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public function purgeExpired(): void
    {
        $this->db->run('DELETE FROM sessions WHERE expires_at < ?', [time()]);
        $this->db->run('DELETE FROM throttle WHERE reset_at < ?', [time()]);
    }
}

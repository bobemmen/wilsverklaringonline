<?php

declare(strict_types=1);

namespace WV;

final class Auth
{
    private ?array $user = null;
    private bool $resolved = false;

    public function __construct(private readonly Store $store, private readonly Session $session)
    {
    }

    public function user(): ?array
    {
        if (!$this->resolved) {
            $this->resolved = true;
            $this->user = $this->store->userById($this->session->userId());
        }
        return $this->user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    /** Leesbaar tijdelijk wachtwoord (nu doorgeven; de gebruiker moet het bij de eerste login wijzigen). */
    public function temporaryPassword(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(15)), '+/', '-_'), '=');
    }

    /** Controleert e-mail + wachtwoord; geeft de gebruiker terug of null. Kost altijd evenveel tijd. */
    public function attempt(string $email, string $password): ?array
    {
        $user = $this->store->userByEmail($email);
        if ($user === null) {
            password_hash($password, PASSWORD_ARGON2ID); // vergelijkbare rekentijd, geen account-detectie via timing
            return null;
        }
        if (!password_verify($password, $user['password'])) {
            return null;
        }
        if (password_needs_rehash($user['password'], PASSWORD_ARGON2ID)) {
            $user['password'] = $this->hashPassword($password);
            $this->store->saveUser($user);
        }
        return $user;
    }

    /** Start een ingelogde sessie. Tweestapsverificatie moet daarna nog worden doorlopen. */
    public function login(array $user): void
    {
        $this->session->regenerate();
        $this->session->setUser($user['id']);
        $this->session->put('twofa_verified', false);
        $this->user = $user;
        $this->resolved = true;
    }

    public function logout(): void
    {
        $this->session->destroy();
        $this->user = null;
        $this->resolved = true;
    }

    public function twoFactorEnabled(?array $user = null): bool
    {
        $user ??= $this->user();
        return $user !== null && !empty($user['two_factor_confirmed_at']);
    }

    public function twoFactorVerified(): bool
    {
        return $this->session->get('twofa_verified', false) === true;
    }

    public function markTwoFactorVerified(): void
    {
        $this->session->regenerate(); // nieuw sessie-ID na verhoging van het vertrouwensniveau
        $this->session->put('twofa_verified', true);
    }
}

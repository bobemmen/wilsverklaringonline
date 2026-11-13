<?php

declare(strict_types=1);

namespace WV;

/** Eenvoudige teller per sleutel in SQLite (sleutels worden als HMAC opgeslagen). */
final class RateLimiter
{
    public function __construct(private readonly Db $db, private readonly Crypto $crypto)
    {
    }

    private function idx(string $key): string
    {
        return $this->crypto->index('throttle', $key);
    }

    /** Telt een verzoek en geeft true terug zolang er niet meer dan $max verzoeken per venster zijn. */
    public function attempt(string $key, int $max, int $windowSeconds): bool
    {
        $this->hit($key, $windowSeconds);
        return !$this->tooMany($key, $max + 1);
    }

    public function hit(string $key, int $windowSeconds): void
    {
        $idx = $this->idx($key);
        $now = time();
        $this->db->run(
            'INSERT INTO throttle (key_idx, hits, reset_at) VALUES (?, 1, ?)
             ON CONFLICT(key_idx) DO UPDATE SET
               hits = CASE WHEN reset_at < ? THEN 1 ELSE hits + 1 END,
               reset_at = CASE WHEN reset_at < ? THEN ? ELSE reset_at END',
            [$idx, $now + $windowSeconds, $now, $now, $now + $windowSeconds]
        );
    }

    public function tooMany(string $key, int $max): bool
    {
        $row = $this->db->one('SELECT hits, reset_at FROM throttle WHERE key_idx = ?', [$this->idx($key)]);
        return $row !== null && (int) $row['reset_at'] >= time() && (int) $row['hits'] >= $max;
    }

    public function clear(string $key): void
    {
        $this->db->run('DELETE FROM throttle WHERE key_idx = ?', [$this->idx($key)]);
    }
}

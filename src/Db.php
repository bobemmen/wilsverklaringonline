<?php

declare(strict_types=1);

namespace WV;

use PDO;

/**
 * SQLite-bestand. Alle kolommen met gegevens bevatten versleutelde JSON
 * (zie Crypto); in platte tekst staan alleen willekeurige id's, HMAC-indexen
 * en sessie-/throttle-verloopmomenten.
 */
final class Db
{
    private const SCHEMA_VERSION = 1;

    public PDO $pdo;

    public function __construct(public readonly string $path)
    {
        $dir = dirname($path);
        $oldUmask = umask(0o077);
        try {
            if (!is_dir($dir) && !mkdir($dir, 0o700, true) && !is_dir($dir)) {
                throw new \RuntimeException("Kan databasemap niet aanmaken: $dir");
            }
            @chmod($dir, 0o700); // alleen de eigenaar mag in de databasemap
            if (!file_exists($path)) {
                touch($path);
            }
            @chmod($path, 0o600);
        } finally {
            umask($oldUmask);
        }

        $this->pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec('PRAGMA busy_timeout = 5000');
        $this->pdo->exec('PRAGMA secure_delete = ON');
        $this->pdo->exec('PRAGMA trusted_schema = OFF');
        $this->pdo->exec('PRAGMA journal_mode = WAL');
        $this->pdo->exec('PRAGMA synchronous = FULL');

        $this->migrate();
    }

    public function migrate(): void
    {
        $version = (int) $this->pdo->query('PRAGMA user_version')->fetchColumn();
        if ($version >= self::SCHEMA_VERSION) {
            return;
        }

        $this->pdo->beginTransaction();
        try {
            if ($version < 1) {
                $this->pdo->exec(<<<'SQL'
                CREATE TABLE users (
                    id        TEXT PRIMARY KEY,
                    email_idx TEXT NOT NULL UNIQUE,
                    data      TEXT NOT NULL
                ) WITHOUT ROWID;

                CREATE TABLE declarations (
                    id        TEXT PRIMARY KEY,
                    user_id   TEXT NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
                    token_idx TEXT UNIQUE,
                    data      TEXT NOT NULL
                ) WITHOUT ROWID;

                CREATE TABLE versions (
                    seq            INTEGER PRIMARY KEY AUTOINCREMENT,
                    id             TEXT NOT NULL UNIQUE,
                    declaration_id TEXT NOT NULL REFERENCES declarations(id) ON DELETE CASCADE,
                    data           TEXT NOT NULL
                );
                CREATE INDEX versions_declaration ON versions(declaration_id);

                CREATE TABLE shares (
                    seq            INTEGER PRIMARY KEY AUTOINCREMENT,
                    id             TEXT NOT NULL UNIQUE,
                    declaration_id TEXT NOT NULL REFERENCES declarations(id) ON DELETE CASCADE,
                    token_idx      TEXT NOT NULL UNIQUE,
                    data           TEXT NOT NULL
                );
                CREATE INDEX shares_declaration ON shares(declaration_id);

                CREATE TABLE access_logs (
                    seq            INTEGER PRIMARY KEY AUTOINCREMENT,
                    id             TEXT NOT NULL UNIQUE,
                    declaration_id TEXT NOT NULL REFERENCES declarations(id) ON DELETE CASCADE,
                    data           TEXT NOT NULL
                );
                CREATE INDEX access_logs_declaration ON access_logs(declaration_id);

                CREATE TABLE sessions (
                    id_idx     TEXT PRIMARY KEY,
                    user_idx   TEXT,
                    expires_at INTEGER NOT NULL,
                    data       TEXT NOT NULL
                ) WITHOUT ROWID;
                CREATE INDEX sessions_user ON sessions(user_idx);
                CREATE INDEX sessions_expires ON sessions(expires_at);

                CREATE TABLE throttle (
                    key_idx  TEXT PRIMARY KEY,
                    hits     INTEGER NOT NULL,
                    reset_at INTEGER NOT NULL
                ) WITHOUT ROWID;

                CREATE TABLE meta (
                    k TEXT PRIMARY KEY,
                    v TEXT NOT NULL
                ) WITHOUT ROWID;
                SQL);
            }
            $this->pdo->exec('PRAGMA user_version = ' . self::SCHEMA_VERSION);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** @return list<array<string,mixed>> */
    public function all(string $sql, array $params = []): array
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function one(string $sql, array $params = []): ?array
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }

    public function scalar(string $sql, array $params = []): mixed
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $v = $st->fetchColumn();
        return $v === false ? null : $v;
    }

    public function run(string $sql, array $params = []): int
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public function transaction(callable $fn): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $fn();
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}

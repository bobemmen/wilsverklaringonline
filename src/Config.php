<?php

declare(strict_types=1);

namespace WV;

/**
 * Minimale configuratie: .env-bestand in de projectmap, overschreven door
 * echte omgevingsvariabelen (bijv. systemd EnvironmentFile).
 */
final class Config
{
    private static array $values = [];
    private static string $root = '';

    public static function load(string $root): void
    {
        self::$root = rtrim($root, '/');
        self::$values = [];

        $file = self::$root . '/.env';
        if (is_file($file) && is_readable($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                if (strlen($val) >= 2 && ($val[0] === '"' || $val[0] === "'") && substr($val, -1) === $val[0]) {
                    $val = substr($val, 1, -1);
                } else {
                    $val = preg_replace('/\s+#.*$/', '', $val) ?? $val;
                }
                self::$values[$key] = $val;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $env = getenv($key);
        if ($env !== false && $env !== '') {
            return $env;
        }
        $val = self::$values[$key] ?? null;
        return ($val === null || $val === '') ? $default : $val;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        return $v === null ? $default : in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default): int
    {
        $v = self::get($key);
        return ($v !== null && ctype_digit($v)) ? (int) $v : $default;
    }

    public static function root(): string
    {
        return self::$root;
    }

    /** Pad relatief aan de projectmap, of absoluut als het met / begint. */
    public static function path(string $relative): string
    {
        return str_starts_with($relative, '/') ? $relative : self::$root . '/' . $relative;
    }

    public static function isProduction(): bool
    {
        return self::get('APP_ENV', 'production') === 'production';
    }
}

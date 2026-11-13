<?php

declare(strict_types=1);

final class T
{
    public static int $pass = 0;
    public static int $fail = 0;

    public static function ok(bool $cond, string $msg): void
    {
        if ($cond) {
            self::$pass++;
            echo "  ok   $msg\n";
        } else {
            self::$fail++;
            echo "  FAIL $msg\n";
        }
    }

    public static function eq(mixed $actual, mixed $expected, string $msg): void
    {
        $ok = $actual === $expected;
        self::ok($ok, $msg . ($ok ? '' : ' (kreeg ' . var_export($actual, true) . ', verwacht ' . var_export($expected, true) . ')'));
    }

    public static function throws(callable $fn, string $class, string $msg): void
    {
        try {
            $fn();
            self::ok(false, $msg . ' (geen exception)');
        } catch (Throwable $e) {
            self::ok($e instanceof $class, $msg . ($e instanceof $class ? '' : ' (kreeg ' . get_class($e) . ')'));
        }
    }

    public static function section(string $name): void
    {
        echo "\n== $name\n";
    }

    public static function finish(): never
    {
        echo "\n" . self::$pass . " geslaagd, " . self::$fail . " mislukt\n";
        exit(self::$fail === 0 ? 0 : 1);
    }
}

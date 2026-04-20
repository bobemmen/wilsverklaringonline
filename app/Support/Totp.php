<?php

namespace App\Support;

class Totp
{
    public static function generateSecret(int $length = 20): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, 31)];
        }
        return $secret;
    }

    public static function base32Decode(string $input): string
    {
        $input = strtoupper($input);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        for ($i = 0; $i < strlen($input); $i++) {
            $pos = strpos($alphabet, $input[$i]);
            if ($pos === false) continue;
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $output = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $output .= chr(bindec(substr($bits, $i, 8)));
        }
        return $output;
    }

    public static function generateCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $counter = intdiv($timestamp, 30);
        $binaryCounter = pack('N*', 0, $counter);
        $key = self::base32Decode($secret);
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $truncated = (ord($hash[$offset]) & 0x7F) << 24
            | (ord($hash[$offset + 1]) & 0xFF) << 16
            | (ord($hash[$offset + 2]) & 0xFF) << 8
            | (ord($hash[$offset + 3]) & 0xFF);
        $code = $truncated % 1000000;
        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $time = time();
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::generateCode($secret, $time + ($i * 30)), $code)) {
                return true;
            }
        }
        return false;
    }

    public static function otpauthUrl(string $label, string $secret, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $label)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&period=30&digits=6&algorithm=SHA1';
    }
}

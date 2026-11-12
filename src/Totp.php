<?php

declare(strict_types=1);

namespace WV;

/** TOTP (RFC 6238, SHA-1, 6 cijfers, 30 seconden), compatibel met alle gangbare authenticator-apps. */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** 32 tekens base32 = 160 bit, zoals RFC 4226 aanbeveelt. */
    public static function generateSecret(int $length = 32): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::ALPHABET[random_int(0, 31)];
        }
        return $secret;
    }

    public static function base32Decode(string $input): string
    {
        $input = strtoupper(preg_replace('/[\s=]+/', '', $input) ?? '');
        $bits = '';
        foreach (str_split($input) as $ch) {
            $pos = strpos(self::ALPHABET, $ch);
            if ($pos === false) {
                continue;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $out .= chr((int) bindec(substr($bits, $i, 8)));
        }
        return $out;
    }

    public static function codeForStep(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);
        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public static function generateCode(string $secret, ?int $timestamp = null): string
    {
        return self::codeForStep($secret, intdiv($timestamp ?? time(), 30));
    }

    /**
     * Controleert een code (±1 tijdvenster). Geeft de tijdstap terug waarmee hij
     * overeenkwam (de aanroeper bewaart die om hergebruik te weigeren) of null.
     */
    public static function verify(string $secret, string $code, int $window = 1, ?int $now = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (!preg_match('/^\d{6}$/', $code)) {
            return null;
        }
        $current = intdiv($now ?? time(), 30);
        $matched = null;
        for ($i = -$window; $i <= $window; $i++) {
            // geen vroege return: constante looptijd
            if (hash_equals(self::codeForStep($secret, $current + $i), $code)) {
                $matched = $current + $i;
            }
        }
        return $matched;
    }

    public static function otpauthUrl(string $label, string $secret, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $label)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&period=30&digits=6&algorithm=SHA1';
    }
}

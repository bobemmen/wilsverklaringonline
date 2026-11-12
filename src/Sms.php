<?php

declare(strict_types=1);

namespace WV;

/**
 * Verstuurt sms-berichten.
 *   SMS_DRIVER=log   – schrijft naar storage/logs/sms.log (alleen voor ontwikkeling!)
 *   SMS_DRIVER=bird  – Bird (bird.com): nog niet gekoppeld; verzenden mislukt (de patiënt kan dan niet koppelen)
 * In productie zonder gekoppelde dienst wordt nooit stilzwijgend "verstuurd".
 */
final class Sms
{
    public function send(string $to, string $text): bool
    {
        $driver = Config::get('SMS_DRIVER', 'log');
        try {
            return match ($driver) {
                'bird' => $this->bird($to, $text),
                default => $this->log($to, $text),
            };
        } catch (\Throwable $e) {
            error_log('[WilsVerklaring] sms mislukt: ' . $e->getMessage());
            return false;
        }
    }

    private function bird(string $to, string $text): bool
    {
        // Hier komt later de API-aanroep naar Bird (SMS_BIRD_* in .env).
        error_log('[WilsVerklaring] SMS_DRIVER=bird is nog niet gekoppeld: sms NIET verstuurd.');
        return false;
    }

    private function log(string $to, string $text): bool
    {
        if (Config::isProduction()) {
            error_log('[WilsVerklaring] SMS_DRIVER=log in productie: sms NIET verstuurd. Koppel een sms-dienst.');
            return false;
        }
        $file = Config::path('storage/logs/sms.log');
        @mkdir(dirname($file), 0o700, true);
        return file_put_contents($file, date('c') . " naar $to: $text\n", FILE_APPEND | LOCK_EX) !== false;
    }
}

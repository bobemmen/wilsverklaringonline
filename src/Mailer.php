<?php

declare(strict_types=1);

namespace WV;

/**
 * Verstuurt platte-tekstmail.
 *   MAIL_DRIVER=smtp  (standaard bij MAIL_HOST) – eigen SMTP-client met TLS/STARTTLS en AUTH
 *   MAIL_DRIVER=mail  – PHP mail() / lokale sendmail
 *   MAIL_DRIVER=log   – schrijft naar storage/logs/mail.log (alleen voor ontwikkeling!)
 */
final class Mailer
{
    public function send(string $to, string $subject, string $body): bool
    {
        $driver = Config::get('MAIL_DRIVER', Config::get('MAIL_HOST') ? 'smtp' : 'log');
        $from = Config::get('MAIL_FROM_ADDRESS', 'no-reply@localhost');
        $fromName = Config::get('MAIL_FROM_NAME', 'WilsVerklaring');

        if (str_ends_with(mb_strtolower(trim($to)), '@demo.invalid')) {
            return true; // demo-accounts bestaan alleen in de app; er gaat nooit een echte mail naartoe
        }
        if (preg_match('/[\r\n]/', $to . $from . $fromName . $subject)) {
            return false; // header-injectie
        }

        try {
            return match ($driver) {
                'smtp' => $this->smtp($to, $from, $fromName, $subject, $body),
                'mail' => $this->phpMail($to, $from, $fromName, $subject, $body),
                default => $this->log($to, $subject, $body),
            };
        } catch (\Throwable $e) {
            error_log('[WilsVerklaring] mail mislukt: ' . $e->getMessage());
            return false;
        }
    }

    private function message(string $to, string $from, string $fromName, string $subject, string $body): string
    {
        $encode = fn(string $s) => '=?UTF-8?B?' . base64_encode($s) . '?=';
        $headers = [
            'Date: ' . date('r'),
            'From: ' . $encode($fromName) . ' <' . $from . '>',
            'To: <' . $to . '>',
            'Subject: ' . $encode($subject),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (parse_url((string) Config::get('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost') . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'Auto-Submitted: auto-generated',
        ];
        return implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n");
    }

    private function log(string $to, string $subject, string $body): bool
    {
        if (Config::isProduction()) {
            error_log('[WilsVerklaring] MAIL_DRIVER=log in productie: e-mail NIET verstuurd. Stel MAIL_HOST in.');
            return false;
        }
        $file = Config::path('storage/logs/mail.log');
        @mkdir(dirname($file), 0o700, true);
        return file_put_contents($file, "--- " . date('c') . " naar $to\nOnderwerp: $subject\n\n$body\n\n", FILE_APPEND | LOCK_EX) !== false;
    }

    private function phpMail(string $to, string $from, string $fromName, string $subject, string $body): bool
    {
        $msg = $this->message($to, $from, $fromName, $subject, $body);
        [$headers, $content] = explode("\r\n\r\n", $msg, 2);
        // mail() zet To en Subject zelf
        $headers = implode("\r\n", array_filter(explode("\r\n", $headers), fn($h) => !str_starts_with($h, 'To:') && !str_starts_with($h, 'Subject:')));
        return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $content, $headers);
    }

    private function smtp(string $to, string $from, string $fromName, string $subject, string $body): bool
    {
        $host = (string) Config::get('MAIL_HOST');
        $enc = strtolower((string) Config::get('MAIL_ENCRYPTION', 'tls')); // tls (STARTTLS), ssl (impliciet), none
        $port = Config::int('MAIL_PORT', $enc === 'ssl' ? 465 : ($enc === 'none' ? 25 : 587));
        $user = Config::get('MAIL_USERNAME');
        $pass = Config::get('MAIL_PASSWORD');

        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host]]);
        $remote = ($enc === 'ssl' ? 'tls://' : 'tcp://') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            throw new \RuntimeException("SMTP-verbinding mislukt: $errstr ($errno)");
        }
        stream_set_timeout($fp, 15);

        $read = function () use ($fp): array {
            $lines = [];
            while (($line = fgets($fp, 1024)) !== false) {
                $lines[] = rtrim($line);
                if (strlen($line) < 4 || $line[3] === ' ') {
                    break;
                }
            }
            if (!$lines) {
                throw new \RuntimeException('SMTP-server reageert niet.');
            }
            return [(int) substr($lines[0], 0, 3), implode("\n", $lines)];
        };
        $cmd = function (string $line, array $ok) use ($fp, $read): string {
            fwrite($fp, $line . "\r\n");
            [$code, $text] = $read();
            if (!in_array($code, $ok, true)) {
                throw new \RuntimeException("SMTP-fout na '" . (str_starts_with($line, 'AUTH') || strlen($line) > 60 ? substr($line, 0, 10) . '…' : $line) . "': $text");
            }
            return $text;
        };

        try {
            [$code, $text] = $read();
            if ($code !== 220) {
                throw new \RuntimeException("SMTP-begroeting: $text");
            }
            $ehlo = parse_url((string) Config::get('APP_URL', ''), PHP_URL_HOST) ?: 'localhost';
            $caps = $cmd('EHLO ' . $ehlo, [250]);

            if ($enc === 'tls') {
                $cmd('STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('STARTTLS mislukt.');
                }
                $caps = $cmd('EHLO ' . $ehlo, [250]);
            }
            if ($user !== null && $pass !== null) {
                if (stripos($caps, 'AUTH') === false) {
                    throw new \RuntimeException('Server ondersteunt geen AUTH.');
                }
                $cmd('AUTH PLAIN ' . base64_encode("\0" . $user . "\0" . $pass), [235]);
            }
            $cmd('MAIL FROM:<' . $from . '>', [250]);
            $cmd('RCPT TO:<' . $to . '>', [250, 251]);
            $cmd('DATA', [354]);
            $msg = $this->message($to, $from, $fromName, $subject, $body);
            $msg = preg_replace('/^\./m', '..', $msg) ?? $msg; // dot-stuffing
            fwrite($fp, $msg . "\r\n.\r\n");
            [$code, $text] = $read();
            if ($code !== 250) {
                throw new \RuntimeException("Bericht geweigerd: $text");
            }
            @fwrite($fp, "QUIT\r\n");
            return true;
        } finally {
            fclose($fp);
        }
    }
}

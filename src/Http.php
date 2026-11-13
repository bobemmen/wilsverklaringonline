<?php

declare(strict_types=1);

namespace WV;

/** Gooi dit om direct een foutpagina te tonen (404, 403, 429, ...). */
final class HttpException extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message ?: "HTTP $status");
    }
}

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $post,
        public readonly array $cookies,
        public readonly array $server,
    ) {
    }

    public static function capture(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = rawurldecode((string) parse_url($uri, PHP_URL_PATH));
        if ($path === '' || $path[0] !== '/') {
            $path = '/';
        }
        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            $path,
            $_GET,
            $_POST,
            $_COOKIE,
            $_SERVER
        );
    }

    public function input(string $key, string $default = ''): string
    {
        $v = $this->post[$key] ?? $this->query[$key] ?? $default;
        return is_string($v) ? $v : $default;
    }

    /** Geneste formuliergegevens, bijv. data[intro]. */
    public function inputArray(string $key): array
    {
        $v = $this->post[$key] ?? [];
        return is_array($v) ? $v : [];
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $v = $this->server[$key] ?? null;
        return is_string($v) ? $v : null;
    }

    public function isSecure(): bool
    {
        if (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') {
            return true;
        }
        return $this->fromTrustedProxy() && strtolower((string) $this->header('X-Forwarded-Proto')) === 'https';
    }

    private function trustedProxies(): array
    {
        return array_filter(array_map('trim', explode(',', (string) Config::get('TRUSTED_PROXIES', ''))));
    }

    private function fromTrustedProxy(): bool
    {
        return in_array((string) ($this->server['REMOTE_ADDR'] ?? ''), $this->trustedProxies(), true);
    }

    /** Client-IP; X-Forwarded-For wordt alleen vertrouwd vanaf een ingestelde proxy (TRUSTED_PROXIES). */
    public function ip(): string
    {
        $remote = (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
        if ($this->fromTrustedProxy() && ($xff = $this->header('X-Forwarded-For'))) {
            $trusted = $this->trustedProxies();
            $hops = array_reverse(array_map('trim', explode(',', $xff)));
            foreach ($hops as $hop) {
                if (filter_var($hop, FILTER_VALIDATE_IP) && !in_array($hop, $trusted, true)) {
                    return $hop;
                }
            }
        }
        return $remote;
    }

    public function userAgent(): string
    {
        return (string) ($this->server['HTTP_USER_AGENT'] ?? '');
    }
}

final class Response
{
    /** @param array<string,string> $headers */
    public function __construct(
        public string $body = '',
        public int $status = 200,
        public array $headers = [],
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function redirect(string $location, int $status = 303): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        return new self(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            $status,
            ['Content-Type' => 'application/json; charset=utf-8'] + $headers
        );
    }

    public static function download(string $body, string $filename, string $mime): self
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
        return new self($body, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $safe . '"',
            'Content-Length' => (string) strlen($body),
        ]);
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }
        echo $this->body;
    }
}

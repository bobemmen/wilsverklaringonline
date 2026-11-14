<?php

declare(strict_types=1);

namespace WV;

/**
 * Eenvoudige PHP-templates. In een template is $this deze klasse:
 *   <?php $this->layout('layouts/app', ['title' => 'Dashboard']) ?>
 *   <?= e($naam) ?>
 */
final class View
{
    private ?array $layout = null;

    public function __construct(
        private readonly App $app,
        private readonly string $dir,
    ) {
    }

    public function render(string $template, array $vars = []): string
    {
        $this->layout = null;
        $content = $this->capture($template, $vars);

        // een template kan een layout kiezen; die krijgt de inhoud als $content
        while ($this->layout !== null) {
            [$name, $layoutVars] = $this->layout;
            $this->layout = null;
            $content = $this->capture($name, ['content' => $content] + $layoutVars + $vars);
        }
        return $content;
    }

    public function layout(string $name, array $vars = []): void
    {
        $this->layout = [$name, $vars];
    }

    public function include(string $template, array $vars = []): void
    {
        $saved = $this->layout;
        echo $this->capture($template, $vars);
        $this->layout = $saved;
    }

    private function capture(string $template, array $vars): string
    {
        $file = $this->dir . '/' . $template . '.php';
        $fn = function (string $__file, array $__vars): string {
            extract($__vars, EXTR_SKIP);
            ob_start();
            try {
                include $__file;
            } catch (\Throwable $t) {
                ob_end_clean();
                throw $t;
            }
            return (string) ob_get_clean();
        };
        return $fn->call($this, $file, $vars);
    }

    // ----------------------------------------------------------- hulpfuncties

    /** Het logo (avondzon met gloed) als inline svg; de kleuren volgen het gekozen thema (zie .logo in app.css). */
    public function logo(int $size = 40, bool $decorative = true): string
    {
        static $n = 0;
        $id = 'logo-clip-' . (++$n);
        $a = $decorative ? ' aria-hidden="true"' : ' role="img" aria-label="Logo WilsVerklaring"';
        return '<svg class="logo" width="' . $size . '" height="' . $size . '" viewBox="0 0 64 64" focusable="false"' . $a . '>'
            . '<defs><clipPath id="' . $id . '"><circle cx="32" cy="32" r="28"/></clipPath></defs><g clip-path="url(#' . $id . ')">'
            . '<rect class="logo-bg" width="64" height="64"/>'
            . '<circle class="logo-sun" cx="36" cy="43" r="18" opacity="0.18"/><circle class="logo-sun" cx="36" cy="43" r="14" opacity="0.3"/><circle class="logo-sun" cx="36" cy="43" r="10"/>'
            . '<path class="logo-wave" d="M2 46C14 36 26 36 36 43S54 48 64 40V64H2z"/>'
            . '<path class="logo-bg" opacity="0.3" d="M2 54C16 48 30 48 40 53S56 56 64 52V64H2z"/>'
            . '</g></svg>';
    }

    public function url(string $path = '/'): string
    {
        return $this->app->url($path);
    }

    public function asset(string $file): string
    {
        // Versienummer = inhoud van het bestand: nieuwe CSS/JS krijgt een nieuwe URL, dus geen Cmd+Shift+R nodig.
        // De openbare map is de map van index.php (op hosting staat die los van de app-map).
        $public = defined('WV_PUBLIC_DIR') ? WV_PUBLIC_DIR : $this->app->root . '/public';
        $abs = $public . '/assets/' . $file;
        static $cache = [];
        $v = $cache[$abs] ??= is_file($abs) ? substr((string) md5_file($abs), 0, 10) : '0';
        return '/assets/' . $file . '?v=' . $v;
    }

    private ?Prefs $prefs = null;

    public function prefs(): Prefs
    {
        return $this->prefs ??= Prefs::fromRequest($this->app->request);
    }

    public function setPrefs(Prefs $prefs): void
    {
        $this->prefs = $prefs;
    }

    /** Attributen voor <html>: taal, tekstgrootte, contrast. */
    public function htmlAttrs(): string
    {
        return $this->prefs()->htmlAttrs();
    }

    private ?array $declaration = null;

    /** Verklaring van de ingelogde gebruiker (voor statusweergave in de kop). */
    public function declaration(): ?array
    {
        $user = $this->user();
        return $this->declaration ??= $user ? $this->app->store->declarationForUser($user['id']) : null;
    }

    public function textarea(string $name, string $value, string $placeholder = '', string $class = '', ?string $describedBy = null): string
    {
        // de eerste regeleinde direct na <textarea> wordt door browsers genegeerd, dus voeg er een toe
        return '<textarea id="' . e($name) . '" name="data[' . e($name) . ']"' . ($placeholder !== '' ? ' placeholder="' . e($placeholder) . '"' : '')
            . ($class !== '' ? ' class="' . e($class) . '"' : '') . ($describedBy ? ' aria-describedby="' . e($describedBy) . '"' : '')
            . ' maxlength="' . Content::MAX_FIELD_LENGTH . '">' . "\n" . e($value) . '</textarea>';
    }

    public function textInput(string $name, string $value, string $placeholder = '', string $type = 'text'): string
    {
        return '<input type="' . e($type) . '" id="' . e($name) . '" name="data[' . e($name) . ']" value="' . e($value)
            . '"' . ($placeholder !== '' ? ' placeholder="' . e($placeholder) . '"' : '') . ' maxlength="200">';
    }

    public function csrf(): string
    {
        return '<input type="hidden" name="_token" value="' . e($this->app->session->csrfToken()) . '">';
    }

    /** Staat de demo aan (DEMO_ENABLED=true in .env)? */
    public function demoEnabled(): bool
    {
        return \WV\Config::bool('DEMO_ENABLED', false);
    }

    public function isDemo(): bool
    {
        return $this->app->store->isDemo($this->user());
    }

    public function user(): ?array
    {
        return $this->app->auth->user();
    }

    /** Is de huidige pagina (of een subpagina van) $prefix? */
    public function active(string $prefix): bool
    {
        $path = $this->app->request->path;
        return $path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/');
    }

    public function flash(string $key, mixed $default = null): mixed
    {
        return $this->app->session->flashed($key, $default);
    }

    public function old(string $key, string $default = ''): string
    {
        $old = $this->app->session->flashed('old', []);
        $v = is_array($old) ? ($old[$key] ?? $default) : $default;
        return is_string($v) ? $v : $default;
    }

    /** Alle foutmeldingen van het vorige verzoek: veld => melding. */
    public function errors(): array
    {
        $errors = $this->app->session->flashed('errors', []);
        return is_array($errors) ? array_filter($errors, 'is_string') : [];
    }

    /** Foutmelding bij een veld (gekoppeld via aria-describedby, zie invalid()). */
    public function fieldError(string $key): string
    {
        $msg = $this->error($key);
        return $msg ? '<p class="field-error" id="' . e($key) . '-error">' . e($msg) . '</p>' : '';
    }

    /** Attributen voor een ongeldig veld. */
    public function invalid(string $key): string
    {
        return $this->error($key) ? ' aria-invalid="true" aria-describedby="' . e($key) . '-error"' : '';
    }

    /** Link naar /weergave die de wijziging doorvoert en daarna terugkeert naar deze pagina. */
    public function prefLink(array $change): string
    {
        $req = $this->app->request;
        $back = $req->path;
        $qs = http_build_query(array_filter($req->query, 'is_string'));
        if ($qs !== '' && preg_match('/^[A-Za-z0-9=&_-]+$/', $qs)) {
            $back .= '?' . $qs;
        }
        return '/weergave?' . http_build_query($change + ['terug' => $back]);
    }

    public function error(string $key): ?string
    {
        $errors = $this->app->session->flashed('errors', []);
        return is_array($errors) ? ($errors[$key] ?? null) : null;
    }

    /** Keuzekaart met een echt keuzerondje (groot, goed te raken); zit binnen een <fieldset>. */
    public function radio(string $name, string $value, string $label, ?string $help, mixed $current): string
    {
        $checked = $current === $value;
        return '<label class="choice' . ($checked ? ' selected' : '') . '">'
            . '<input type="radio" name="data[' . e($name) . ']" value="' . e($value) . '"' . ($checked ? ' checked' : '') . '>'
            . '<span><span class="choice-label">' . $label . '</span>'
            . ($help ? '<span class="choice-help">' . $help . '</span>' : '')
            . (isset(Content::MEANINGS[$name . '.' . $value])
                ? '<span class="choice-meaning"><strong>Wat betekent deze keuze?</strong>' . e(Content::MEANINGS[$name . '.' . $value]) . '</span>'
                : '')
            . '</span></label>';
    }
}

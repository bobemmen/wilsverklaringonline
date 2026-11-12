<?php

declare(strict_types=1);

namespace WV;

/**
 * Weergave-instellingen (tekstgrootte, contrast, ruimte). Staan in een gewone cookie `wv_prefs`
 * zodat ze ook voor het inloggen werken, door de server én door JavaScript te lezen zijn,
 * en bevatten geen persoonsgegevens.
 */
final class Prefs
{
    public const COOKIE = 'wv_prefs';
    public const MAX_SIZE = 4;
    public const CONTRASTS = ['standaard', 'hoog', 'donker'];
    public const SIZE_LABELS = [1 => 'Normaal', 2 => 'Groot', 3 => 'Extra groot', 4 => 'Maximaal'];

    public function __construct(
        public readonly int $size = 1,
        public readonly string $contrast = 'standaard',
        public readonly bool $spacious = false,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $raw = $request->cookies[self::COOKIE] ?? '';
        $data = [];
        if (is_string($raw) && strlen($raw) < 100) {
            parse_str($raw, $data);
        }
        return self::fromArray($data);
    }

    public static function fromArray(array $data, ?self $base = null): self
    {
        $base ??= new self();
        $size = $base->size;
        if (isset($data['t']) && is_scalar($data['t']) && ctype_digit((string) $data['t'])) {
            $size = max(1, min(self::MAX_SIZE, (int) $data['t']));
        }
        $contrast = $base->contrast;
        if (isset($data['c']) && is_string($data['c']) && in_array($data['c'], self::CONTRASTS, true)) {
            $contrast = $data['c'];
        }
        $spacious = $base->spacious;
        if (isset($data['r']) && is_scalar($data['r'])) {
            $spacious = (string) $data['r'] === '1';
        }
        return new self($size, $contrast, $spacious);
    }

    public function cookieValue(): string
    {
        return 't=' . $this->size . '&c=' . $this->contrast . '&r=' . ($this->spacious ? '1' : '0');
    }

    public function isDefault(): bool
    {
        return $this->size === 1 && $this->contrast === 'standaard' && !$this->spacious;
    }

    /** Attributen voor het <html>-element. */
    public function htmlAttrs(): string
    {
        $classes = [];
        if ($this->size > 1) {
            $classes[] = 't' . $this->size;
        }
        if ($this->spacious) {
            $classes[] = 'ruim';
        }
        return ' lang="nl"'
            . ($classes ? ' class="' . implode(' ', $classes) . '"' : '')
            . ($this->contrast !== 'standaard' ? ' data-contrast="' . $this->contrast . '"' : '');
    }
}

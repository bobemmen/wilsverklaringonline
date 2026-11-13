<?php

declare(strict_types=1);

namespace WV;

/**
 * Juridische teksten (docs/juridisch/*.md) worden in de app getoond. Gegevens van de exploitant komen uit .env,
 * zodat de teksten in de repository neutraal blijven.
 */
final class Legal
{
    /** Verhogen als de pilotinformatie of privacyverklaring inhoudelijk wijzigt; wordt bij de toestemming vastgelegd. */
    public const VERSION = '2026-11-pilot-3';

    public const PAGES = [
        'pilot' => ['pilotinformatie.md', 'Pilotinformatie'],
        'privacy' => ['privacyverklaring.md', 'Privacyverklaring'],
        'voorwaarden' => ['gebruiksvoorwaarden.md', 'Gebruiksvoorwaarden'],
    ];

    public static function placeholders(): array
    {
        $fallback = fn(string $k, string $label) => Config::get($k) ?: '[' . $label . ' invullen in .env]';
        return [
            '{{exploitant}}' => $fallback('OPERATOR_NAME', 'naam exploitant'),
            '{{exploitant_adres}}' => $fallback('OPERATOR_ADDRESS', 'adres exploitant'),
            '{{privacy_contact}}' => $fallback('PRIVACY_CONTACT_EMAIL', 'e-mailadres privacycontact'),
            '{{praktijk}}' => Config::get('PILOT_PRACTICE_NAME') ?: '[naam pilotpraktijk invullen in .env]',
            '{{versie}}' => self::VERSION,
        ];
    }

    public static function render(string $root, string $key): ?array
    {
        if (!isset(self::PAGES[$key])) {
            return null;
        }
        [$file, $title] = self::PAGES[$key];
        $path = $root . '/docs/juridisch/' . $file;
        if (!is_file($path)) {
            return null;
        }
        $text = strtr((string) file_get_contents($path), self::placeholders());
        return ['title' => $title, 'html' => self::markdown($text)];
    }

    /** Heel kleine Markdown-weergave: koppen, alinea's, lijsten, tabellen, **vet**, *cursief*, [links](/pad). Alles wordt eerst ge-escaped. */
    public static function markdown(string $md): string
    {
        $inline = function (string $s): string {
            $s = htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s);
            $s = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/s', '<em>$1</em>', $s);
            return preg_replace_callback('/\[([^\]]+)\]\((\/[A-Za-z0-9\/_\-#.]*|https:\/\/[^\s)]+)\)/', fn($m) => '<a href="' . $m[2] . '">' . $m[1] . '</a>', $s);
        };
        $out = [];
        $list = null;
        $para = [];
        $table = [];
        $flush = function () use (&$out, &$para, &$list, &$table, $inline) {
            if ($para) {
                $out[] = '<p>' . $inline(implode(' ', $para)) . '</p>';
                $para = [];
            }
            if ($list) {
                $out[] = "<$list[0]>" . implode('', array_map(fn($i) => '<li>' . $inline($i) . '</li>', $list[1])) . "</$list[0]>";
                $list = null;
            }
            if ($table) {
                $head = array_shift($table);
                $rows = '';
                foreach ($table as $r) {
                    $rows .= '<tr>' . implode('', array_map(fn($c) => '<td>' . $inline($c) . '</td>', $r)) . '</tr>';
                }
                $out[] = '<div class="table-wrap"><table><thead><tr>' . implode('', array_map(fn($c) => '<th scope="col">' . $inline($c) . '</th>', $head)) . '</tr></thead><tbody>' . $rows . '</tbody></table></div>';
                $table = [];
            }
        };
        foreach (preg_split('/\R/', $md) as $line) {
            $t = rtrim($line);
            if ($t === '') {
                $flush();
            } elseif (preg_match('/^(#{1,4})\s+(.*)$/', $t, $m)) {
                $flush();
                $lvl = min(4, strlen($m[1]) + 0);
                $out[] = "<h$lvl>" . $inline($m[2]) . "</h$lvl>";
            } elseif (preg_match('/^\s*[-*]\s+(.*)$/', $t, $m)) {
                if ($para || ($list && $list[0] !== 'ul') || $table) { $flush(); }
                $list ??= ['ul', []];
                $list[1][] = $m[1];
            } elseif (preg_match('/^\s*\d+[.)]\s+(.*)$/', $t, $m)) {
                if ($para || ($list && $list[0] !== 'ol') || $table) { $flush(); }
                $list ??= ['ol', []];
                $list[1][] = $m[1];
            } elseif (str_starts_with($t, '|')) {
                if ($para || $list) { $flush(); }
                if (preg_match('/^\|[\s:|-]+\|$/', $t)) {
                    continue;
                }
                $table[] = array_map('trim', explode('|', trim($t, '|')));
            } elseif ($list && preg_match('/^\s{2,}\S/', $line)) {
                $list[1][count($list[1]) - 1] .= ' ' . trim($t);
            } else {
                if ($list || $table) { $flush(); }
                $para[] = trim($t);
            }
        }
        $flush();
        return implode("\n", $out);
    }
}

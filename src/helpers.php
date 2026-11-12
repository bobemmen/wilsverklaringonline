<?php

declare(strict_types=1);

/** HTML-escape (alles wat de gebruiker invoert gaat hier doorheen). */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escaped tekst met regeleinden als <br>. */
function nl2br_e(string $value): string
{
    return nl2br(e($value), false);
}

/** Eerste letter hoofdletter, multibyte-veilig. */
function ucfirst_mb(string $value): string
{
    return mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1);
}

/** Datumnotatie dd-mm-jjjj (Europe/Amsterdam, zie bootstrap). */
function fmt_date(?int $ts): string
{
    return $ts ? date('d-m-Y', $ts) : '';
}

function fmt_datetime(?int $ts): string
{
    return $ts ? date('d-m-Y H:i', $ts) : '';
}

/** "2026-11-01" (veld van een datumkiezer) → "01-11-2026"; andere waarden blijven ongewijzigd. */
function fmt_iso_date(string $value): string
{
    return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) ? "$m[3]-$m[2]-$m[1]" : $value;
}

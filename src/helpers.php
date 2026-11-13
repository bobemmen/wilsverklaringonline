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

/**
 * Geboortedatum uit een formulier of zoekveld: accepteert 1985-03-07, 07-03-1985, 7-3-1985, 7/3/1985, 7.3.1985.
 * Geeft de ISO-vorm (1985-03-07) terug, of null bij een ongeldige datum of een jaartal buiten 1900 tot vandaag.
 */
function parse_dob(string $value): ?string
{
    $value = trim($value);
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $m)) {
        [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
    } elseif (preg_match('/^(\d{1,2})[-\/. ](\d{1,2})[-\/. ](\d{4})$/', $value, $m)) {
        [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
    } else {
        return null;
    }
    if (!checkdate($mo, $d, $y) || $y < 1900) {
        return null;
    }
    $iso = sprintf('%04d-%02d-%02d', $y, $mo, $d);
    return $iso > date('Y-m-d') ? null : $iso;
}

/** "2026-11-01" (veld van een datumkiezer) → "01-11-2026"; andere waarden blijven ongewijzigd. */
function fmt_iso_date(string $value): string
{
    return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) ? "$m[3]-$m[2]-$m[1]" : $value;
}

/** "1950-03-07" → DateTimeImmutable, of null als het geen echte datum in het verleden is. */
function parse_birthdate(string $value): ?DateTimeImmutable
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));
    if (!$d || $d->format('Y-m-d') !== trim($value)) {
        return null;
    }
    return ($d < new DateTimeImmutable('today') && (int) $d->format('Y') >= 1900) ? $d : null;
}

/** Mobiel nummer naar internationaal formaat (+316…), of null als het niet klopt. */
function normalize_phone(string $value): ?string
{
    $v = preg_replace('/[\s().-]/', '', $value) ?? '';
    if (str_starts_with($v, '00')) {
        $v = '+' . substr($v, 2);
    } elseif (str_starts_with($v, '0')) {
        $v = '+31' . substr($v, 1);
    }
    return preg_match('/^\+[1-9][0-9]{9,14}$/', $v) ? $v : null;
}

/** "+31612345678" → "+31 6•• ••• 78" */
function mask_phone(string $phone): string
{
    return substr($phone, 0, 4) . ' ••• ••• ' . substr($phone, -2);
}

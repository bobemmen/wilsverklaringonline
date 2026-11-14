<?php

declare(strict_types=1);

namespace WV;

/**
 * Minimale PDF-schrijver (A4, WinAnsi). Het lettertype Lexend (SIL OFL, src/data/fonts) wordt ingebed,
 * zodat de pdf dezelfde letter en kleuren heeft als de website. Geen externe bibliotheken: de tekstbreedtes
 * komen uit src/data/pdf_font_widths_lexend.php (uit de lettertypebestanden gelezen), zodat regelafbreking klopt.
 */
final class Pdf
{
    public const PAGE_W = 595.28;
    public const PAGE_H = 841.89;
    public const MARGIN = 56.7; // 2 cm

    // Kleuren van de website (zie app.css)
    public const TEXT = [30, 43, 34];
    public const MUTED = [70, 86, 75];
    public const BORDER = [207, 219, 210];
    public const BRAND = [47, 107, 79];
    public const BRAND_STRONG = [31, 74, 54];
    public const TINT = [221, 235, 223];
    public const SURFACE_ALT = [245, 248, 244];
    public const OK = [20, 83, 45];
    public const OK_BG = [227, 243, 234];

    /** resource, lettertypenaam, bestand */
    private const FONTS = [
        'L' => ['F1', 'Lexend-Regular', 'Lexend-Regular.ttf'],
        'LB' => ['F2', 'Lexend-SemiBold', 'Lexend-SemiBold.ttf'],
    ];
    private const FONT_BBOX = [
        'L' => [-209, -245, 1070, 1080],
        'LB' => [-213, -246, 1126, 1127],
    ];

    private array $widths;
    /** @var list<string> */
    private array $pages = [];
    private string $stream = '';
    private float $y;
    /** Vergrotingsfactor voor tekst (1.0 = normaal, 1.4 = grote letters). */
    public float $scale = 1.0;

    public function __construct(private readonly string $title = '')
    {
        $this->widths = require __DIR__ . '/data/pdf_font_widths_lexend.php';
        $this->y = self::PAGE_H - self::MARGIN;
    }

    // ------------------------------------------------------------ tekstmaat

    private function enc(string $s): string
    {
        $s = str_replace(["\t", "\r"], [' ', ''], $s);
        return mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
    }

    private function textWidth(string $font, string $encoded, float $size, float $charSpace = 0.0): float
    {
        $w = 0.0;
        $table = $this->widths[$font];
        $n = strlen($encoded);
        for ($i = 0; $i < $n; $i++) {
            $w += $table[ord($encoded[$i])] ?: 600;
        }
        return $w * $size / 1000 + $charSpace * $n;
    }

    /** @return list<string> regels (al in Windows-1252) */
    private function wrap(string $font, string $text, float $size, float $maxWidth, float $charSpace = 0.0): array
    {
        $out = [];
        foreach (explode("\n", $this->enc($text)) as $para) {
            $words = preg_split('/ +/', rtrim($para)) ?: [''];
            $line = '';
            foreach ($words as $word) {
                $try = $line === '' ? $word : $line . ' ' . $word;
                if ($this->textWidth($font, $try, $size, $charSpace) <= $maxWidth) {
                    $line = $try;
                    continue;
                }
                if ($line !== '') {
                    $out[] = $line;
                    $line = '';
                }
                // woord langer dan een regel: hard afbreken (lineair: breedte per teken optellen)
                $table = $this->widths[$font];
                $chunk = '';
                $w = 0.0;
                for ($i = 0, $n = strlen($word); $i < $n; $i++) {
                    $cw = ($table[ord($word[$i])] ?: 600) * $size / 1000 + $charSpace;
                    if ($chunk !== '' && $w + $cw > $maxWidth) {
                        $out[] = $chunk;
                        $chunk = '';
                        $w = 0.0;
                    }
                    $chunk .= $word[$i];
                    $w += $cw;
                }
                $word = $chunk;
                $line = $word;
            }
            $out[] = $line;
        }
        return $out;
    }

    private static function esc(string $encoded): string
    {
        return strtr($encoded, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']);
    }

    private static function rgb(array $c): string
    {
        return sprintf('%.3F %.3F %.3F', $c[0] / 255, $c[1] / 255, $c[2] / 255);
    }

    // ---------------------------------------------------------- paginabeheer

    private function ensure(float $height): void
    {
        if ($this->y - $height < self::MARGIN + 18) {
            $this->newPage();
        }
    }

    public function newPage(): void
    {
        if ($this->stream !== '') {
            $this->pages[] = $this->stream;
        }
        $this->stream = '';
        $this->y = self::PAGE_H - self::MARGIN;
    }

    public function space(float $points): void
    {
        $this->y -= $points;
    }

    public function contentWidth(): float
    {
        return self::PAGE_W - 2 * self::MARGIN;
    }

    // ------------------------------------------------------------- tekenen

    /**
     * Schrijft een alinea. $align: 'L' of 'C'. $indent verschuift de linkerrand.
     */
    public function text(
        string $text,
        string $font = 'L',
        float $size = 10,
        array $color = self::TEXT,
        float $indent = 0.0,
        string $align = 'L',
        float $charSpace = 0.0,
        float $leadingFactor = 1.4,
        float $keepWithNext = 0.0,
    ): void {
        $size *= $this->scale;
        $maxW = $this->contentWidth() - $indent;
        $lines = $this->wrap($font, $text, $size, $maxW, $charSpace);
        $leading = $size * $leadingFactor;
        $first = true;
        foreach ($lines as $line) {
            $this->ensure($leading + ($first ? $keepWithNext : 0.0));
            $first = false;
            $w = $this->textWidth($font, $line, $size, $charSpace);
            $x = self::MARGIN + $indent + ($align === 'C' ? ($maxW - $w) / 2 : 0.0);
            $baseline = $this->y - $size;
            $this->stream .= sprintf(
                "BT /%s %.2F Tf %s rg %.2F Tc %.2F %.2F Td (%s) Tj ET\n",
                self::FONTS[$font][0],
                $size,
                self::rgb($color),
                $charSpace,
                $x,
                $baseline,
                self::esc($line)
            );
            $this->y -= $leading;
        }
    }

    /** Tekst op een vaste plek (voor kolommen). Geen paginabeheer. */
    public function textAt(float $x, float $y, string $text, string $font, float $size, array $color, float $charSpace = 0.0, bool $encoded = false): void
    {
        $this->stream .= sprintf(
            "BT /%s %.2F Tf %s rg %.2F Tc %.2F %.2F Td (%s) Tj ET\n",
            self::FONTS[$font][0],
            $size,
            self::rgb($color),
            $charSpace,
            $x,
            $y,
            self::esc($encoded ? $text : $this->enc($text))
        );
    }

    public function hr(array $color = self::BORDER, ?float $x1 = null, ?float $x2 = null, float $width = 0.6): void
    {
        $this->stream .= sprintf(
            "%s RG %.2F w %.2F %.2F m %.2F %.2F l S\n",
            self::rgb($color),
            $width,
            $x1 ?? self::MARGIN,
            $this->y,
            $x2 ?? (self::PAGE_W - self::MARGIN),
            $this->y
        );
    }

    /** Afgeronde rechthoek; $r = [linksboven, rechtsboven, rechtsonder, linksonder]. $stroke null = geen rand. */
    public function roundRect(float $x, float $yTop, float $w, float $h, array $r, array $fill, ?array $stroke = null, float $lineW = 1.0): void
    {
        $k = 0.5523;
        $y = $yTop - $h;
        [$tl, $tr, $br, $bl] = $r;
        $p = sprintf('%.2F %.2F m ', $x + $bl, $y);
        $p .= sprintf('%.2F %.2F l ', $x + $w - $br, $y);
        $p .= sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x + $w - $br + $br * $k, $y, $x + $w, $y + $br - $br * $k, $x + $w, $y + $br);
        $p .= sprintf('%.2F %.2F l ', $x + $w, $y + $h - $tr);
        $p .= sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x + $w, $y + $h - $tr + $tr * $k, $x + $w - $tr + $tr * $k, $y + $h, $x + $w - $tr, $y + $h);
        $p .= sprintf('%.2F %.2F l ', $x + $tl, $y + $h);
        $p .= sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x + $tl - $tl * $k, $y + $h, $x, $y + $h - $tl + $tl * $k, $x, $y + $h - $tl);
        $p .= sprintf('%.2F %.2F l ', $x, $y + $bl);
        $p .= sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c h ', $x, $y + $bl - $bl * $k, $x + $bl - $bl * $k, $y, $x + $bl, $y);
        $this->stream .= self::rgb($fill) . ' rg ';
        if ($stroke !== null) {
            $this->stream .= sprintf('%s RG %.2F w %s B' . "\n", self::rgb($stroke), $lineW, $p);
        } else {
            $this->stream .= $p . "f\n";
        }
    }

    // ------------------------------------------------- opgemaakte onderdelen

    /** Titel bovenaan (gecentreerd). */
    public function title(string $text): void
    {
        $this->text($text, 'LB', 22, self::TEXT, 0, 'C', 0.0, 1.25);
    }

    public function subtitle(string $text): void
    {
        $this->text($text, 'L', 9.5, self::MUTED, 0, 'C');
    }

    /** Kleine kleurige regel boven de titel (zoals "Wilsverklaring van" op de website). */
    public function eyebrow(string $text): void
    {
        $this->text($text, 'LB', 9.5, self::BRAND, 0, 'C', 0.0, 1.4);
        $this->space(2);
    }

    /** Onderwerpkop met lijn eronder. */
    public function heading(string $text): void
    {
        $this->ensure(70);
        $this->space(18);
        $this->text($text, 'LB', 15, self::TEXT, 0, 'L', 0.0, 1.3, 50);
        $this->space(2);
        $this->hr(self::BORDER, null, null, 1.5);
        $this->space(10);
    }

    public function subheading(string $text): void
    {
        $this->space(4);
        $this->text($text, 'LB', 11.5, self::TEXT, 0, 'L', 0.0, 1.3, 40);
        $this->space(2);
    }

    /** Kopje met daaronder een alinea (zoals h3 en tekst op de website). */
    public function field(string $label, string $value): void
    {
        $this->space(4);
        $this->text($label, 'LB', 11.5, self::TEXT, 0, 'L', 0.0, 1.3, 40);
        $this->space(2);
        $this->text($value, 'L', 10, self::TEXT, 0, 'L', 0.0, 1.5);
        $this->space(6);
    }

    /** Label met de gekozen waarde als groen label (zoals de keuzes op de website). */
    public function choice(string $label, string $value): void
    {
        $k = $this->scale;
        $h = 18 * $k;
        $this->ensure($h + 10);
        $this->space(2);
        $top = $this->y;
        $fs = 9.5 * $k;
        $baseline = $top - $h / 2 - $fs * 0.35;
        $this->textAt(self::MARGIN, $baseline, $label, 'LB', $fs, self::MUTED);
        $labelW = $this->textWidth('LB', $this->enc($label), $fs);
        $x = self::MARGIN + max($labelW + 14, 150 * $k);
        $val = Content::choiceLabel($value);
        $w = $this->textWidth('LB', $this->enc($val), $fs) + 20 * $k;
        $maxW = self::PAGE_W - self::MARGIN - $x;
        if ($w > $maxW) { // lang label: onder de naam laten staan
            $x = self::MARGIN;
            $top -= $h;
            $this->y = $top;
            $this->ensure($h + 4);
            $top = $this->y;
            $baseline = $top - $h / 2 - $fs * 0.35;
        }
        $this->roundRect($x, $top, $w, $h, [$h / 2, $h / 2, $h / 2, $h / 2], self::TINT, self::BRAND, 1.1);
        $this->textAt($x + 10 * $k, $baseline, $val, 'LB', $fs, self::BRAND_STRONG);
        $this->y = $top - $h - 6;
    }

    /** Toelichting in een afgerond vlak (zoals het citaatvlak op de website). */
    public function quote(string $text): void
    {
        $this->space(4);
        $fs = 10 * $this->scale;
        $pad = 11;
        $lines = $this->wrap('L', $text, $fs, $this->contentWidth() - 2 * $pad - 4);
        $leading = $fs * 1.5;
        $i = 0;
        $n = count($lines);
        $first = true;
        while ($i < $n) {
            $this->ensure($leading + 2 * $pad);
            $room = (int) floor(($this->y - self::MARGIN - 18 - 2 * $pad) / $leading);
            $take = max(1, min($n - $i, $room));
            $h = $take * $leading + 2 * $pad;
            $last = $i + $take >= $n;
            $big = 14;
            $this->roundRect(self::MARGIN, $this->y, $this->contentWidth(), $h, [$first ? $big : 3, $first ? $big : 3, $last ? $big : 3, $last ? 4 : 3], self::SURFACE_ALT);
            $yy = $this->y - $pad;
            for ($j = 0; $j < $take; $j++) {
                $this->textAt(self::MARGIN + $pad + 2, $yy - $fs - ($leading - $fs) / 2 + 1, $lines[$i + $j], 'L', $fs, self::MUTED, 0.0, true);
                $yy -= $leading;
            }
            $this->y -= $h;
            $i += $take;
            $first = false;
            if (!$last) {
                $this->newPage();
            }
        }
        $this->space(6);
    }

    /** Handtekeningblok: naam/datum, daaronder lijnen voor handtekening en plaats. */
    public function signature(string $name, string $date, string $place = ''): void
    {
        $this->ensure(120);
        $this->space(30);
        $this->hr(self::BORDER, null, null, 1.5);
        $this->space(10);
        $right = self::PAGE_W - self::MARGIN;
        $y = $this->y - 8;
        $this->textAt(self::MARGIN, $y, 'Ondertekend door', 'LB', 8.5, self::BRAND);
        $this->textAt($right - 140, $y, 'Datum', 'LB', 8.5, self::BRAND);
        $this->textAt(self::MARGIN, $y - 15, $name, 'L', 10, self::TEXT);
        $this->textAt($right - 140, $y - 15, $date, 'L', 10, self::TEXT);
        $this->y -= 62;
        $this->hr(self::BORDER, self::MARGIN, self::MARGIN + 290);
        $this->hr(self::BORDER, $right - 190, $right);
        if ($place !== '') {
            $this->textAt($right - 190, $this->y + 5, $place, 'L', 10, self::TEXT);
        }
        $this->textAt(self::MARGIN, $this->y - 13, 'Handtekening', 'L', 8.5, self::MUTED);
        $this->textAt($right - 190, $this->y - 13, 'Plaats', 'L', 8.5, self::MUTED);
        $this->y -= 24;
    }

    /** Afgerond vlak met kopje en tekst; $ok = groen (bevestiging), anders neutraal. */
    public function noteBox(string $label, string $text, bool $ok = false): void
    {
        $lines = $this->wrap('L', $text, 9.5, $this->contentWidth() - 28);
        $h = 14 + 16 + count($lines) * 14 + 8;
        $this->ensure($h + 20);
        $this->space(16);
        $this->roundRect(self::MARGIN, $this->y, $this->contentWidth(), $h, [14, 14, 14, 14], $ok ? self::OK_BG : self::SURFACE_ALT);
        $this->textAt(self::MARGIN + 14, $this->y - 24, $label, 'LB', 10.5, $ok ? self::OK : self::TEXT);
        $yy = $this->y - 40;
        foreach ($lines as $line) {
            $this->textAt(self::MARGIN + 14, $yy, $line, 'L', 9.5, self::TEXT, 0.0, true);
            $yy -= 14;
        }
        $this->y -= $h;
    }

    // -------------------------------------------------------------- uitvoer

    public function output(): string
    {
        $this->newPage();
        if (!$this->pages) {
            $this->pages[] = '';
        }
        $total = count($this->pages);

        $objects = [];           // nr => inhoud
        $add = function (string $body) use (&$objects): int {
            $objects[] = $body;
            return count($objects);
        };

        $catalog = $add('');     // 1
        $pagesObj = $add('');    // 2
        $info = $add(sprintf(
            '<< /Title <%s> /Producer (WilsVerklaring) /CreationDate (D:%s) >>',
            strtoupper(bin2hex("\xFE\xFF" . mb_convert_encoding($this->title, 'UTF-16BE', 'UTF-8'))),
            gmdate('YmdHis') . 'Z'
        ));
        $fontRefs = [];
        foreach (self::FONTS as $key => [$res, $base, $file]) {
            $ttf = (string) file_get_contents(__DIR__ . '/data/fonts/' . $file);
            $z = gzcompress($ttf, 9);
            $ff = $add(sprintf("<< /Length %d /Length1 %d /Filter /FlateDecode >>\nstream\n%s\nendstream", strlen($z), strlen($ttf), $z));
            $bb = self::FONT_BBOX[$key];
            $fd = $add(sprintf(
                '<< /Type /FontDescriptor /FontName /%s /Flags 32 /FontBBox [%d %d %d %d] /ItalicAngle 0 /Ascent 1000 /Descent -250 /CapHeight 700 /StemV 80 /FontFile2 %d 0 R >>',
                $base, $bb[0], $bb[1], $bb[2], $bb[3], $ff
            ));
            $w = implode(' ', array_slice($this->widths[$key], 32, 224));
            $n = $add(sprintf(
                '<< /Type /Font /Subtype /TrueType /BaseFont /%s /FirstChar 32 /LastChar 255 /Widths [%s] /FontDescriptor %d 0 R /Encoding /WinAnsiEncoding >>',
                $base, $w, $fd
            ));
            $fontRefs[] = "/$res $n 0 R";
        }
        $resources = '<< /Font << ' . implode(' ', $fontRefs) . ' >> >>';

        $kids = [];
        foreach ($this->pages as $i => $content) {
            $footer = sprintf(
                "BT /F1 8 Tf %s rg 0 Tc %.2F %.2F Td (%s) Tj ET\n",
                self::rgb(self::MUTED),
                self::PAGE_W / 2 - 30,
                28,
                self::esc('Pagina ' . ($i + 1) . ' van ' . $total)
            );
            $data = gzcompress($content . $footer, 6);
            $contentObj = $add(sprintf("<< /Length %d /Filter /FlateDecode >>\nstream\n%s\nendstream", strlen($data), $data));
            $pageObj = $add(sprintf(
                '<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %.2F %.2F] /Resources %s /Contents %d 0 R >>',
                $pagesObj,
                self::PAGE_W,
                self::PAGE_H,
                $resources,
                $contentObj
            ));
            $kids[] = "$pageObj 0 R";
        }
        $objects[$catalog - 1] = sprintf('<< /Type /Catalog /Pages %d 0 R >>', $pagesObj);
        $objects[$pagesObj - 1] = sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', implode(' ', $kids), $total);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $i => $body) {
            $offsets[$i + 1] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        $pdf .= sprintf(
            "trailer\n<< /Size %d /Root %d 0 R /Info %d 0 R >>\nstartxref\n%d\n%%%%EOF\n",
            count($objects) + 1,
            $catalog,
            $info,
            $xref
        );
        return $pdf;
    }
}

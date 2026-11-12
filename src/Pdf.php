<?php

declare(strict_types=1);

namespace WV;

/**
 * Minimale PDF-schrijver (A4, standaard Helvetica/Times-lettertypen, WinAnsi).
 * Geen externe bibliotheken: de tekstbreedtes komen uit de Adobe AFM-metrics
 * (src/data/pdf_font_widths.php), zodat regelafbreking klopt.
 */
final class Pdf
{
    public const PAGE_W = 595.28;
    public const PAGE_H = 841.89;
    public const MARGIN = 56.7; // 2 cm

    private const FONTS = [
        'H' => ['F1', 'Helvetica'],
        'HB' => ['F2', 'Helvetica-Bold'],
        'T' => ['F3', 'Times-Roman'],
        'TB' => ['F4', 'Times-Bold'],
        'TI' => ['F5', 'Times-Italic'],
    ];

    private array $widths;
    /** @var list<string> */
    private array $pages = [];
    private string $stream = '';
    private float $y;

    public function __construct(private readonly string $title = '')
    {
        $this->widths = require __DIR__ . '/data/pdf_font_widths.php';
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
            $w += $table[ord($encoded[$i])] ?? 500;
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
                    $cw = ($table[ord($word[$i])] ?? 500) * $size / 1000 + $charSpace;
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
        string $font = 'H',
        float $size = 10.5,
        array $color = [44, 44, 42],
        float $indent = 0.0,
        string $align = 'L',
        float $charSpace = 0.0,
        float $leadingFactor = 1.4,
        float $keepWithNext = 0.0,
    ): void {
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
    public function textAt(float $x, float $y, string $text, string $font, float $size, array $color, float $charSpace = 0.0): void
    {
        $this->stream .= sprintf(
            "BT /%s %.2F Tf %s rg %.2F Tc %.2F %.2F Td (%s) Tj ET\n",
            self::FONTS[$font][0],
            $size,
            self::rgb($color),
            $charSpace,
            $x,
            $y,
            self::esc($this->enc($text))
        );
    }

    public function hr(array $color = [211, 209, 199], ?float $x1 = null, ?float $x2 = null, float $width = 0.6): void
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

    /** Verticale balk links van een citaat. */
    private function bar(float $yTop, float $yBottom, array $color): void
    {
        $this->stream .= sprintf(
            "%s RG 1.5 w %.2F %.2F m %.2F %.2F l S\n",
            self::rgb($color),
            self::MARGIN + 2,
            $yTop,
            self::MARGIN + 2,
            $yBottom
        );
    }

    public function fillRect(float $x, float $yTop, float $w, float $h, array $fill, array $stroke): void
    {
        $this->stream .= sprintf(
            "%s rg %s RG 0.6 w %.2F %.2F %.2F %.2F re B\n",
            self::rgb($fill),
            self::rgb($stroke),
            $x,
            $yTop - $h,
            $w,
            $h
        );
    }

    // ------------------------------------------------- opgemaakte onderdelen

    public function title(string $text): void
    {
        $this->text($text, 'T', 24, [38, 33, 92], 0, 'C', 0.0, 1.25);
    }

    public function subtitle(string $text): void
    {
        $this->text($text, 'H', 9.5, [136, 135, 128], 0, 'C');
    }

    public function heading(string $text): void
    {
        $this->ensure(60);
        $this->space(14);
        $this->text($text, 'T', 15, [38, 33, 92], 0, 'L', 0.0, 1.3, 40);
        $this->space(1);
        $this->hr();
        $this->space(7);
    }

    public function subheading(string $text): void
    {
        $this->space(6);
        $this->text($text, 'T', 12, [38, 33, 92], 0, 'L', 0.0, 1.3, 30);
        $this->space(2);
    }

    /** Veld met klein hoofdletterlabel en daaronder de tekst. */
    public function field(string $label, string $value): void
    {
        $this->space(4);
        $this->text(mb_strtoupper($label), 'H', 7.5, [136, 135, 128], 0, 'L', 0.7, 1.4, 30);
        $this->text($value, 'H', 10.5, [44, 44, 42]);
    }

    /** Label gevolgd door een waarde in vet (voor keuzes zoals "Ja"/"Nee"). */
    public function choice(string $label, string $value): void
    {
        $this->ensure(18);
        $this->space(4);
        $baseline = $this->y - 10.5;
        $this->textAt(self::MARGIN, $baseline, mb_strtoupper($label), 'H', 7.5, [136, 135, 128], 0.7);
        $labelW = $this->textWidth('H', $this->enc(mb_strtoupper($label)), 7.5, 0.7);
        $this->textAt(self::MARGIN + max($labelW + 10, 150), $baseline, ucfirst_mb($value), 'HB', 10.5, [38, 33, 92]);
        $this->y -= 15;
    }

    /** Toelichting als citaat met balk. */
    public function quote(string $text): void
    {
        $this->space(4);
        $lines = $this->wrap('TI', $text, 11, $this->contentWidth() - 16);
        $leading = 11 * 1.4;
        foreach ($lines as $line) {
            $this->ensure($leading);
            $top = $this->y;
            $this->stream .= sprintf(
                "BT /F5 11 Tf %s rg 0 Tc %.2F %.2F Td (%s) Tj ET\n",
                self::rgb([68, 68, 68]),
                self::MARGIN + 12,
                $this->y - 11,
                self::esc($line)
            );
            $this->bar($top, $top - $leading, [175, 169, 236]);
            $this->y -= $leading;
        }
    }

    /** Handtekeningblok: naam/datum, daaronder lijnen voor handtekening en plaats. */
    public function signature(string $name, string $date): void
    {
        $this->ensure(110);
        $this->space(34);
        $this->hr();
        $this->space(10);
        $right = self::PAGE_W - self::MARGIN;
        $y = $this->y - 8;
        $this->textAt(self::MARGIN, $y, 'ONDERTEKEND DOOR', 'H', 7.5, [136, 135, 128], 0.7);
        $this->textAt($right - 140, $y, 'DATUM', 'H', 7.5, [136, 135, 128], 0.7);
        $this->textAt(self::MARGIN, $y - 13, $name, 'H', 10, [44, 44, 42]);
        $this->textAt($right - 140, $y - 13, $date, 'H', 10, [44, 44, 42]);
        $this->y -= 58;
        $this->hr([211, 209, 199], self::MARGIN, self::MARGIN + 290);
        $this->hr([211, 209, 199], $right - 190, $right);
        $this->textAt(self::MARGIN, $this->y - 12, 'Handtekening', 'H', 8.5, [136, 135, 128]);
        $this->textAt($right - 190, $this->y - 12, 'Plaats', 'H', 8.5, [136, 135, 128]);
        $this->y -= 22;
    }

    public function noteBox(string $label, string $text): void
    {
        $lines = $this->wrap('H', $text, 9.5, $this->contentWidth() - 20);
        $h = 14 + 11 + count($lines) * 13.3 + 4;
        $this->ensure($h + 20);
        $this->space(18);
        $this->fillRect(self::MARGIN, $this->y, $this->contentWidth(), $h, [241, 239, 232], [211, 209, 199]);
        $this->textAt(self::MARGIN + 10, $this->y - 17, mb_strtoupper($label), 'H', 7.5, [136, 135, 128], 0.7);
        $yy = $this->y - 31;
        foreach ($lines as $line) {
            $this->stream .= sprintf("BT /F1 9.5 Tf %s rg 0 Tc %.2F %.2F Td (%s) Tj ET\n", self::rgb([44, 44, 42]), self::MARGIN + 10, $yy, self::esc($line));
            $yy -= 13.3;
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
        foreach (self::FONTS as [$res, $base]) {
            $n = $add(sprintf('<< /Type /Font /Subtype /Type1 /BaseFont /%s /Encoding /WinAnsiEncoding >>', $base));
            $fontRefs[] = "/$res $n 0 R";
        }
        $resources = '<< /Font << ' . implode(' ', $fontRefs) . ' >> >>';

        $kids = [];
        foreach ($this->pages as $i => $content) {
            $footer = sprintf(
                "BT /F1 8 Tf %s rg 0 Tc %.2F %.2F Td (%s) Tj ET\n",
                self::rgb([136, 135, 128]),
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

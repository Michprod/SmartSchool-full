<?php

namespace App\Support;

/**
 * Générateur PDF minimal (Helvetica, A4) — structure xref explicite.
 */
class SimplePdfDocument
{
    private const PAGE_WIDTH = 595;

    private const PAGE_HEIGHT = 842;

    private const MARGIN = 45;

    /** @var list<string> */
    private array $pages = [];

    private string $pageContent = '';

    private float $cursorY = 0;

    public function __construct()
    {
        $this->startPage();
    }

    public function addTitle(string $text, int $size = 16): void
    {
        $this->addSpacer(8);
        $this->drawText($text, $size, true, 'center');
        $this->addSpacer(14);
    }

    public function addSubtitle(string $text, int $size = 12): void
    {
        $this->drawText($text, $size, false, 'center');
        $this->addSpacer(10);
    }

    public function addLine(string $text, int $size = 11, bool $bold = false): void
    {
        $this->ensureSpace($size + 6);
        $this->drawText($text, $size, $bold, 'left');
        $this->addSpacer(4);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     * @param  list<int|float>  $widths
     */
    public function addTable(array $headers, array $rows, array $widths): void
    {
        $rowHeight = 16;
        $this->ensureSpace($rowHeight + 4);

        $x = self::MARGIN;
        $y = $this->cursorY;

        foreach ($headers as $i => $header) {
            $this->pageContent .= $this->rectStream($x, $y - $rowHeight, (float) $widths[$i], $rowHeight);
            $this->pageContent .= $this->cellTextStream($header, $x + 4, $y - 12, 10, true);
            $x += (float) $widths[$i];
        }

        $this->cursorY -= $rowHeight;

        foreach ($rows as $row) {
            $this->ensureSpace($rowHeight + 2);
            $x = self::MARGIN;
            $y = $this->cursorY;

            foreach ($row as $i => $cell) {
                $this->pageContent .= $this->rectStream($x, $y - $rowHeight, (float) $widths[$i], $rowHeight, false);
                $this->pageContent .= $this->cellTextStream((string) $cell, $x + 4, $y - 12, 9, false);
                $x += (float) $widths[$i];
            }

            $this->cursorY -= $rowHeight;
        }

        $this->addSpacer(8);
    }

    public function addSpacer(float $points): void
    {
        $this->cursorY -= $points;
    }

    public function output(): string
    {
        $this->flushPage();

        if ($this->pages === []) {
            $this->startPage();
            $this->drawText('Document vide', 12, false, 'left');
            $this->flushPage();
        }

        $pageCount = count($this->pages);
        $fontRegularId = 3 + ($pageCount * 2);
        $fontBoldId = $fontRegularId + 1;
        $totalObjects = $fontBoldId;

        $objects = [];

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        $pageRefs = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $pageRefs[] = (3 + ($i * 2)).' 0 R';
        }
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageRefs)."] /Count {$pageCount} >>";

        for ($i = 0; $i < $pageCount; $i++) {
            $pageId = 3 + ($i * 2);
            $contentId = $pageId + 1;
            $content = $this->pages[$i];
            $length = strlen($content);

            $objects[$contentId] = "<< /Length {$length} >>\nstream\n{$content}\nendstream";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R '
                .'/MediaBox [0 0 '.self::PAGE_WIDTH.' '.self::PAGE_HEIGHT.'] '
                ."/Contents {$contentId} 0 R "
                ."/Resources << /Font << /F1 {$fontRegularId} 0 R /F2 {$fontBoldId} 0 R >> >> >>";
        }

        $objects[$fontRegularId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[$fontBoldId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        $pdf = "%PDF-1.4\n";
        $offsets = array_fill(0, $totalObjects + 1, 0);

        for ($id = 1; $id <= $totalObjects; $id++) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$objects[$id]}\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 ".($totalObjects + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id <= $totalObjects; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $pdf .= "trailer\n<< /Size ".($totalObjects + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefPos}\n%%EOF";

        return $pdf;
    }

    private function startPage(): void
    {
        $this->pageContent = '';
        $this->cursorY = self::PAGE_HEIGHT - self::MARGIN;
    }

    private function flushPage(): void
    {
        if ($this->pageContent !== '') {
            $this->pages[] = $this->pageContent;
        }
    }

    private function ensureSpace(float $needed): void
    {
        if ($this->cursorY - $needed < self::MARGIN) {
            $this->flushPage();
            $this->startPage();
        }
    }

    private function drawText(string $text, int $size, bool $bold, string $align): void
    {
        $font = $bold ? '/F2' : '/F1';
        $safe = $this->escape($this->toPdfText($text));
        $textWidth = strlen($safe) * ($size * 0.5);
        $x = self::MARGIN;

        if ($align === 'center') {
            $x = max(self::MARGIN, (self::PAGE_WIDTH - $textWidth) / 2);
        }

        $y = $this->cursorY;
        $this->pageContent .= "BT {$font} {$size} Tf 1 0 0 1 {$x} {$y} Tm ({$safe}) Tj ET\n";
        $this->cursorY -= $size + 2;
    }

    private function cellTextStream(string $text, float $x, float $y, int $size, bool $bold): string
    {
        $font = $bold ? '/F2' : '/F1';
        $safe = $this->escape($this->toPdfText($text));

        return "BT {$font} {$size} Tf 1 0 0 1 {$x} {$y} Tm ({$safe}) Tj ET\n";
    }

    private function rectStream(float $x, float $y, float $w, float $h, bool $header = true): string
    {
        $fill = $header ? '0.92 g' : '1 g';

        return sprintf("%.2F g %.2F %.2F %.2F %.2F re f 0 g\n", $header ? 0.92 : 1.0, $x, $y, $w, $h);
    }

    private function toPdfText(string $text): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        if ($converted === false) {
            $converted = $text;
        }

        return preg_replace('/[^\x20-\x7E]/', '', $converted) ?? '';
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}

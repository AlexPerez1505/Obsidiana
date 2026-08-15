<?php

namespace App\Support;

class SimplePdf
{
    private const WIDTH = 595;
    private const HEIGHT = 842;
    private const MARGIN = 42;

    private array $pages = [];
    private array $commands = [];
    private float $y = 800;

    public function __construct()
    {
        $this->addPage();
    }

    public function title(string $text): void
    {
        $this->text(self::MARGIN, $this->y, $text, 20, true);
        $this->y -= 26;
    }

    public function subtitle(string $text): void
    {
        $this->text(self::MARGIN, $this->y, $text, 11);
        $this->y -= 20;
        $this->line(self::MARGIN, $this->y, self::WIDTH - self::MARGIN, $this->y);
        $this->y -= 22;
    }

    public function section(string $text): void
    {
        $this->ensureSpace(34);
        $this->y -= 6;
        $this->text(self::MARGIN, $this->y, $text, 13, true);
        $this->y -= 18;
    }

    public function keyValue(string $key, string $value): void
    {
        $this->ensureSpace(18);
        $this->text(self::MARGIN, $this->y, $key . ':', 10, true);
        $this->text(self::MARGIN + 110, $this->y, $value, 10);
        $this->y -= 16;
    }

    public function table(array $headers, array $rows, array $widths): void
    {
        $this->ensureSpace(48);
        $x = self::MARGIN;

        foreach ($headers as $index => $header) {
            $this->text($x + 2, $this->y, (string) $header, 9, true);
            $x += $widths[$index];
        }

        $this->y -= 8;
        $this->line(self::MARGIN, $this->y, self::WIDTH - self::MARGIN, $this->y);
        $this->y -= 12;

        foreach ($rows as $row) {
            $wrapped = [];
            $maxLines = 1;

            foreach ($row as $index => $cell) {
                $lines = $this->wrap((string) $cell, max(8, (int) floor($widths[$index] / 5.2)));
                $wrapped[$index] = $lines;
                $maxLines = max($maxLines, count($lines));
            }

            $rowHeight = ($maxLines * 12) + 8;
            $this->ensureSpace($rowHeight + 6);

            $x = self::MARGIN;
            foreach ($wrapped as $index => $lines) {
                foreach ($lines as $lineIndex => $line) {
                    $this->text($x + 2, $this->y - ($lineIndex * 12), $line, 8.5);
                }
                $x += $widths[$index];
            }

            $this->y -= $rowHeight;
        }
    }

    public function output(): string
    {
        if ($this->commands !== []) {
            $this->pages[] = implode("\n", $this->commands);
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];

        $kids = [];
        $nextId = 5;

        foreach ($this->pages as $content) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $kids[] = "{$pageId} 0 R";
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . self::WIDTH . ' ' . self::HEIGHT . "] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentId} 0 R >>";
            $objects[$contentId] = "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function addPage(): void
    {
        if ($this->commands !== []) {
            $this->pages[] = implode("\n", $this->commands);
        }

        $this->commands = [];
        $this->y = 800;
    }

    private function ensureSpace(float $height): void
    {
        if ($this->y - $height < self::MARGIN) {
            $this->addPage();
        }
    }

    private function text(float $x, float $y, string $text, float $size = 10, bool $bold = false): void
    {
        $font = $bold ? 'F2' : 'F1';
        $this->commands[] = sprintf(
            'BT /%s %.2F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET',
            $font,
            $size,
            $x,
            $y,
            $this->escape($text)
        );
    }

    private function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->commands[] = sprintf('0.75 G %.2F %.2F m %.2F %.2F l S 0 G', $x1, $y1, $x2, $y2);
    }

    private function wrap(string $text, int $limit): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = trim($line . ' ' . $word);
            if (mb_strlen($candidate) > $limit && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines === [] ? [''] : $lines;
    }

    private function escape(string $text): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);

        return str_replace(
            ["\\", '(', ')', "\r", "\n"],
            ["\\\\", "\\(", "\\)", ' ', ' '],
            $encoded === false ? $text : $encoded
        );
    }
}

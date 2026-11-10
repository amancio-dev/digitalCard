<?php

namespace App\Services\Payment;

class PixQrCodeSvgGenerator
{
    /**
     * Gera um SVG de QR Code determinístico e de alto contraste no servidor a partir do payload BR Code PIX (B5).
     */
    public static function renderSvg(string $brCodePayload, int $size = 180): string
    {
        $gridSize = 25;
        $matrix = array_fill(0, $gridSize, array_fill(0, $gridSize, false));

        // Padrões localizadores (Finder Patterns 7x7 nos 3 cantos)
        self::placeFinderPattern($matrix, 0, 0);
        self::placeFinderPattern($matrix, 0, $gridSize - 7);
        self::placeFinderPattern($matrix, $gridSize - 7, 0);

        // Padrão de alinhamento 5x5 em (16, 16)
        for ($r = 16; $r <= 20; $r++) {
            for ($c = 16; $c <= 20; $c++) {
                $isBorder = $r === 16 || $r === 20 || $c === 16 || $c === 20;
                $isCenter = $r === 18 && $c === 18;
                $matrix[$r][$c] = $isBorder || $isCenter;
            }
        }

        // Timing patterns na linha 6 e coluna 6
        for ($i = 8; $i < $gridSize - 8; $i++) {
            $matrix[6][$i] = ($i % 2 === 0);
            $matrix[$i][6] = ($i % 2 === 0);
        }

        // Preenche os módulos de dados de forma determinística a partir dos bytes do BR Code (CRC16 incluído)
        $hashStream = '';
        for ($block = 0; $block < 4; $block++) {
            $hashStream .= hash('sha256', $block.':'.$brCodePayload, true);
        }

        $bitIndex = 0;
        $totalBits = strlen($hashStream) * 8;

        for ($r = 0; $r < $gridSize; $r++) {
            for ($c = 0; $c < $gridSize; $c++) {
                if (self::isReservedModule($r, $c, $gridSize)) {
                    continue;
                }

                $bytePos = intdiv($bitIndex % $totalBits, 8);
                $bitOffset = ($bitIndex % $totalBits) % 8;
                $bit = (ord($hashStream[$bytePos]) >> (7 - $bitOffset)) & 1;
                $matrix[$r][$c] = ($bit === 1);
                $bitIndex++;
            }
        }

        $quietZone = 2;
        $totalModules = $gridSize + ($quietZone * 2);
        $rects = [];

        for ($r = 0; $r < $gridSize; $r++) {
            for ($c = 0; $c < $gridSize; $c++) {
                if ($matrix[$r][$c]) {
                    $x = $c + $quietZone;
                    $y = $r + $quietZone;
                    $rects[] = "<rect x=\"{$x}\" y=\"{$y}\" width=\"1\" height=\"1\"/>";
                }
            }
        }

        $rectsHtml = implode('', $rects);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$totalModules} {$totalModules}" width="{$size}" height="{$size}" shape-rendering="crispEdges" role="img" aria-label="QR Code PIX Simulado">
  <rect width="{$totalModules}" height="{$totalModules}" fill="#ffffff"/>
  <g fill="#0B1120">{$rectsHtml}</g>
</svg>
SVG;
    }

    /**
     * @param  array<int, array<int, bool>>  $matrix
     */
    private static function placeFinderPattern(array &$matrix, int $startRow, int $startCol): void
    {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                $isOuterBorder = $r === 0 || $r === 6 || $c === 0 || $c === 6;
                $isInnerCore = $r >= 2 && $r <= 4 && $c >= 2 && $c <= 4;
                $matrix[$startRow + $r][$startCol + $c] = $isOuterBorder || $isInnerCore;
            }
        }
    }

    private static function isReservedModule(int $r, int $c, int $gridSize): bool
    {
        if ($r < 8 && $c < 8) {
            return true;
        }
        if ($r < 8 && $c >= $gridSize - 8) {
            return true;
        }
        if ($r >= $gridSize - 8 && $c < 8) {
            return true;
        }
        if ($r === 6 || $c === 6) {
            return true;
        }
        if ($r >= 16 && $r <= 20 && $c >= 16 && $c <= 20) {
            return true;
        }

        return false;
    }
}

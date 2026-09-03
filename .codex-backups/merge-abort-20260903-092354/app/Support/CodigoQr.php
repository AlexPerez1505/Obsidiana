<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Genera el QR que se imprime en el PDF.
 *
 * Sale en SVG y se incrusta como data URI: dompdf lo dibuja sin necesitar
 * imagick ni escribir archivos temporales en disco.
 */
class CodigoQr
{
    public static function svg(string $texto, int $tamano = 220): string
    {
        $writer = new Writer(
            new ImageRenderer(new RendererStyle($tamano, 1), new SvgImageBackEnd())
        );

        return $writer->writeString($texto);
    }

    /** Listo para usar en <img src="..."> dentro del PDF. */
    public static function dataUri(string $texto, int $tamano = 220): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode(self::svg($texto, $tamano));
    }
}

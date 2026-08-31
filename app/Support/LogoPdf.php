<?php

namespace App\Support;

/**
 * Logo listo para incrustar en un PDF.
 *
 * El archivo original mide 3544px de ancho y pesa ~400 KB; dompdf lo mete
 * tal cual en cada documento. Aquí se reduce una sola vez a la medida en
 * que realmente se imprime y se deja en caché.
 */
class LogoPdf
{
    private const ORIGEN = 'images/logomedy.png';
    private const CACHE = 'app/logo-pdf.png';
    private const ANCHO = 360; // suficiente para 42px de alto impreso

    /** Devuelve el data URI, o null si no hay logo. */
    public static function dataUri(): ?string
    {
        $origen = public_path(self::ORIGEN);

        if (! is_file($origen)) {
            return null;
        }

        $cache = storage_path(self::CACHE);

        // Se rehace solo si el original cambió.
        if (! is_file($cache) || filemtime($cache) < filemtime($origen)) {
            if (! self::reducir($origen, $cache)) {
                // Sin GD disponible se usa el original: pesa más, pero sale.
                return 'data:image/png;base64,' . base64_encode(file_get_contents($origen));
            }
        }

        return 'data:image/png;base64,' . base64_encode(file_get_contents($cache));
    }

    private static function reducir(string $origen, string $destino): bool
    {
        if (! function_exists('imagecreatefrompng')) {
            return false;
        }

        $img = @imagecreatefrompng($origen);

        if (! $img) {
            return false;
        }

        $ancho = imagesx($img);
        $alto = imagesy($img);
        $nuevoAlto = (int) round($alto * (self::ANCHO / $ancho));

        $chico = imagecreatetruecolor(self::ANCHO, $nuevoAlto);

        // Se conserva la transparencia del PNG.
        imagealphablending($chico, false);
        imagesavealpha($chico, true);
        imagefill($chico, 0, 0, imagecolorallocatealpha($chico, 0, 0, 0, 127));

        imagecopyresampled($chico, $img, 0, 0, 0, 0, self::ANCHO, $nuevoAlto, $ancho, $alto);

        @mkdir(dirname($destino), 0775, true);
        $ok = imagepng($chico, $destino, 9);

        imagedestroy($img);
        imagedestroy($chico);

        return $ok;
    }
}

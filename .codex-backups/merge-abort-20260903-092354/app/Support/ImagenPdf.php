<?php

namespace App\Support;

/**
 * Foto del equipo lista para incrustar en un PDF.
 *
 * Las imágenes se guardan como URL completa (http://.../storage/equipos/x.png).
 * dompdf tendría que descargarlas del mismo servidor que está atendiendo la
 * petición, lo que se traba y termina dibujando el recuadro tachado. Aquí la
 * URL se traduce a la ruta en disco, se reduce al tamaño en que realmente se
 * imprime y se deja en caché.
 */
class ImagenPdf
{
    private const CACHE = 'app/pdf-img';
    private const ANCHO = 200; // suficiente para 34px impresos

    /** Data URI de la imagen, o null si no hay o no se pudo leer. */
    public static function dataUri(?string $valor, int $ancho = self::ANCHO): ?string
    {
        $origen = self::rutaLocal($valor);

        if (! $origen) {
            return null;
        }

        $tipo = @getimagesize($origen);

        if (! $tipo) {
            return null;
        }

        $cache = storage_path(self::CACHE . '/' . sha1($origen) . "-{$ancho}.png");

        if (! is_file($cache) || filemtime($cache) < filemtime($origen)) {
            if (! self::reducir($origen, $cache, $ancho)) {
                // Sin GD se manda el original: pesa más, pero la foto sale.
                return 'data:' . $tipo['mime'] . ';base64,' . base64_encode(file_get_contents($origen));
            }
        }

        return 'data:image/png;base64,' . base64_encode(file_get_contents($cache));
    }

    /**
     * Traduce lo que esté guardado a una ruta de disco.
     *
     * Acepta la URL completa, la ruta relativa dentro de storage y la ruta
     * absoluta. Una imagen alojada en otro dominio devuelve null: no se
     * descargan archivos ajenos al armar el documento.
     */
    private static function rutaLocal(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        if (is_file($valor)) {
            return $valor;
        }

        $ruta = $valor;

        if (str_starts_with($valor, 'http://') || str_starts_with($valor, 'https://')) {
            $host = parse_url($valor, PHP_URL_HOST);
            $nuestro = parse_url(config('app.url'), PHP_URL_HOST);

            // Solo se resuelven las imágenes del propio sistema.
            if ($host && $nuestro && $host !== $nuestro && ! in_array($host, ['127.0.0.1', 'localhost'], true)) {
                return null;
            }

            $ruta = parse_url($valor, PHP_URL_PATH) ?: '';
        }

        $ruta = ltrim(rawurldecode($ruta), '/');

        if ($ruta === '' || str_contains($ruta, '..')) {
            return null;
        }

        $candidatos = [
            storage_path('app/public/' . preg_replace('#^storage/#', '', $ruta)),
            public_path($ruta),
        ];

        foreach ($candidatos as $c) {
            if (is_file($c)) {
                return $c;
            }
        }

        return null;
    }

    private static function reducir(string $origen, string $destino, int $ancho): bool
    {
        if (! function_exists('imagecreatetruecolor')) {
            return false;
        }

        $img = @imagecreatefromstring(file_get_contents($origen));

        if (! $img) {
            return false;
        }

        $w = imagesx($img);
        $h = imagesy($img);

        // Una imagen ya pequeña no se agranda.
        $nuevoAncho = min($ancho, $w);
        $nuevoAlto = (int) max(1, round($h * ($nuevoAncho / $w)));

        $chico = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

        imagealphablending($chico, false);
        imagesavealpha($chico, true);
        imagefill($chico, 0, 0, imagecolorallocatealpha($chico, 255, 255, 255, 127));

        imagecopyresampled($chico, $img, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $w, $h);

        @mkdir(dirname($destino), 0775, true);
        $ok = imagepng($chico, $destino, 9);

        imagedestroy($img);
        imagedestroy($chico);

        return $ok;
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Throwable;

/**
 * Arma un solo PDF con el documento, sus anexos y las fichas tecnicas.
 *
 * Antes el documento solo imprimia el titulo y las notas de la ficha; el PDF
 * que el usuario habia subido nunca se anexaba.
 */
class FusionadorPdf
{
    /**
     * @param  string  $principal  Bytes del PDF de la cotizacion o venta.
     * @param  iterable  $fichas  Fichas con archivo a anexar, al final.
     * @param  array<int, array{titulo: string, contenido: string}>  $adicionales
     *         Documentos ya generados (contrato, carta garantia) que van
     *         inmediatamente despues del principal, antes de las fichas.
     * @return array{contenido: string, anexadas: array<int, string>, omitidas: array<int, string>}
     */
    public static function unir(string $principal, iterable $fichas, array $adicionales = []): array
    {
        $anexadas = [];
        $omitidas = [];

        $pdf = new Fpdi();

        // El documento base siempre entra; si fallara, no hay nada que entregar.
        self::copiarPaginas($pdf, StreamReader::createByString($principal));

        foreach ($adicionales as $doc) {
            if (empty($doc['contenido'])) {
                continue;
            }

            try {
                self::copiarPaginas($pdf, StreamReader::createByString($doc['contenido']));
                $anexadas[] = $doc['titulo'];
            } catch (Throwable $e) {
                $omitidas[] = $doc['titulo'];

                Log::warning('No se pudo anexar el documento al PDF', [
                    'documento' => $doc['titulo'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ($fichas as $ficha) {
            if (! $ficha->archivo || ! Storage::disk('public')->exists($ficha->archivo)) {
                continue;
            }

            $ruta = Storage::disk('public')->path($ficha->archivo);

            try {
                self::copiarPaginas($pdf, $ruta);
                $anexadas[] = $ficha->titulo;
            } catch (Throwable $e) {
                // Un PDF ilegible no debe tumbar la descarga completa: se
                // omite ese anexo y el documento se entrega igual.
                $omitidas[] = $ficha->titulo;

                Log::warning('No se pudo anexar la ficha técnica al PDF', [
                    'ficha' => $ficha->titulo,
                    'archivo' => $ficha->archivo,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'contenido' => $pdf->Output('S'),
            'anexadas' => $anexadas,
            'omitidas' => $omitidas,
        ];
    }

    /** Copia todas las paginas de un origen, respetando su tamaño y orientacion. */
    private static function copiarPaginas(Fpdi $pdf, $origen): void
    {
        $paginas = $pdf->setSourceFile($origen);

        for ($i = 1; $i <= $paginas; $i++) {
            $plantilla = $pdf->importPage($i);
            $medidas = $pdf->getTemplateSize($plantilla);

            $pdf->AddPage($medidas['orientation'], [$medidas['width'], $medidas['height']]);
            $pdf->useTemplate($plantilla);
        }
    }
}

<?php

namespace App\Services\Cotizaciones;

use App\Models\Cotizacion;
use App\Support\FusionadorPdf;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * El PDF de una cotización tal como se descarga en la web: la hoja de la
 * cotización y, al final, las fichas técnicas que lleva adjuntas.
 *
 * La API entrega el mismo archivo; por eso se arma en un solo lugar.
 */
class PdfCotizacion
{
    /** Bytes del PDF listo para mandar. */
    public function generar(Cotizacion $cotizacion): string
    {
        $cotizacion->load(['customer', 'seller', 'items', 'pagos', 'fichas']);

        $pdf = Pdf::loadView('structure.commercial_management.cotizaciones.pdf', [
            'cotizacion' => $cotizacion,
        ])->setPaper('letter');

        // Las fichas técnicas se pegan al final, en el mismo archivo.
        return FusionadorPdf::unir($pdf->output(), $cotizacion->fichas)['contenido'];
    }

    public function nombreArchivo(Cotizacion $cotizacion): string
    {
        return "{$cotizacion->folio}.pdf";
    }
}

<?php

namespace App\Services\Ventas;

use App\Models\Venta;
use App\Support\AnexosVenta;
use App\Support\FusionadorPdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Los PDF de una venta: la venta completa (con contrato, carta garantía y
 * fichas anexas), el contrato y la carta garantía sueltos.
 *
 * Salía directo de VentaController; vive aquí para que el asesor reciba el
 * mismo archivo desde la web o desde la app. Quién puede pedir cada uno y
 * cuándo aplica (contrato solo a plazos, carta solo con garantía) lo revisa
 * cada controlador antes de llamar.
 */
class DocumentosDeVenta
{
    public function venta(Venta $venta): Response
    {
        $venta->load(['customer', 'seller', 'items', 'pagos', 'fichas']);

        $pdf = Pdf::loadView('structure.commercial_management.ventas.pdf', [
            'venta' => $venta,
        ])->setPaper('letter');

        // Todo en un solo archivo: la venta, el contrato y la carta garantía,
        // y hasta el final las fichas técnicas del equipo.
        $unido = FusionadorPdf::unir($pdf->output(), $venta->fichas, AnexosVenta::para($venta));

        return response($unido['contenido'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$venta->folio}.pdf\"",
        ]);
    }

    /** Contrato de compraventa a plazos. */
    public function contrato(Venta $venta): Response
    {
        $venta->load(['customer', 'seller', 'items', 'pagos']);

        return $this->entregar('structure.commercial_management.ventas.contrato', ['venta' => $venta], "Contrato-{$venta->folio}");
    }

    /** Carta garantía del equipo. */
    public function garantia(Venta $venta): Response
    {
        $venta->load(['customer', 'seller', 'items']);

        return $this->entregar('structure.commercial_management.ventas.garantia', ['venta' => $venta], "Garantia-{$venta->folio}");
    }

    private function entregar(string $vista, array $datos, string $nombre): Response
    {
        $pdf = Pdf::loadView($vista, $datos)->setPaper('letter');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$nombre}.pdf\"",
        ]);
    }
}

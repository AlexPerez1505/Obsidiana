<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Cotizacion;
use App\Models\Venta;
use App\Support\AnexosVenta;
use App\Support\FusionadorPdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;

/**
 * Consulta publica: lo que ve el cliente al escanear el QR del PDF.
 *
 * No pide sesion. Se entra por un token largo, no por el id, y solo se
 * muestra el documento; no hay forma de llegar de aqui al panel interno.
 */
class ConsultaPublicaController extends Controller
{
    public function cotizacion(string $token): View
    {
        $doc = Cotizacion::where('public_token', $token)
            ->with(['customer', 'seller', 'items', 'pagos', 'fichas', 'congreso'])
            ->firstOrFail();

        return view('publico.documento', [
            'doc' => $doc,
            'tipo' => 'Cotización',
            'rutaPdf' => route('publico.cotizacion.pdf', $token),
        ]);
    }

    public function venta(string $token): View
    {
        $doc = Venta::where('public_token', $token)
            ->with(['customer', 'seller', 'items', 'pagos.cobros', 'cobros', 'fichas', 'congreso'])
            ->firstOrFail();

        return view('publico.documento', [
            'doc' => $doc,
            'tipo' => 'Venta',
            'rutaPdf' => route('publico.venta.pdf', $token),
            // Solo la venta lleva estado de cobranza; una cotización todavía no se cobra.
            'esVenta' => true,
        ]);
    }

    /**
     * Recibo de un pago, para que el cliente se lo descargue solo.
     *
     * El cobro se busca dentro de la venta del token: así nadie puede pedir
     * el recibo de otro cliente cambiando el número en la dirección.
     */
    public function recibo(string $token, int $cobro)
    {
        $venta = Venta::where('public_token', $token)->firstOrFail();

        $registro = Cobro::where('venta_id', $venta->id)->findOrFail($cobro);

        return CobroController::pdfRecibo($registro);
    }

    /** El mismo PDF que se le entregó, con sus fichas anexas. */
    public function cotizacionPdf(string $token)
    {
        $doc = Cotizacion::where('public_token', $token)
            ->with(['customer', 'seller', 'items', 'pagos', 'fichas'])
            ->firstOrFail();

        return $this->entregar('structure.commercial_management.cotizaciones.pdf', ['cotizacion' => $doc], $doc);
    }

    public function ventaPdf(string $token)
    {
        $doc = Venta::where('public_token', $token)
            ->with(['customer', 'seller', 'items', 'pagos', 'fichas'])
            ->firstOrFail();

        // Al cliente le llega el mismo paquete que descarga el asesor.
        return $this->entregar(
            'structure.commercial_management.ventas.pdf',
            ['venta' => $doc],
            $doc,
            AnexosVenta::para($doc)
        );
    }

    private function entregar(string $vista, array $datos, $doc, array $adicionales = [])
    {
        $pdf = Pdf::loadView($vista, $datos)->setPaper('letter');

        $unido = FusionadorPdf::unir($pdf->output(), $doc->fichas, $adicionales);

        return response($unido['contenido'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$doc->folio}.pdf\"",
        ]);
    }
}

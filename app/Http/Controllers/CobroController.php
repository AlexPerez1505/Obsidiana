<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Venta;
use App\Models\VentaPago;
use App\Services\CalendarioPagos;
use App\Services\Ventas\CobrosDeVenta;
use App\Services\Ventas\OperacionRechazada;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Cobros de una venta: registrar lo que entró, con su evidencia, y emitir
 * el recibo correspondiente. Las reglas viven en CobrosDeVenta (las usa
 * también la API).
 */
class CobroController extends Controller
{
    public function __construct(
        private readonly CalendarioPagos $calendario,
        private readonly CobrosDeVenta $cobros,
    ) {
    }

    /** Pantalla de cobranza de una venta. */
    public function index(Venta $venta): View
    {
        $venta->load(['customer', 'seller', 'pagos.cobros', 'cobros.evidencias', 'cobros.parcialidad', 'cobros.aplicaciones.pago', 'bitacora.user']);

        return view('structure.commercial_management.cobros.index', [
            'venta' => $venta,
            'metodos' => Cobro::METODOS,
            'excedentePendiente' => $this->calendario->excedentePendiente($venta),
        ]);
    }

    public function store(Request $request, Venta $venta): RedirectResponse
    {
        try {
            $this->cobros->asegurarCobrable($venta);
        } catch (OperacionRechazada $e) {
            return back()->withErrors([$e->campo => $e->getMessage()]);
        }

        $data = $request->validate(CobrosDeVenta::reglasCobro($venta), CobrosDeVenta::MENSAJES_COBRO);

        try {
            $r = $this->cobros->registrar($venta, $data, (array) $request->file('evidencias', []));
        } catch (OperacionRechazada $e) {
            return back()->withInput()->withErrors([$e->campo => $e->getMessage()]);
        }

        return redirect()->route('commercial.ventas.cobros.index', $venta)
            ->with('status', $r['mensaje']);
    }

    /**
     * Cancelar un cobro. No se edita: se cancela y se vuelve a capturar,
     * para que el recibo entregado siempre corresponda con el sistema.
     */
    public function destroy(Venta $venta, Cobro $cobro): RedirectResponse
    {
        abort_if($cobro->venta_id !== $venta->id, 404);

        return back()->with('status', $this->cobros->cancelarCobro($venta, $cobro));
    }

    /** Recibo en PDF de un cobro. */
    public function recibo(Venta $venta, Cobro $cobro)
    {
        abort_if($cobro->venta_id !== $venta->id, 404);

        return $this->pdfRecibo($cobro);
    }

    /** Recorre el calendario para que empiece en otra fecha. */
    public function recorrer(Request $request, Venta $venta): RedirectResponse
    {
        $data = $request->validate([
            'nueva_fecha' => ['required', 'date'],
        ]);

        return back()->with('status', $this->cobros->recorrer($venta, Carbon::parse($data['nueva_fecha'])));
    }

    /** Cambia la fecha o el monto de una parcialidad concreta. */
    public function actualizarParcialidad(Request $request, Venta $venta, VentaPago $pago): RedirectResponse
    {
        abort_if($pago->venta_id !== $venta->id, 404);

        $data = $request->validate(CobrosDeVenta::REGLAS_PARCIALIDAD);

        try {
            return back()->with('status', $this->cobros->actualizarParcialidad($venta, $pago, $data));
        } catch (OperacionRechazada $e) {
            return back()->withErrors([$e->campo => $e->getMessage()]);
        }
    }

    /** Reparte la diferencia entre las parcialidades sin cobrar. */
    public function rebalancear(Venta $venta): RedirectResponse
    {
        try {
            return back()->with('status', $this->cobros->rebalancear($venta));
        } catch (OperacionRechazada $e) {
            return back()->withErrors([$e->campo => $e->getMessage()]);
        }
    }

    /** Reasigna a las parcialidades pendientes el excedente de abonos sueltos o que se pasaron de lo que les tocaba. */
    public function absorberExcedente(Venta $venta): RedirectResponse
    {
        return back()->with('status', $this->cobros->absorberExcedente($venta));
    }

    /** Agrega una parcialidad al final del plan. */
    public function agregarParcialidad(Request $request, Venta $venta): RedirectResponse
    {
        $data = $request->validate(CobrosDeVenta::REGLAS_PARCIALIDAD_NUEVA);

        $this->cobros->agregarParcialidad($venta, $data);

        return back()->with('status', 'Parcialidad agregada.');
    }

    /** Elimina una parcialidad, solo si no tiene dinero encima. */
    public function eliminarParcialidad(Venta $venta, VentaPago $pago): RedirectResponse
    {
        abort_if($pago->venta_id !== $venta->id, 404);

        try {
            return back()->with('status', $this->cobros->eliminarParcialidad($venta, $pago));
        } catch (OperacionRechazada $e) {
            return back()->withErrors([$e->campo => $e->getMessage()]);
        }
    }

    // ===================== Recibo compartido =====================

    /** Lo usa el panel, la consulta pública del cliente y la API. */
    public static function pdfRecibo(Cobro $cobro)
    {
        // items: el recibo dice qué se vendió, no solo cuánto se pagó.
        $cobro->load(['venta.customer', 'venta.seller', 'venta.items', 'venta.pagos', 'parcialidad', 'aplicaciones.pago', 'registradoPor']);

        $pdf = Pdf::loadView('structure.commercial_management.cobros.recibo', [
            'cobro' => $cobro,
        ])->setPaper('letter');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$cobro->folio}.pdf\"",
        ]);
    }
}

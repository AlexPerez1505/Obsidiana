<?php

namespace App\Http\Controllers;

use App\Models\CobroSolicitud;
use App\Models\Venta;
use App\Services\Ventas\ComprobantesDePago;
use App\Services\Ventas\OperacionRechazada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Pagos por aprobar: el vendedor los sube; un admin o alguien con NIP los aprueba. */
class ComprobantePagoController extends Controller
{
    public function __construct(private readonly ComprobantesDePago $comprobantes)
    {
    }

    public function store(Request $request, Venta $venta): RedirectResponse
    {
        abort_unless($venta->visiblePara($request->user()), 403);

        $data = $request->validateWithBag('comprobante', ComprobantesDePago::reglas($venta), ComprobantesDePago::MENSAJES);

        try {
            $this->comprobantes->subir($venta, $data, (array) $request->file('archivos', []), $request->user());
        } catch (OperacionRechazada $e) {
            return back()->withInput()->withErrors([$e->campo => $e->getMessage()], 'comprobante');
        }

        return back()->with('status', 'Pago enviado: queda por aprobar hasta que alguien con NIP confirme que se vio reflejado.');
    }

    public function aprobar(Request $request, CobroSolicitud $solicitud): RedirectResponse
    {
        $msg = $this->comprobantes->aprobar($solicitud, $request->user(), $request->input('nip'));

        return back()->with('status', $msg);
    }

    /** "Ya lo registré": liga el pago subido a un cobro que ya existe. */
    public function ligar(Request $request, CobroSolicitud $solicitud): RedirectResponse
    {
        $data = $request->validate(['cobro_id' => ['required', 'integer']]);
        $cobro = \App\Models\Cobro::findOrFail($data['cobro_id']);

        return back()->with('status', $this->comprobantes->ligar($solicitud, $cobro, $request->user()));
    }

    public function rechazar(Request $request, CobroSolicitud $solicitud): RedirectResponse
    {
        $data = $request->validate(['motivo' => ['required', 'string', 'max:500']], ['motivo.required' => 'Escribe por qué se rechaza.']);

        return back()->with('status', $this->comprobantes->rechazar($solicitud, $request->user(), $data['motivo']));
    }

    public function destroy(Request $request, CobroSolicitud $solicitud): RedirectResponse
    {
        return back()->with('status', $this->comprobantes->retirar($solicitud, $request->user()));
    }
}

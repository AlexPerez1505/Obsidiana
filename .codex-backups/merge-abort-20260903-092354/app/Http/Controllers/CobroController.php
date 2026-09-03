<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Venta;
use App\Models\VentaBitacora;
use App\Models\VentaPago;
use App\Services\CalendarioPagos;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Cobros de una venta: registrar lo que entró, con su evidencia, y emitir
 * el recibo correspondiente.
 */
class CobroController extends Controller
{
    private const CARPETA = 'cobros';

    public function __construct(private readonly CalendarioPagos $calendario)
    {
    }

    /** Pantalla de cobranza de una venta. */
    public function index(Venta $venta): View
    {
        $venta->load(['customer', 'seller', 'pagos.cobros', 'cobros.evidencias', 'cobros.parcialidad', 'bitacora.user']);

        return view('structure.commercial_management.cobros.index', [
            'venta' => $venta,
            'metodos' => Cobro::METODOS,
            'excedentePendiente' => $this->calendario->excedentePendiente($venta),
        ]);
    }

    public function store(Request $request, Venta $venta): RedirectResponse
    {
        $data = $request->validate([
            'venta_pago_id' => ['nullable', Rule::exists('venta_pagos', 'id')->where('venta_id', $venta->id)],
            'fecha' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'metodo' => ['required', Rule::in(array_keys(Cobro::METODOS))],
            'referencia' => ['nullable', 'string', 'max:255'],
            'nota' => ['nullable', 'string', 'max:255'],
            'evidencias' => ['nullable', 'array', 'max:5'],
            'evidencias.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], [
            'monto.min' => 'El monto debe ser mayor a cero.',
            'evidencias.*.mimes' => 'La evidencia debe ser una imagen o un PDF.',
        ]);

        // No se acepta cobrar de más: sería un descuadre imposible de explicar.
        if ($data['monto'] > $venta->saldo() + 0.009) {
            return back()->withInput()->withErrors([
                'monto' => 'El monto supera el saldo pendiente ($' . number_format($venta->saldo(), 2) . ').',
            ]);
        }

        $excedente = DB::transaction(function () use ($request, $venta, $data) {
            $cobro = Cobro::create([
                'venta_id' => $venta->id,
                'venta_pago_id' => $data['venta_pago_id'] ?? null,
                'folio' => Cobro::siguienteFolio(),
                'fecha' => $data['fecha'],
                'monto' => $data['monto'],
                'metodo' => $data['metodo'],
                'referencia' => $data['referencia'] ?? null,
                'nota' => $data['nota'] ?? null,
                'registrado_por' => auth()->id(),
            ]);

            foreach ((array) $request->file('evidencias', []) as $archivo) {
                $cobro->evidencias()->create([
                    'archivo' => $archivo->store(self::CARPETA, 'public'),
                    'nombre' => $archivo->getClientOriginalName(),
                ]);
            }

            VentaBitacora::registrar(
                $venta,
                'cobro_registrado',
                "Cobro {$cobro->folio} por \$" . number_format((float) $cobro->monto, 2) . ' (' . $cobro->metodoLabel() . ')',
                ['cobro_id' => $cobro->id, 'monto' => (float) $cobro->monto]
            );

            // Si este cobro fue un abono suelto o se pasó de lo que le tocaba
            // a su parcialidad, ese excedente se reparte solo hacia lo que
            // sigue pendiente: no hace falta un paso aparte para "cuadrar".
            $venta->load(['pagos.cobros', 'cobros']);

            return $this->calendario->absorberExcedente($venta);
        });

        $aviso = $excedente['excedente'] > 0
            ? ' Se aplicaron $' . number_format($excedente['excedente'], 2) . ' a lo que sigue pendiente.'
            : '';

        return redirect()->route('commercial.ventas.cobros.index', $venta)
            ->with('status', 'Cobro registrado correctamente.' . $aviso);
    }

    /**
     * Cancelar un cobro. No se edita: se cancela y se vuelve a capturar,
     * para que el recibo entregado siempre corresponda con el sistema.
     */
    public function destroy(Venta $venta, Cobro $cobro): RedirectResponse
    {
        abort_if($cobro->venta_id !== $venta->id, 404);

        DB::transaction(function () use ($venta, $cobro) {
            foreach ($cobro->evidencias as $ev) {
                if (Storage::disk('public')->exists($ev->archivo)) {
                    Storage::disk('public')->delete($ev->archivo);
                }
            }

            VentaBitacora::registrar(
                $venta,
                'cobro_cancelado',
                "Se canceló el cobro {$cobro->folio} por \$" . number_format((float) $cobro->monto, 2),
                ['folio' => $cobro->folio, 'monto' => (float) $cobro->monto]
            );

            $cobro->delete();
        });

        return back()->with('status', 'Cobro cancelado.');
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

        $movidas = $this->calendario->recorrer($venta, Carbon::parse($data['nueva_fecha']));

        return back()->with('status', $movidas > 0
            ? "Se recorrieron {$movidas} fecha(s). Las parcialidades ya cobradas no se movieron."
            : 'No había fechas por mover.');
    }

    /** Cambia la fecha o el monto de una parcialidad concreta. */
    public function actualizarParcialidad(Request $request, Venta $venta, VentaPago $pago): RedirectResponse
    {
        abort_if($pago->venta_id !== $venta->id, 404);

        $data = $request->validate([
            'fecha' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0'],
        ]);

        // Lo ya cobrado marca el piso: por debajo, el recibo dejaría de cuadrar.
        $cobrado = $pago->cobrado();

        if ($data['monto'] < $cobrado - 0.009) {
            return back()->withErrors([
                'monto' => 'No puede quedar por debajo de lo ya cobrado ($' . number_format($cobrado, 2) . ').',
            ]);
        }

        $antes = ['fecha' => $pago->fecha?->format('Y-m-d'), 'monto' => (float) $pago->monto];

        $pago->update($data);

        VentaBitacora::registrar(
            $venta,
            'parcialidad_editada',
            "Se ajustó «{$pago->nombre}»",
            ['antes' => $antes, 'despues' => ['fecha' => $data['fecha'], 'monto' => (float) $data['monto']]]
        );

        return back()->with('status', 'Parcialidad actualizada.');
    }

    /** Reparte la diferencia entre las parcialidades sin cobrar. */
    public function rebalancear(Venta $venta): RedirectResponse
    {
        $r = $this->calendario->rebalancear($venta);

        if (! empty($r['sin_donde'])) {
            return back()->withErrors([
                'plan' => 'Todas las parcialidades tienen cobros; no hay dónde repartir la diferencia. Agrega una parcialidad nueva.',
            ]);
        }

        return back()->with('status', $r['ajustadas'] > 0
            ? 'Se repartió la diferencia entre ' . $r['ajustadas'] . ' parcialidad(es).'
            : 'El plan ya cuadra con el total.');
    }

    /** Reasigna a las parcialidades pendientes el excedente de abonos sueltos o que se pasaron de lo que les tocaba. */
    public function absorberExcedente(Venta $venta): RedirectResponse
    {
        $r = $this->calendario->absorberExcedente($venta);

        if ($r['excedente'] < 0.01) {
            return back()->with('status', 'No hay excedente por repartir.');
        }

        return back()->with('status',
            'Se aplicaron $'.number_format($r['excedente'], 2).' a '.$r['ajustadas'].' parcialidad(es) pendiente(s).'
        );
    }

    /** Agrega una parcialidad al final del plan. */
    public function agregarParcialidad(Request $request, Venta $venta): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'fecha' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0.01'],
        ]);

        $venta->pagos()->create($data + ['orden' => (int) $venta->pagos()->max('orden') + 1]);

        VentaBitacora::registrar($venta, 'parcialidad_agregada', "Se agregó «{$data['nombre']}»", $data);

        return back()->with('status', 'Parcialidad agregada.');
    }

    /** Elimina una parcialidad, solo si no tiene dinero encima. */
    public function eliminarParcialidad(Venta $venta, VentaPago $pago): RedirectResponse
    {
        abort_if($pago->venta_id !== $venta->id, 404);

        if ($pago->tieneCobros()) {
            return back()->withErrors(['plan' => 'Esa parcialidad ya tiene cobros; cancela primero sus cobros.']);
        }

        $nombre = $pago->nombre;
        $pago->delete();

        VentaBitacora::registrar($venta, 'parcialidad_eliminada', "Se eliminó «{$nombre}»");

        return back()->with('status', 'Parcialidad eliminada.');
    }

    // ===================== Recibo compartido =====================

    /** Lo usa tanto el panel como la consulta pública del cliente. */
    public static function pdfRecibo(Cobro $cobro)
    {
        $cobro->load(['venta.customer', 'venta.seller', 'parcialidad', 'registradoPor']);

        $pdf = Pdf::loadView('structure.commercial_management.cobros.recibo', [
            'cobro' => $cobro,
        ])->setPaper('letter');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$cobro->folio}.pdf\"",
        ]);
    }
}

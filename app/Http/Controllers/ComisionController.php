<?php

namespace App\Http\Controllers;

use App\Models\ComisionPago;
use App\Models\User;
use App\Services\Ventas\Comisiones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Control de comisiones: quién vendió más, cuánto le toca y cuánto ya se
 * le pagó. Los cálculos viven en Comisiones (los usa también la API).
 */
class ComisionController extends Controller
{
    public const BASES = Comisiones::BASES;

    public function __construct(private readonly Comisiones $comisiones)
    {
    }

    public function index(Request $request): View
    {
        $periodo = Comisiones::periodo($request->get('periodo'));
        $base = Comisiones::base($request->get('base'));

        [$inicio, $fin] = Comisiones::limites($periodo);

        $filas = $this->comisiones->filasDelPeriodo($inicio, $fin, $periodo, $base);

        return view('structure.commercial_management.comisiones.index', [
            'periodo' => $periodo,
            'periodoTexto' => ucfirst($inicio->locale('es')->isoFormat('MMMM YYYY')),
            'anterior' => (clone $inicio)->subMonth()->format('Y-m'),
            'siguiente' => (clone $inicio)->addMonth()->format('Y-m'),
            'esMesActual' => $periodo === now()->format('Y-m'),
            'base' => $base,
            'bases' => self::BASES,
            'filas' => $filas,
            'resumen' => $this->comisiones->resumen($filas),
            'maximo' => (float) ($filas->max('vendido') ?: 0),
            'pagos' => $this->comisiones->pagosDelPeriodo($periodo),
            'puedeGestionar' => $request->user()->can('comisiones.gestionar'),
        ]);
    }

    /** Define (o cambia) el porcentaje de comisión de un asesor. */
    public function porcentaje(Request $request, User $asesor): RedirectResponse
    {
        $data = $request->validate(Comisiones::REGLAS_PORCENTAJE);

        $this->comisiones->definirPorcentaje($asesor, $data['porcentaje']);

        return back()->with('status', "Porcentaje de {$asesor->name} actualizado.");
    }

    /** Registra un pago de comisión a un asesor por un mes. */
    public function pagar(Request $request): RedirectResponse
    {
        $data = $request->validate(Comisiones::reglasPago(), Comisiones::MENSAJES_PAGO);

        $pago = $this->comisiones->registrarPago($data, $request->user());

        return redirect()
            ->route('commercial.comisiones.index', ['periodo' => $data['periodo'], 'base' => $data['base']])
            ->with('status', 'Pago de $'.number_format((float) $pago->monto, 2).' registrado a '.$pago->asesor->name.'.');
    }

    /** Quita un pago capturado por error. */
    public function eliminarPago(ComisionPago $pago): RedirectResponse
    {
        $pago->delete();

        return back()->with('status', 'Pago de comisión eliminado.');
    }
}

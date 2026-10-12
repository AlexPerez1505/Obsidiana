<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\Nomina\QuincenaDeNomina;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Nómina por quincena: se calcula sola con la asistencia y las reglas de RH.
 * Los números y las acciones viven en QuincenaDeNomina, que comparte con la API.
 */
class NominaController extends Controller
{
    public function __construct(private QuincenaDeNomina $quincena)
    {
    }

    public function index(Request $request): View
    {
        [$desde, $hasta] = $this->periodo($request);
        $q = $this->quincena->resumen($desde, $hasta);

        return view('nomina.quincena', [
            'desde' => $desde, 'hasta' => $hasta, 'run' => $q['run'],
            'filas' => $q['filas'], 'totales' => $q['totales'],
            'concluida' => $q['concluida'],
            'enCurso' => $q['en_curso'],
            'anterior' => $q['anterior'],
            'siguiente' => $q['siguiente'],
            'sinSueldo' => $q['sin_sueldo'],
            'pendientes' => $q['pendientes'],
        ]);
    }

    public function cerrar(Request $request): RedirectResponse
    {
        [$desde, $hasta] = $this->periodo($request);

        try {
            $run = $this->quincena->cerrar($desde, $hasta, $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['periodo' => $e->getMessage()]);
        }

        return back()->with('status', 'Quincena cerrada: '.$run->employees.' empleados, neto $'.number_format($run->total_neto, 2).'. Los montos quedaron congelados.');
    }

    public function reabrir(Request $request): RedirectResponse
    {
        [$desde, $hasta] = $this->periodo($request);

        $this->quincena->reabrir($desde, $hasta);

        return back()->with('status', 'Quincena reabierta: vuelve a calcularse con la asistencia y las reglas actuales.');
    }

    public function pagada(Request $request): RedirectResponse
    {
        [$desde, $hasta] = $this->periodo($request);

        try {
            $run = $this->quincena->alternarPagada($desde, $hasta);
        } catch (\DomainException $e) {
            return back()->withErrors(['periodo' => $e->getMessage()]);
        }

        return back()->with('status', $run->status === 'pagada' ? 'Quincena marcada como pagada.' : 'Quincena marcada como pendiente de pago.');
    }

    /** Bono o descuento solo para esta quincena, desde el desglose de una persona. */
    public function conceptoUnico(Request $request, User $user): RedirectResponse
    {
        [$desde, $hasta] = $this->periodo($request);

        // Antes de validar: con la quincena cerrada no tiene caso revisar el formulario
        if ($this->quincena->corrida($desde, $hasta)) {
            return back()->withErrors(['periodo' => 'La quincena está cerrada: reábrela para agregar conceptos.']);
        }

        $data = $request->validate(QuincenaDeNomina::reglasConceptoUnico(), QuincenaDeNomina::mensajesConceptoUnico());

        $this->quincena->agregarConceptoUnico($user, $desde, $hasta, $data, $request->user()->id);

        return back()->with('status', ($data['kind'] === 'bono' ? 'Bono' : 'Descuento')." agregado a {$user->name} solo para esta quincena.")
            ->with('abrir', 'u'.$user->id);
    }

    public function historial(): View
    {
        return view('nomina.historial', [
            'corridas' => PayrollRun::with('cerro')->orderByDesc('period_start')->limit(48)->get(),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        [$desde, $hasta] = $this->periodo($request);

        return $this->quincena->descargarCsv($desde, $hasta);
    }

    private function periodo(Request $request): array
    {
        return QuincenaDeNomina::periodo($request->get('desde'));
    }
}

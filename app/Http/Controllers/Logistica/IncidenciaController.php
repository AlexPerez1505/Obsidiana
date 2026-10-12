<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\RutaIncidencia;
use App\Services\Logistica\OperacionDeRutas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Tablero de incidencias reportadas por los choferes. */
class IncidenciaController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->get('estado') === 'resueltas' ? 'resueltas' : 'abiertas';

        $incidencias = RutaIncidencia::with(['ruta.vehicle', 'ruta.chofer', 'parada', 'usuario'])
            ->when($estado === 'abiertas', fn ($q) => $q->whereNull('resuelta_en'))
            ->when($estado === 'resueltas', fn ($q) => $q->whereNotNull('resuelta_en'))
            ->latest()->paginate(20)->withQueryString();

        return view('structure.logistica.incidencias', [
            'incidencias' => $incidencias,
            'estado' => $estado,
            'abiertas' => RutaIncidencia::whereNull('resuelta_en')->count(),
        ]);
    }

    public function resolver(Request $request, RutaIncidencia $incidencia, OperacionDeRutas $op): RedirectResponse
    {
        $data = $request->validate(['resolucion' => ['nullable', 'string', 'max:500']]);

        $op->resolverIncidencia($incidencia, $data['resolucion'] ?? null, $request->user()->id);

        return back()->with('status', 'Incidencia marcada como resuelta.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\LineaDeTiempo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Línea de tiempo: qué hizo cada quien, día por día, en una sola pantalla.
 */
class ActividadController extends Controller
{
    public function index(Request $request, LineaDeTiempo $linea): View
    {
        $hasta = $this->fecha($request->get('hasta')) ?? now();
        $desde = $this->fecha($request->get('desde')) ?? $hasta->copy()->subDays(6);

        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        // Tope de 92 días: la lista se arma en memoria.
        if ($desde->diffInDays($hasta) > 92) {
            $desde = $hasta->copy()->subDays(92);
        }

        $usuarioId = $request->integer('usuario') ?: null;
        $modulo = array_key_exists($request->get('modulo'), LineaDeTiempo::MODULOS) ? $request->get('modulo') : null;

        $eventos = $linea->eventos($desde, $hasta, $usuarioId, $modulo);

        return view('admin.actividad.index', [
            'desde' => $desde,
            'hasta' => $hasta,
            'usuarioId' => $usuarioId,
            'modulo' => $modulo,
            'modulos' => LineaDeTiempo::MODULOS,
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
            // Agrupados por día, del más reciente al más viejo.
            'porDia' => $eventos->groupBy(fn ($e) => $e['cuando']->toDateString())->sortKeysDesc(),
            'total' => $eventos->count(),
            'porModulo' => $eventos->countBy('modulo'),
            'porUsuario' => $eventos->countBy('usuario')->sortDesc(),
        ]);
    }

    private function fecha(?string $valor): ?Carbon
    {
        try {
            return $valor ? Carbon::parse($valor) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

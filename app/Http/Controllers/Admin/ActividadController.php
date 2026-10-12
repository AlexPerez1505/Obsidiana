<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\LineaDeTiempo;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Línea de tiempo: qué hizo cada quien, día por día, en una sola pantalla.
 */
class ActividadController extends Controller
{
    public function index(Request $request, LineaDeTiempo $linea): View
    {
        // Por omisión la última semana, con tope de días (ver la clase).
        [$desde, $hasta] = LineaDeTiempo::rango($request->get('desde'), $request->get('hasta'));

        $usuarioId = $request->integer('usuario') ?: null;
        $modulo = LineaDeTiempo::modulo($request->get('modulo'));

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
}

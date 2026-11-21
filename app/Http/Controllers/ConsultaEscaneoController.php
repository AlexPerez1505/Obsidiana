<?php

namespace App\Http\Controllers;

use App\Services\Inventario\ConsultaPorEscaneo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Consulta por escaneo: se escanea una etiqueta y en segundos se ve qué
 * es, dónde está, cuántas piezas se pueden surtir y su historial.
 *
 * Todo es GET y de solo lectura: consultar no reserva, no mueve ni cambia
 * nada (tampoco dispara el tiempo real, que solo escucha escrituras).
 */
class ConsultaEscaneoController extends Controller
{
    public function index(): View
    {
        return view('structure.gestion_Inventario.consulta_escaneo.index');
    }

    /** Qué coincide con lo leído; si es una sola cosa, ya trae la ficha. */
    public function buscar(Request $request): JsonResponse
    {
        $data = $request->validate([
            // Texto, no número: se conservan los ceros a la izquierda.
            'codigo' => ['required', 'string', 'max:255'],
        ]);

        $consulta = new ConsultaPorEscaneo($request->user());
        $r = $consulta->buscar($data['codigo']);

        if ($r['estado'] === 'unico') {
            $r['ficha'] = $consulta->ficha($r['tipo'], $r['id'], $r['codigo']);
        }

        return response()->json($r);
    }

    /** La ficha de una de las opciones, cuando hubo varias coincidencias. */
    public function ficha(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:pieza,producto'],
            'id' => ['required', 'integer', 'min:1'],
            'codigo' => ['nullable', 'string', 'max:255'],
        ]);

        $ficha = (new ConsultaPorEscaneo($request->user()))
            ->ficha($data['tipo'], (int) $data['id'], (string) ($data['codigo'] ?? ''));

        return $ficha
            ? response()->json(['estado' => 'unico', 'ficha' => $ficha])
            : response()->json(['estado' => 'no_encontrado'], 404);
    }

    /** Movimientos anteriores (el historial se carga por partes). */
    public function historial(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:pieza,producto'],
            'id' => ['required', 'integer', 'min:1'],
            'cargados' => ['required', 'integer', 'min:0'],
        ]);

        $pagina = (new ConsultaPorEscaneo($request->user()))
            ->historial($data['tipo'], (int) $data['id'], (int) $data['cargados']);

        return $pagina
            ? response()->json($pagina)
            : response()->json(['mensaje' => 'Ya no existe.'], 404);
    }
}

<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PiezaComponente;
use App\Models\ProductoSerial;
use App\Services\Inventario\ComponentesDePieza;
use App\Support\EdicionPropia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Componentes de una pieza (procesador, fuente, pigtail, interfaz…). Todo en
 * JSON: se manejan desde una ventana sin salir del detalle.
 */
class ComponenteController extends Controller
{
    public function __construct(private ComponentesDePieza $componentes) {}

    public function index(Request $request, ProductoSerial $pieza): JsonResponse
    {
        return response()->json($this->componentes->listar($pieza, $request->user()));
    }

    public function store(Request $request, ProductoSerial $pieza): JsonResponse
    {
        $data = $request->validate(ComponentesDePieza::reglas(true), ComponentesDePieza::MENSAJES);
        $nuevos = $this->componentes->agregar($pieza, $data, $request->user());

        return response()->json([
            'message' => $nuevos->count() === 1 ? 'Componente agregado.' : $nuevos->count().' componentes agregados.',
        ] + $this->componentes->listar($pieza, $request->user()));
    }

    public function update(Request $request, PiezaComponente $componente): JsonResponse
    {
        $data = $request->validate(ComponentesDePieza::reglas(false), ComponentesDePieza::MENSAJES);
        $estabaPorLlegar = $componente->porLlegar();
        $this->componentes->actualizar($componente, $data, $request->user());

        return response()->json([
            'message' => $estabaPorLlegar && ! $componente->porLlegar() ? "{$componente->nombre} quedó como recibido." : 'Componente actualizado.',
        ] + $this->componentes->listar($componente->pieza, $request->user()));
    }

    public function destroy(Request $request, PiezaComponente $componente): JsonResponse
    {
        abort_unless(EdicionPropia::puedeEliminarComponente($request->user(), $componente), 403, EdicionPropia::mensaje('quitar'));

        $pieza = $componente->pieza;
        $componente->delete();

        return response()->json(['message' => 'Componente quitado.'] + $this->componentes->listar($pieza, $request->user()));
    }
}

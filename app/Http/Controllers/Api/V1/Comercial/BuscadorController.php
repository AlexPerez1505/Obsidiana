<?php

namespace App\Http\Controllers\Api\V1\Comercial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Comercial\ClienteBuscadoResource;
use App\Http\Resources\Api\V1\Comercial\PaqueteCotizableResource;
use App\Http\Resources\Api\V1\Comercial\ProductoCotizableResource;
use App\Models\FichaTecnica;
use App\Services\Cotizaciones\BuscadorCotizador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Buscadores para armar una cotización o una venta: los mismos que usa
 * el formulario web. Piden poder crear o editar cotizaciones o ventas.
 * Sin paginar: regresan los primeros resultados (15 clientes, 10 paquetes
 * + 20 productos, 20 fichas); `q` vacío trae los primeros por nombre.
 *
 * @tags Cotizaciones · Buscadores
 */
class BuscadorController extends Controller
{
    /** Quien arma documentos: en la web estos buscadores solo los usa ese formulario. */
    private const PUEDEN = ['cotizaciones.crear', 'cotizaciones.editar', 'ventas.crear', 'ventas.editar'];

    public function __construct(private readonly BuscadorCotizador $buscador)
    {
    }

    /**
     * Buscar clientes.
     *
     * Por nombre, apellido, correo, teléfono o RFC. Respeta el alcance del
     * directorio: sin `clientes.ver_todos` solo los del propio usuario.
     * Cada uno trae su `historial` (compras, cotizaciones, saldo pendiente
     * y última compra).
     */
    public function clientes(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->canAny(self::PUEDEN), 403);
        $datos = $request->validate(['q' => ['nullable', 'string', 'max:255']]);

        return ClienteBuscadoResource::collection(
            $this->buscador->clientes($request->user(), $this->texto($datos))
        );
    }

    /**
     * Buscar productos y paquetes.
     *
     * Primero los paquetes activos (por nombre) y luego los productos (por
     * tipo, subtipo, modelo, marca o número de serie) con su stock vendible,
     * las piezas disponibles y su ficha técnica si tiene.
     */
    public function productos(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(self::PUEDEN), 403);
        $datos = $request->validate(['q' => ['nullable', 'string', 'max:255']]);

        $q = $this->texto($datos);

        return response()->json(['data' => array_merge(
            PaqueteCotizableResource::collection($this->buscador->paquetes($q))->resolve($request),
            ProductoCotizableResource::collection($this->buscador->productos($q))->resolve($request),
        )]);
    }

    /**
     * Buscar fichas técnicas.
     *
     * Fichas activas por título, para adjuntarlas (en `fichas`) al PDF.
     */
    public function fichas(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(self::PUEDEN), 403);
        $datos = $request->validate(['q' => ['nullable', 'string', 'max:255']]);

        return response()->json(['data' => $this->buscador->fichas($this->texto($datos))
            ->map(fn (FichaTecnica $f) => [
                'id' => $f->id,
                'titulo' => $f->titulo,
            ])->values()]);
    }

    private function texto(array $datos): string
    {
        return trim((string) ($datos['q'] ?? ''));
    }
}

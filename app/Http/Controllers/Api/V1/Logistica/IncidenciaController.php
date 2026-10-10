<?php

namespace App\Http\Controllers\Api\V1\Logistica;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Logistica\IncidenciaResource;
use App\Models\RutaIncidencia;
use App\Services\Logistica\OperacionDeRutas;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Tablero de incidencias que reportan los choferes en ruta.
 *
 * @tags Logística
 */
class IncidenciaController extends Controller
{
    use ListadoApi;

    /**
     * Listar incidencias.
     *
     * `estado=abiertas` (por defecto) o `resueltas`; se puede filtrar por
     * `ruta_id` y `tipo`. Las más recientes primero. En `abiertas` viene el
     * total de incidencias sin resolver.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $f = $request->validate([
            'estado' => ['nullable', 'in:abiertas,resueltas'],
            'ruta_id' => ['nullable', 'integer'],
            'tipo' => ['nullable', 'in:'.implode(',', array_keys(RutaIncidencia::TIPOS))],
        ]);

        $estado = $f['estado'] ?? 'abiertas';

        $query = RutaIncidencia::with(['ruta.vehicle', 'ruta.chofer', 'parada', 'usuario'])
            ->when($estado === 'abiertas', fn ($q) => $q->whereNull('resuelta_en'))
            ->when($estado === 'resueltas', fn ($q) => $q->whereNotNull('resuelta_en'))
            ->when($f['ruta_id'] ?? null, fn ($q, $id) => $q->where('ruta_id', $id))
            ->when($f['tipo'] ?? null, fn ($q, $t) => $q->where('tipo', $t));

        $this->ordenar($query, $request, ['created_at', 'id', 'resuelta_en'], '-created_at')->orderByDesc('id');

        return IncidenciaResource::collection($query->paginate($this->porPagina($request)))->additional([
            'abiertas' => RutaIncidencia::whereNull('resuelta_en')->count(),
        ]);
    }

    /**
     * Ver una incidencia.
     */
    public function show(RutaIncidencia $incidencia): IncidenciaResource
    {
        return new IncidenciaResource($incidencia->load(['ruta.vehicle', 'ruta.chofer', 'parada', 'usuario']));
    }

    /**
     * Resolver una incidencia.
     *
     * La marca como atendida con una nota opcional (`resolucion`) y lo deja
     * en la bitácora de la ruta.
     */
    public function resolver(Request $request, RutaIncidencia $incidencia, OperacionDeRutas $op): IncidenciaResource
    {
        $data = $request->validate(['resolucion' => ['nullable', 'string', 'max:500']]);

        $op->resolverIncidencia($incidencia, $data['resolucion'] ?? null, $request->user()->id);

        return (new IncidenciaResource($incidencia->fresh(['ruta', 'parada', 'usuario'])))
            ->additional(['message' => 'Incidencia marcada como resuelta.']);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Administracion;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Administracion\ActividadResource;
use App\Support\LineaDeTiempo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Línea de tiempo: qué hizo cada quien (clientes, cotizaciones, ventas,
 * cobros, entradas, salidas, seguimientos, comisiones y tareas).
 *
 * @tags Administración · Actividad
 */
class ActividadController extends Controller
{
    use ListadoApi;

    /**
     * Línea de tiempo de actividad.
     *
     * Eventos del más reciente al más viejo, paginados. Por omisión la
     * última semana; `desde` y `hasta` (Y-m-d) cambian el rango, con tope
     * de 92 días. Filtros: `usuario` (id) y `modulo` (clientes,
     * cotizaciones, ventas, cobranza, inventario, salidas, seguimientos,
     * comisiones, tareas). `meta.resumen` trae los totales por módulo y
     * por usuario del rango completo.
     */
    public function index(Request $request, LineaDeTiempo $linea): AnonymousResourceCollection
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'usuario' => ['nullable', 'integer'],
            'modulo' => ['nullable', 'string'],
        ]);

        [$desde, $hasta] = LineaDeTiempo::rango($request->query('desde'), $request->query('hasta'));
        $usuarioId = $request->integer('usuario') ?: null;
        $modulo = LineaDeTiempo::modulo($request->query('modulo'));

        $eventos = $linea->eventos($desde, $hasta, $usuarioId, $modulo);

        // La lista se arma en memoria (ver LineaDeTiempo); se pagina aquí.
        $porPagina = $this->porPagina($request);
        $pagina = max(1, (int) $request->query('page', 1));

        $paginados = new LengthAwarePaginator(
            $eventos->forPage($pagina, $porPagina)->values(),
            $eventos->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return ActividadResource::collection($paginados)->additional(['meta' => [
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'usuario' => $usuarioId,
            'modulo' => $modulo,
            'modulos' => LineaDeTiempo::MODULOS,
            'resumen' => [
                'total' => $eventos->count(),
                'por_modulo' => $eventos->countBy('modulo'),
                'por_usuario' => $eventos->countBy('usuario')->sortDesc(),
            ],
        ]]);
    }
}

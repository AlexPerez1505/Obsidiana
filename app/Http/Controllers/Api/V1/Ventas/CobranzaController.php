<?php

namespace App\Http\Controllers\Api\V1\Ventas;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Ventas\CobranzaFilaResource;
use App\Services\Ventas\PanoramaCobranza;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

/**
 * Panorama de cobranza: quién debe, quién está atrasado, cuánto se vendió
 * y cobró por mes y qué asesor vende más. Mismo alcance que la web: sin
 * `ventas.ver_todas`, solo las ventas propias.
 *
 * @tags Cobranza
 */
class CobranzaController extends Controller
{
    use ListadoApi;

    public function __construct(private readonly PanoramaCobranza $panorama)
    {
    }

    /**
     * Quién debe.
     *
     * Una fila por venta activa con lo exigible, lo cobrado, el saldo, la
     * próxima fecha y los días de atraso; primero lo más atrasado.
     *
     * Filtros: `estado` (deben —el de siempre—, atrasadas, pagadas, todas),
     * `asesor_id` y `buscar` (folio, cliente, asesor o producto). Trae los
     * `conteos` de cada filtro y el `resumen` (totales y lo del mes).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'estado' => ['nullable', Rule::in(PanoramaCobranza::ESTADOS)],
            'asesor_id' => ['nullable', 'integer'],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $filas = $this->panorama->filas($request->user(), $request->integer('asesor_id') ?: null);
        $filtradas = $this->panorama->filtrar(
            $filas,
            (string) $request->query('estado', 'deben'),
            trim((string) $request->query('buscar', ''))
        );

        // La lista se arma en PHP (el saldo vive en el modelo), así que se pagina aquí.
        $porPagina = $this->porPagina($request);
        $pagina = LengthAwarePaginator::resolveCurrentPage();
        $paginado = new LengthAwarePaginator(
            $filtradas->forPage($pagina, $porPagina)->values(),
            $filtradas->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return CobranzaFilaResource::collection($paginado)->additional([
            'conteos' => $this->panorama->conteos($filas),
            'resumen' => $this->panorama->resumen($filas, $request->user()),
        ]);
    }

    /**
     * Gráficas de cobranza.
     *
     * El resumen (por cobrar, vencido, vendido y cobrado del mes), lo
     * vendido contra lo cobrado de los últimos 12 meses, el ranking de
     * asesores y la lista de asesores por los que se puede filtrar.
     */
    public function panorama(Request $request): JsonResponse
    {
        $user = $request->user();
        $filas = $this->panorama->filas($user);

        return response()->json(['data' => [
            'resumen' => $this->panorama->resumen($filas, $user),
            'por_mes' => $this->panorama->porMes($user),
            'asesores' => $this->panorama->rankingAsesores($user),
            'lista_asesores' => $this->panorama->listaAsesores($user)
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
                ->values(),
        ]]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Marketing;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Marketing\TareaResource;
use App\Models\Task;
use App\Services\Marketing\TareasDeMarketing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Marketing
 */
class TableroController extends Controller
{
    use ListadoApi;

    public function __construct(private TareasDeMarketing $tareas)
    {
    }

    /**
     * Inicio de Marketing.
     *
     * Los números de la pantalla de inicio: piezas con cambios
     * solicitados, en revisión, pendientes por tomar y cuántas categorías
     * distintas se están trabajando.
     */
    public function inicio(): JsonResponse
    {
        return response()->json(['data' => $this->tareas->resumenInicio()]);
    }

    /**
     * Aprobación de flyers.
     *
     * Las piezas por fecha de entrega con los contadores del tablero de
     * aprobación (`stats`). `status` filtra el listado (los contadores
     * siempre son de todas). Requiere el permiso de aprobar flyers.
     */
    public function aprobacionFlyers(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'status' => ['nullable', 'in:'.implode(',', array_keys(TareasDeMarketing::ESTADOS))],
        ]);

        $stats = $this->tareas->estadisticasDeAprobacion(Task::get(['id', 'status', 'rejection_comment']));

        $tareas = Task::with(['user', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderBy('due_date')
            ->orderBy('id')
            ->paginate($this->porPagina($request));

        return TareaResource::collection($tareas)->additional(['stats' => $stats]);
    }

    /**
     * Calendario de contenido.
     *
     * Las piezas que se entregan en un mes (`month` 1-12 y `year`; por
     * defecto el mes actual), por fecha de entrega, con las categorías del
     * calendario y su color. No se pagina: es un mes.
     */
    public function calendario(Request $request): AnonymousResourceCollection
    {
        $datos = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        $month = (int) ($datos['month'] ?? now()->month);
        $year = (int) ($datos['year'] ?? now()->year);

        return TareaResource::collection($this->tareas->delMes($month, $year)->get())
            ->additional([
                'mes' => ['month' => $month, 'year' => $year],
                'categorias' => TareasDeMarketing::CATEGORIAS,
            ]);
    }
}

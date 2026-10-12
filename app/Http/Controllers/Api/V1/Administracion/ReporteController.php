<?php

namespace App\Http\Controllers\Api\V1\Administracion;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Administracion\ReporteResource;
use App\Services\Administracion\ReportesDePersonal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Reportes de personal: asistencias, faltas, vacaciones, permisos e
 * incidencias registradas a mano en Administración → Reportes.
 *
 * Consultar no pide permiso aparte (igual que la pantalla); registrar es
 * solo de administradores.
 *
 * @tags Administración · Reportes
 */
class ReporteController extends Controller
{
    use ListadoApi;

    public function __construct(private ReportesDePersonal $reportes)
    {
    }

    /**
     * Listar reportes.
     *
     * Del más reciente al más viejo. Filtros: `start_date` y `end_date`
     * (sobre la fecha de inicio), `area` y `person` (nombre del
     * colaborador). Los totales del periodo están en `/reportes/resumen`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $this->filtros($request);

        return ReporteResource::collection(
            $this->reportes->consulta($filtros)->paginate($this->porPagina($request))
        );
    }

    /**
     * Resumen del periodo.
     *
     * Con los mismos filtros que el listado: los indicadores de la
     * pantalla (colaboradores, asistencia promedio, faltas, vacaciones,
     * permisos, incidencias), el resumen por colaborador, lo que sigue
     * pendiente por tipo y las áreas disponibles para filtrar.
     */
    public function resumen(Request $request): JsonResponse
    {
        $reportes = $this->reportes->consulta($this->filtros($request))->get();
        $colaboradores = $this->reportes->resumenPorColaborador($reportes);

        return response()->json(['data' => [
            'indicadores' => $this->reportes->indicadores($reportes, $colaboradores),
            'colaboradores' => $colaboradores,
            'pendientes' => $this->reportes->pendientesPorModulo($reportes),
            'areas' => $this->reportes->areas(),
            'total' => $reportes->count(),
        ]]);
    }

    /**
     * Registrar reporte.
     *
     * Solo administradores. `type`: asistencia, falta, vacaciones, permiso
     * o incidencia. `status`: pendiente, validada, aprobada, justificar,
     * revision, rechazada o cancelada. Si `employee_name` coincide con un
     * usuario, queda ligado a él.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(ReportesDePersonal::reglasAlta());

        $reporte = $this->reportes->registrar($data, $request->user());

        return (new ReporteResource($reporte->load('employee')))
            ->additional(['message' => 'Reporte generado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    private function filtros(Request $request): array
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'area' => ['nullable', 'string', 'max:255'],
            'person' => ['nullable', 'string', 'max:150'],
        ]);

        // Sin área (o 'all') no se filtra por área.
        return array_filter([
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'area' => $request->query('area'),
            'person' => $request->query('person'),
        ], fn ($v) => $v !== null && $v !== '');
    }
}

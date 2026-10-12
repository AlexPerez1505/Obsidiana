<?php

namespace App\Http\Controllers\Api\V1\Rh;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rh\FestivoResource;
use App\Models\Holiday;
use App\Services\Asistencia\ConfiguracionDeAsistencia;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Días festivos: nadie debe checar. Agregar o quitar uno que ya pasó
 * corrige las faltas automáticas de ese día (las que puso RH a mano no se
 * tocan).
 *
 * @tags RH · Asistencia
 */
class FestivoController extends Controller
{
    use ListadoApi;

    public function __construct(private ConfiguracionDeAsistencia $config)
    {
    }

    /**
     * Listar festivos.
     *
     * Por defecto, del 1 de enero del año en curso en adelante, en orden de
     * fecha. Con `anio` solo los de ese año.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'anio' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        $q = $request->filled('anio')
            ? Holiday::whereYear('date', $request->integer('anio'))
            : Holiday::whereDate('date', '>=', ServicioDeAsistencia::diaLocal(now())->startOfYear()->toDateString());

        $this->ordenar($q, $request, ['date', 'id'], 'date');

        return FestivoResource::collection($q->paginate($this->porPagina($request)));
    }

    /**
     * Agregar festivo.
     */
    public function store(Request $request): FestivoResource
    {
        $data = $request->validate(ConfiguracionDeAsistencia::reglasFestivo(), ConfiguracionDeAsistencia::mensajesFestivo());

        /** @status 201 */
        return (new FestivoResource($this->config->crearFestivo($data)))
            ->additional(['message' => 'Festivo agregado.']);
    }

    /**
     * Cargar los festivos oficiales del año.
     *
     * Los días de descanso obligatorio de la Ley Federal del Trabajo
     * (art. 74) para `anio`. Solo agrega los que faltan; devuelve todos los
     * festivos de ese año y cuántos fueron nuevos.
     */
    public function oficiales(Request $request): AnonymousResourceCollection
    {
        $anio = (int) $request->validate(ConfiguracionDeAsistencia::reglasFestivosOficiales())['anio'];

        $nuevos = $this->config->cargarFestivosOficiales($anio);

        return FestivoResource::collection(Holiday::whereYear('date', $anio)->orderBy('date')->get())
            ->additional([
                'nuevos' => $nuevos,
                'message' => $nuevos
                    ? "Se agregaron {$nuevos} festivo(s) oficiales de {$anio}. Ya aparecen en la Agenda."
                    : "Los festivos oficiales de {$anio} ya estaban cargados.",
            ]);
    }

    /**
     * Eliminar festivo.
     */
    public function destroy(Holiday $festivo): JsonResponse
    {
        $this->config->eliminarFestivo($festivo);

        return response()->json(['message' => 'Festivo eliminado.']);
    }
}

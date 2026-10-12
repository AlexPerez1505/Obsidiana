<?php

namespace App\Http\Controllers\Api\V1\Rh;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rh\ReglaResource;
use App\Models\PayrollRule;
use App\Models\PayrollSetting;
use App\Services\Nomina\ConfiguracionDeNomina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Reglas de nómina: "cuando pase esto, bonificar o descontar tanto". Cada
 * regla tiene condiciones (todas deben cumplirse) sobre métricas de
 * asistencia de la quincena o del mes, cuántas veces se aplica y cuánto
 * (monto fijo, días de sueldo o % del sueldo). Una condición sobre el mes
 * completo hace que la regla se aplique al cerrar el mes.
 *
 * @tags RH · Nómina
 */
class ReglaNominaController extends Controller
{
    use ListadoApi;

    public function __construct(private ConfiguracionDeNomina $nomina)
    {
    }

    /**
     * Listar reglas.
     *
     * En el orden en que se aplican. Trae los catálogos para armar una
     * regla (métricas, operadores, tipos de cálculo) y cuántos días vale
     * la quincena para "días de sueldo".
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            // bono | descuento
            'kind' => ['nullable', 'in:bono,descuento'],
            'active' => ['nullable', 'boolean'],
        ]);

        $q = PayrollRule::query()
            ->when($request->filled('kind'), fn ($q) => $q->where('kind', $request->query('kind')))
            ->when($request->filled('active'), fn ($q) => $q->where('active', $request->boolean('active')));

        $this->ordenar($q, $request, ['sort', 'name', 'id'], 'sort');
        $q->orderBy('id');

        return ReglaResource::collection($q->paginate($this->porPagina($request)))->additional([
            'catalogos' => [
                'metricas' => collect(PayrollRule::METRICAS)->map(fn ($m) => ['label' => $m[0], 'unidad' => $m[1]]),
                'operadores' => PayrollRule::OPERADORES,
                'calculos' => PayrollRule::CALCULOS,
                'dias_sueldo' => PayrollSetting::diasSueldo(),
            ],
        ]);
    }

    /**
     * Ver una regla.
     */
    public function show(PayrollRule $regla): ReglaResource
    {
        return new ReglaResource($regla);
    }

    /**
     * Crear regla.
     *
     * `conditions`: de 1 a 5 `{metric, scope: periodo|mes, op, value}`.
     * `repeat_mode: por_cada` repite el monto por cada `repeat_every` de
     * `repeat_metric` (con tope opcional `repeat_max`). `scope`: todos,
     * incluir (solo `user_ids`) o excluir (todos menos `user_ids`).
     */
    public function store(Request $request): ReglaResource
    {
        $data = $request->validate(ConfiguracionDeNomina::reglasRegla(), ConfiguracionDeNomina::mensajesRegla());

        [$regla, , $alCierre] = $this->nomina->guardarRegla($data, null, $request->user()->id);

        /** @status 201 */
        return (new ReglaResource($regla))
            ->additional(['message' => "Regla «{$regla->name}» creada.".$this->avisoMes($alCierre)]);
    }

    /**
     * Editar regla.
     *
     * Mismos campos que al crearla; se reemplaza completa. Las quincenas ya
     * cerradas no cambian.
     */
    public function update(Request $request, PayrollRule $regla): ReglaResource
    {
        $data = $request->validate(ConfiguracionDeNomina::reglasRegla(), ConfiguracionDeNomina::mensajesRegla());

        [$regla, , $alCierre] = $this->nomina->guardarRegla($data, $regla, $request->user()->id);

        return (new ReglaResource($regla))
            ->additional(['message' => "Regla «{$regla->name}» actualizada.".$this->avisoMes($alCierre)]);
    }

    /**
     * Activar o desactivar una regla.
     */
    public function alternar(PayrollRule $regla): ReglaResource
    {
        $regla->update(['active' => ! $regla->active]);

        return (new ReglaResource($regla))
            ->additional(['message' => "«{$regla->name}» ".($regla->active ? 'activada' : 'desactivada').'.']);
    }

    /**
     * Eliminar regla.
     */
    public function destroy(PayrollRule $regla): JsonResponse
    {
        $nombre = $regla->name;
        $regla->delete();

        return response()->json(['message' => "Regla «{$nombre}» eliminada."]);
    }

    /**
     * Ver ajustes de nómina.
     *
     * `dias_sueldo`: entre cuántos días se divide el sueldo de la quincena
     * para sacar "un día de sueldo".
     */
    public function ajustes(): JsonResponse
    {
        return response()->json(['data' => ['dias_sueldo' => PayrollSetting::diasSueldo()]]);
    }

    /**
     * Guardar ajustes de nómina.
     */
    public function guardarAjustes(Request $request): JsonResponse
    {
        $data = $request->validate(['dias_sueldo' => ['required', 'integer', 'between:1,31']]);

        PayrollSetting::guardar('dias_sueldo', $data['dias_sueldo']);

        return response()->json([
            'message' => 'Un día de sueldo ahora es el sueldo de la quincena ÷ '.$data['dias_sueldo'].'.',
            'data' => ['dias_sueldo' => PayrollSetting::diasSueldo()],
        ]);
    }

    private function avisoMes(bool $alCierre): string
    {
        return $alCierre ? ' Como mira el mes completo, se aplica al cerrar el mes.' : '';
    }
}

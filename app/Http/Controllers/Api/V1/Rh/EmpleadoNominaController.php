<?php

namespace App\Http\Controllers\Api\V1\Rh;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rh\ConceptoResource;
use App\Http\Resources\Api\V1\Rh\EmpleadoNominaResource;
use App\Models\PayrollConcept;
use App\Models\User;
use App\Services\Nomina\ConfiguracionDeNomina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

/**
 * Empleados y sueldos: el sueldo quincenal de cada persona y sus bonos o
 * descuentos fijos (transporte, préstamo…). Dato sensible: todo aquí pide
 * el permiso asistencia.nomina.
 *
 * @tags RH · Nómina
 */
class EmpleadoNominaController extends Controller
{
    use ListadoApi;

    public function __construct(private ConfiguracionDeNomina $nomina)
    {
    }

    /**
     * Listar empleados con su sueldo.
     *
     * Cuentas aprobadas con su sueldo quincenal, si toman asistencia (solo
     * ellas entran al cálculo de la quincena) y sus conceptos propios.
     * `sin_sueldo` cuenta a quienes aún no tienen sueldo capturado.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            // Busca en nombre, correo o número de checador.
            'buscar' => ['nullable', 'string', 'max:100'],
            // true: solo quien no tiene sueldo capturado.
            'sin_sueldo' => ['nullable', 'boolean'],
        ]);

        $q = User::with('horario')->where('status', User::STATUS_APPROVED)
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = '%'.$request->query('buscar').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $b)->orWhere('email', 'like', $b)->orWhere('checador_id', 'like', $b));
            })
            ->when($request->boolean('sin_sueldo'), fn ($q) => $q->whereNull('sueldo_quincenal'));

        $this->ordenar($q, $request, ['name', 'id'], 'name');

        $pagina = $q->paginate($this->porPagina($request));
        $this->conConceptos($pagina->getCollection());

        return EmpleadoNominaResource::collection($pagina)->additional([
            'sin_sueldo' => User::where('status', User::STATUS_APPROVED)->whereNull('sueldo_quincenal')->count(),
        ]);
    }

    /**
     * Ver un empleado con su sueldo.
     */
    public function show(User $user): EmpleadoNominaResource
    {
        $this->conConceptos(collect([$user->load('horario')]));

        return new EmpleadoNominaResource($user);
    }

    /**
     * Guardar el sueldo quincenal.
     *
     * Vacío (o `null`) lo quita: la persona aparece en la quincena con aviso
     * de "sin sueldo".
     */
    public function sueldo(Request $request, User $user): EmpleadoNominaResource
    {
        $data = $request->validate(['sueldo_quincenal' => ['nullable', 'numeric', 'min:0', 'max:9999999']]);

        $user->forceFill(['sueldo_quincenal' => $data['sueldo_quincenal'] ?? null])->save();

        $this->conConceptos(collect([$user->load('horario')]));

        return (new EmpleadoNominaResource($user))->additional(['message' => "Sueldo de {$user->name} guardado."]);
    }

    /**
     * Agregar bono o descuento fijo.
     *
     * Se aplica en cada quincena dentro de su vigencia (`valid_from` /
     * `valid_until`, opcionales). Para una sola quincena usa el concepto
     * de una sola vez de la quincena.
     */
    public function conceptoFijo(Request $request, User $user): ConceptoResource
    {
        $data = $request->validate(ConfiguracionDeNomina::reglasConceptoFijo(), ConfiguracionDeNomina::mensajesConceptoFijo());

        $concepto = $this->nomina->crearConceptoFijo($user, $data, $request->user()->id);

        /** @status 201 */
        return (new ConceptoResource($concepto))
            ->additional(['message' => ($data['kind'] === 'bono' ? 'Bono' : 'Descuento')." fijo agregado a {$user->name}."]);
    }

    /**
     * Eliminar un concepto.
     *
     * Sirve para fijos y de una sola vez. Las quincenas ya cerradas no
     * cambian (lo congelado se queda como estaba).
     */
    public function eliminarConcepto(PayrollConcept $concepto): JsonResponse
    {
        $concepto->delete();

        return response()->json(['message' => 'Concepto eliminado.']);
    }

    /** Los conceptos de cada persona, en el orden de la web (tipo y nombre). */
    private function conConceptos(Collection $usuarios): void
    {
        $conceptos = PayrollConcept::whereIn('user_id', $usuarios->pluck('id'))
            ->orderBy('kind')->orderBy('name')->get()->groupBy('user_id');

        $usuarios->each(fn (User $u) => $u->setRelation('conceptos', $conceptos->get($u->id, collect())));
    }
}

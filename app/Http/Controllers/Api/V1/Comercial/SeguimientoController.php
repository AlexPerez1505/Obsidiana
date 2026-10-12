<?php

namespace App\Http\Controllers\Api\V1\Comercial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Comercial\SeguimientoResource;
use App\Models\ClienteSeguimiento;
use App\Models\Customer;
use App\Services\Clientes\SeguimientosCliente;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Seguimientos (recordatorios) de un cliente o prospecto. Todos piden
 * `clientes.editar` y que el cliente sea visible para el usuario, como en
 * la ficha web. El día de la fecha el responsable recibe el aviso.
 *
 * @tags Clientes · Seguimientos
 */
class SeguimientoController extends Controller
{
    public function __construct(private readonly SeguimientosCliente $seguimientos)
    {
    }

    /**
     * Programar seguimiento.
     *
     * `fecha` no puede ser anterior a hoy. `user_id` (a quién le toca) solo
     * se respeta con `clientes.ver_todos`; sin ese permiso el seguimiento
     * queda a nombre del usuario del token aunque se mande otro.
     */
    public function store(Request $request, Customer $cliente): JsonResponse
    {
        $this->asegurarVisible($request, $cliente);

        $data = $request->validate($this->seguimientos->reglasProgramar(), $this->seguimientos->mensajesProgramar());

        $seguimiento = $this->seguimientos->programar($cliente, $data, $request->user());

        return (new SeguimientoResource($seguimiento->load(['responsable', 'hechoPor'])))
            ->additional(['message' => 'Seguimiento programado. Se avisará el día que toque.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Marcar seguimiento como hecho.
     *
     * Guarda quién y cuándo, con un `resultado` opcional ("dijo que en
     * enero", "ya compró"…), y da por leído su aviso en la campana.
     */
    public function hecho(Request $request, Customer $cliente, ClienteSeguimiento $seguimiento): SeguimientoResource
    {
        $this->asegurarDelCliente($request, $cliente, $seguimiento);

        $data = $request->validate($this->seguimientos->reglasHecho());

        $this->seguimientos->marcarHecho($seguimiento, $data['resultado'] ?? null, $request->user());

        return (new SeguimientoResource($seguimiento->load(['responsable', 'hechoPor'])))
            ->additional(['message' => 'Seguimiento marcado como hecho.']);
    }

    /**
     * Reprogramar seguimiento.
     *
     * Lo mueve a otra `fecha` (hoy o después) y vuelve a avisar ese día.
     */
    public function reprogramar(Request $request, Customer $cliente, ClienteSeguimiento $seguimiento): SeguimientoResource
    {
        $this->asegurarDelCliente($request, $cliente, $seguimiento);

        $data = $request->validate($this->seguimientos->reglasReprogramar(), $this->seguimientos->mensajesReprogramar());

        $this->seguimientos->reprogramar($seguimiento, $data['fecha']);

        return (new SeguimientoResource($seguimiento->load(['responsable', 'hechoPor'])))
            ->additional(['message' => 'Seguimiento movido al '.Carbon::parse($data['fecha'])->format('d/m/Y').'.']);
    }

    /**
     * Eliminar seguimiento.
     *
     * Lo borra y da por leído su aviso pendiente.
     */
    public function destroy(Request $request, Customer $cliente, ClienteSeguimiento $seguimiento): JsonResponse
    {
        $this->asegurarDelCliente($request, $cliente, $seguimiento);

        $this->seguimientos->eliminar($seguimiento);

        return response()->json(['message' => 'Seguimiento eliminado.']);
    }

    private function asegurarVisible(Request $request, Customer $cliente): void
    {
        abort_unless($cliente->visiblePara($request->user()), 403, 'Este cliente lo registró otro asesor.');
    }

    /** El seguimiento tiene que ser de ese cliente; si no, para este no existe. */
    private function asegurarDelCliente(Request $request, Customer $cliente, ClienteSeguimiento $seguimiento): void
    {
        $this->asegurarVisible($request, $cliente);

        abort_if((int) $seguimiento->customer_id !== (int) $cliente->id, 404, 'Ese seguimiento no es de este cliente.');
    }
}

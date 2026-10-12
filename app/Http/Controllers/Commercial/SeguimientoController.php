<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Models\ClienteSeguimiento;
use App\Models\Customer;
use App\Services\Clientes\SeguimientosCliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Seguimientos de un cliente o prospecto: programarlos, marcarlos hechos,
 * moverlos de fecha y convertir al prospecto en cliente.
 *
 * Lo que pasa en cada caso vive en SeguimientosCliente, que también usa
 * la API; aquí solo queda validar y regresar a la ficha.
 */
class SeguimientoController extends Controller
{
    public function __construct(private readonly SeguimientosCliente $seguimientos)
    {
    }

    public function store(Request $request, Customer $cliente): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);

        $data = $request->validate($this->seguimientos->reglasProgramar(), $this->seguimientos->mensajesProgramar());

        $this->seguimientos->programar($cliente, $data, $request->user());

        return $this->volver($cliente, 'Seguimiento programado. Se avisará el día que toque.');
    }

    public function hecho(Request $request, Customer $cliente, ClienteSeguimiento $seguimiento): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);
        abort_if($seguimiento->customer_id !== $cliente->id, 404);

        $data = $request->validate($this->seguimientos->reglasHecho());

        $this->seguimientos->marcarHecho($seguimiento, $data['resultado'] ?? null, $request->user());

        return $this->volver($cliente, 'Seguimiento marcado como hecho.');
    }

    public function reprogramar(Request $request, Customer $cliente, ClienteSeguimiento $seguimiento): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);
        abort_if($seguimiento->customer_id !== $cliente->id, 404);

        $data = $request->validate($this->seguimientos->reglasReprogramar(), $this->seguimientos->mensajesReprogramar());

        $this->seguimientos->reprogramar($seguimiento, $data['fecha']);

        return $this->volver($cliente, 'Seguimiento movido al '.\Carbon\Carbon::parse($data['fecha'])->format('d/m/Y').'.');
    }

    public function destroy(Request $request, Customer $cliente, ClienteSeguimiento $seguimiento): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);
        abort_if($seguimiento->customer_id !== $cliente->id, 404);

        $this->seguimientos->eliminar($seguimiento);

        return $this->volver($cliente, 'Seguimiento eliminado.');
    }

    /** El prospecto ya compró (o ya se decidió): pasa a cliente. */
    public function convertir(Request $request, Customer $cliente): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);

        $this->seguimientos->convertir($cliente);

        return $this->volver($cliente, trim($cliente->nombre.' '.$cliente->apellido).' ahora es cliente.');
    }

    private function volver(Customer $cliente, string $mensaje): RedirectResponse
    {
        return redirect()->to(route('commercial.clientes.show', $cliente).'#seguimientos')->with('status', $mensaje);
    }
}

<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Models\ClienteSeguimiento;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Seguimientos de un cliente o prospecto: programarlos, marcarlos hechos,
 * moverlos de fecha y convertir al prospecto en cliente.
 */
class SeguimientoController extends Controller
{
    public function store(Request $request, Customer $cliente): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);

        $data = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(ClienteSeguimiento::TIPOS))],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'nota' => ['nullable', 'string', 'max:500'],
            'user_id' => ['nullable', 'exists:users,id'],
        ], [
            'fecha.after_or_equal' => 'La fecha del seguimiento no puede ser anterior a hoy.',
        ]);

        // Solo quien ve a todos los clientes puede asignarle el seguimiento
        // a otra persona; los demás se lo programan a sí mismos.
        $responsable = $request->user()->can('clientes.ver_todos') && ! empty($data['user_id'])
            ? (int) $data['user_id']
            : $request->user()->id;

        $cliente->seguimientos()->create([
            'user_id' => $responsable,
            'tipo' => $data['tipo'],
            'fecha' => $data['fecha'],
            'nota' => $data['nota'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return $this->volver($cliente, 'Seguimiento programado. Se avisará el día que toque.');
    }

    public function hecho(Request $request, Customer $cliente, ClienteSeguimiento $seguimiento): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);
        abort_if($seguimiento->customer_id !== $cliente->id, 404);

        $data = $request->validate(['resultado' => ['nullable', 'string', 'max:500']]);

        $seguimiento->update([
            'hecho_en' => now(),
            'hecho_por' => $request->user()->id,
            'resultado' => $data['resultado'] ?? null,
        ]);

        // La notificación pendiente de este seguimiento ya no tiene caso.
        $this->marcarLeidas($seguimiento);

        return $this->volver($cliente, 'Seguimiento marcado como hecho.');
    }

    public function reprogramar(Request $request, Customer $cliente, ClienteSeguimiento $seguimiento): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);
        abort_if($seguimiento->customer_id !== $cliente->id, 404);

        $data = $request->validate([
            'fecha' => ['required', 'date', 'after_or_equal:today'],
        ], ['fecha.after_or_equal' => 'La fecha nueva no puede ser anterior a hoy.']);

        // Al moverlo, vuelve a avisar en la fecha nueva.
        $seguimiento->update(['fecha' => $data['fecha'], 'notificado_en' => null]);
        $this->marcarLeidas($seguimiento);

        return $this->volver($cliente, 'Seguimiento movido al '.\Carbon\Carbon::parse($data['fecha'])->format('d/m/Y').'.');
    }

    public function destroy(Request $request, Customer $cliente, ClienteSeguimiento $seguimiento): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);
        abort_if($seguimiento->customer_id !== $cliente->id, 404);

        $this->marcarLeidas($seguimiento);
        $seguimiento->delete();

        return $this->volver($cliente, 'Seguimiento eliminado.');
    }

    /** El prospecto ya compró (o ya se decidió): pasa a cliente. */
    public function convertir(Request $request, Customer $cliente): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);

        $cliente->update(['etapa' => 'cliente']);

        return $this->volver($cliente, trim($cliente->nombre.' '.$cliente->apellido).' ahora es cliente.');
    }

    /** Las notificaciones de la campana que apuntaban a este seguimiento se dan por leídas. */
    private function marcarLeidas(ClienteSeguimiento $seguimiento): void
    {
        User::whereKey($seguimiento->user_id)->first()
            ?->unreadNotifications()
            ->where('data->seguimiento_id', $seguimiento->id)
            ->update(['read_at' => now()]);
    }

    private function volver(Customer $cliente, string $mensaje): RedirectResponse
    {
        return redirect()->to(route('commercial.clientes.show', $cliente).'#seguimientos')->with('status', $mensaje);
    }
}

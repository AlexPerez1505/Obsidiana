<?php

namespace App\Services\Clientes;

use App\Models\ClienteSeguimiento;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * Lo que se hace con los seguimientos de un cliente o prospecto:
 * programarlos, marcarlos hechos, moverlos de fecha, borrarlos y
 * convertir al prospecto en cliente.
 *
 * Lo comparten la web y la API. Los recordatorios del día que toca los
 * manda otro servicio (App\Services\Seguimientos).
 */
class SeguimientosCliente
{
    public function reglasProgramar(): array
    {
        return [
            'tipo' => ['required', Rule::in(array_keys(ClienteSeguimiento::TIPOS))],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'nota' => ['nullable', 'string', 'max:500'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function mensajesProgramar(): array
    {
        return [
            'fecha.after_or_equal' => 'La fecha del seguimiento no puede ser anterior a hoy.',
        ];
    }

    public function reglasHecho(): array
    {
        return ['resultado' => ['nullable', 'string', 'max:500']];
    }

    public function reglasReprogramar(): array
    {
        return ['fecha' => ['required', 'date', 'after_or_equal:today']];
    }

    public function mensajesReprogramar(): array
    {
        return ['fecha.after_or_equal' => 'La fecha nueva no puede ser anterior a hoy.'];
    }

    /** Programa un seguimiento ya validado. */
    public function programar(Customer $cliente, array $data, User $usuario): ClienteSeguimiento
    {
        // Solo quien ve a todos los clientes puede asignarle el seguimiento
        // a otra persona; los demás se lo programan a sí mismos.
        $responsable = $usuario->can('clientes.ver_todos') && ! empty($data['user_id'])
            ? (int) $data['user_id']
            : $usuario->id;

        return $cliente->seguimientos()->create([
            'user_id' => $responsable,
            'tipo' => $data['tipo'],
            'fecha' => $data['fecha'],
            'nota' => $data['nota'] ?? null,
            'created_by' => $usuario->id,
        ]);
    }

    public function marcarHecho(ClienteSeguimiento $seguimiento, ?string $resultado, User $usuario): void
    {
        $seguimiento->update([
            'hecho_en' => now(),
            'hecho_por' => $usuario->id,
            'resultado' => $resultado,
        ]);

        // La notificación pendiente de este seguimiento ya no tiene caso.
        $this->marcarLeidas($seguimiento);
    }

    public function reprogramar(ClienteSeguimiento $seguimiento, string $fecha): void
    {
        // Al moverlo, vuelve a avisar en la fecha nueva.
        $seguimiento->update(['fecha' => $fecha, 'notificado_en' => null]);
        $this->marcarLeidas($seguimiento);
    }

    public function eliminar(ClienteSeguimiento $seguimiento): void
    {
        $this->marcarLeidas($seguimiento);
        $seguimiento->delete();
    }

    /** El prospecto ya compró (o ya se decidió): pasa a cliente. */
    public function convertir(Customer $cliente): void
    {
        $cliente->update(['etapa' => 'cliente']);
    }

    /** Las notificaciones de la campana que apuntaban a este seguimiento se dan por leídas. */
    private function marcarLeidas(ClienteSeguimiento $seguimiento): void
    {
        User::whereKey($seguimiento->user_id)->first()
            ?->unreadNotifications()
            ->where('data->seguimiento_id', $seguimiento->id)
            ->update(['read_at' => now()]);
    }
}

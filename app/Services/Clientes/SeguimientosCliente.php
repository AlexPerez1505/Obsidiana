<?php

namespace App\Services\Clientes;

use App\Models\ClienteSeguimiento;
use App\Models\Customer;
use App\Models\User;
use App\Services\Agenda\Avisos;
use App\Services\Agenda\Historial;
use App\Services\Agenda\Recordatorios;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Todo lo que se hace con un seguimiento de cliente o prospecto:
 * programarlo, completarlo (y programar el siguiente), reprogramarlo,
 * reasignarlo, editarlo, cancelarlo y convertir al prospecto en cliente.
 *
 * Es el único camino para cambiar un seguimiento: lo usan la ficha del
 * cliente, la API y la agenda. Por eso lo que se hace en un lado se ve
 * igual en el otro (es la misma fila), y en cada cambio se escribe el
 * historial y se reacomodan los recordatorios, dentro de una transacción.
 */
class SeguimientosCliente
{
    public function __construct(
        private readonly Recordatorios $recordatorios,
        private readonly Avisos $avisos,
    ) {}

    public function reglasProgramar(): array
    {
        return [
            'tipo' => ['required', Rule::in(array_keys(ClienteSeguimiento::TIPOS))],
            'fecha' => ['required', 'date', 'after_or_equal:'.$this->hoy()],
            'hora' => ['nullable', 'date_format:H:i'],
            'prioridad' => ['nullable', Rule::in(array_keys(ClienteSeguimiento::PRIORIDADES))],
            'nota' => ['nullable', 'string', 'max:500'],
            'user_id' => ['nullable', 'exists:users,id'],
            'recordatorios' => ['nullable', 'array'],
            'recordatorios.*' => ['integer', Rule::in(array_keys(config('agenda.recordatorios')))],
        ];
    }

    public function mensajesProgramar(): array
    {
        return [
            'fecha.after_or_equal' => 'La fecha del seguimiento no puede ser anterior a hoy.',
            'hora.date_format' => 'La hora debe ser HH:MM.',
        ];
    }

    public function reglasHecho(): array
    {
        return [
            'resultado' => ['nullable', 'string', 'max:500'],
            // Siguiente contacto, opcional: se crea como otro seguimiento.
            'siguiente_fecha' => ['nullable', 'date', 'after_or_equal:'.$this->hoy()],
            'siguiente_hora' => ['nullable', 'date_format:H:i'],
            'siguiente_tipo' => ['nullable', 'required_with:siguiente_fecha', Rule::in(array_keys(ClienteSeguimiento::TIPOS))],
            'siguiente_nota' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function reglasReprogramar(): array
    {
        return [
            'fecha' => ['required', 'date', 'after_or_equal:'.$this->hoy()],
            'hora' => ['nullable', 'date_format:H:i'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function mensajesReprogramar(): array
    {
        return ['fecha.after_or_equal' => 'La fecha nueva no puede ser anterior a hoy.'];
    }

    /** Hoy en la zona horaria de la empresa (no en UTC del servidor). */
    private function hoy(): string
    {
        return ClienteSeguimiento::hoy()->toDateString();
    }

    /** Programa un seguimiento ya validado. */
    public function programar(Customer $cliente, array $data, User $usuario, string $origen = 'cliente', ?int $anteriorId = null): ClienteSeguimiento
    {
        // Solo quien ve a todos los clientes (o reasigna en la agenda) puede
        // asignárselo a otra persona; los demás se lo programan a sí mismos.
        $puedeAsignar = $usuario->can('clientes.ver_todos') || $usuario->can('agenda.reasignar');
        $responsable = $puedeAsignar && ! empty($data['user_id']) ? (int) $data['user_id'] : $usuario->id;

        return DB::transaction(function () use ($cliente, $data, $usuario, $origen, $anteriorId, $responsable) {
            $s = $cliente->seguimientos()->create([
                'user_id' => $responsable,
                'tipo' => $data['tipo'],
                'prioridad' => $data['prioridad'] ?? 'normal',
                'estado' => 'pendiente',
                'origen' => $origen,
                'fecha' => $data['fecha'],
                'hora' => $data['hora'] ?? null,
                'recordatorios' => array_key_exists('recordatorios', $data) ? array_map('intval', (array) $data['recordatorios']) : null,
                'nota' => $data['nota'] ?? null,
                'anterior_id' => $anteriorId,
                'created_by' => $usuario->id,
            ]);

            Historial::registrar($s, 'creado', $usuario->id, null, $this->foto($s));
            $this->recordatorios->programar($s);

            if ($responsable !== $usuario->id) {
                $this->avisar($s, 'asignada', 'Nuevo seguimiento asignado: '.$s->nombreCliente(), $this->resumen($s).' · lo programó '.$usuario->name);
            }

            return $s;
        });
    }

    /**
     * Lo completa con su resultado y, si se pidió, programa el siguiente
     * contacto como un seguimiento nuevo ligado a este.
     */
    public function marcarHecho(ClienteSeguimiento $seguimiento, ?string $resultado, User $usuario, array $siguiente = []): ?ClienteSeguimiento
    {
        return DB::transaction(function () use ($seguimiento, $resultado, $usuario, $siguiente) {
            $s = ClienteSeguimiento::whereKey($seguimiento->id)->lockForUpdate()->firstOrFail();

            if ($s->abierto()) {
                $antes = $this->foto($s);
                $s->update([
                    'estado' => 'completado',
                    'hecho_en' => now(),
                    'hecho_por' => $usuario->id,
                    'resultado' => $resultado,
                ]);
                Historial::registrar($s, 'completado', $usuario->id, $antes, $this->foto($s), $resultado);
                $this->recordatorios->cancelar($s);
                Avisos::marcarLeidos($s->llaveAgenda(), $s->id);
            }

            $seguimiento->setRawAttributes($s->getAttributes(), true);

            if (empty($siguiente['siguiente_fecha'])) {
                return null;
            }

            // Si se reintenta, no se crea un segundo "siguiente".
            $ya = ClienteSeguimiento::where('anterior_id', $s->id)->first();
            if ($ya) {
                return $ya;
            }

            return $this->programar($s->customer, [
                'tipo' => $siguiente['siguiente_tipo'] ?? $s->tipo,
                'fecha' => $siguiente['siguiente_fecha'],
                'hora' => $siguiente['siguiente_hora'] ?? null,
                'nota' => $siguiente['siguiente_nota'] ?? $s->nota,
                'prioridad' => $s->prioridad,
                'user_id' => $s->user_id,
            ], $usuario, $s->origen, $s->id);
        });
    }

    /**
     * Mueve el seguimiento de fecha (y hora). $hora = false deja la hora
     * como está; null la quita (pendiente del día).
     */
    public function reprogramar(ClienteSeguimiento $seguimiento, string $fecha, ?User $usuario = null, string|null|false $hora = false, ?string $motivo = null): void
    {
        DB::transaction(function () use ($seguimiento, $fecha, $usuario, $hora, $motivo) {
            $s = ClienteSeguimiento::whereKey($seguimiento->id)->lockForUpdate()->firstOrFail();
            $antes = $this->foto($s);

            $cambios = ['fecha' => Carbon::parse($fecha)->toDateString(), 'notificado_en' => null];
            if ($hora !== false) {
                $cambios['hora'] = $hora ?: null;
            }
            // Un seguimiento cerrado que se mueve vuelve a quedar pendiente.
            if (! $s->abierto()) {
                $cambios += ['estado' => 'pendiente', 'hecho_en' => null, 'hecho_por' => null, 'cancelado_en' => null, 'cancelado_por' => null];
            }

            $s->update($cambios);
            [$a, $d] = Historial::diferencias($antes, $this->foto($s));

            if ($d) {
                Historial::registrar($s, 'reprogramado', $usuario?->id, $a, $d, $motivo);
            }

            $this->recordatorios->programar($s);
            Avisos::marcarLeidos($s->llaveAgenda(), $s->id);

            if ($d && $usuario && (int) $s->user_id !== $usuario->id) {
                $this->avisar($s, 'reprogramada', 'Seguimiento reprogramado: '.$s->nombreCliente(), $this->resumen($s).' · lo movió '.$usuario->name.($motivo ? ' ('.$motivo.')' : ''));
            }

            $seguimiento->setRawAttributes($s->getAttributes(), true);
        });
    }

    /** Pasa el seguimiento a otra persona: le aparece a ella y deja de estar a nombre del anterior. */
    public function reasignar(ClienteSeguimiento $seguimiento, int $nuevoId, User $usuario, ?string $motivo = null): void
    {
        DB::transaction(function () use ($seguimiento, $nuevoId, $usuario, $motivo) {
            $s = ClienteSeguimiento::whereKey($seguimiento->id)->lockForUpdate()->firstOrFail();
            $anterior = (int) $s->user_id;

            if ($anterior === $nuevoId) {
                return;
            }

            $s->update(['user_id' => $nuevoId, 'notificado_en' => null]);
            Historial::registrar($s, 'reasignado', $usuario->id, ['user_id' => $anterior], ['user_id' => $nuevoId], $motivo);

            // Los avisos del anterior se cancelan y se programan los del nuevo.
            $this->recordatorios->programar($s);
            Avisos::marcarLeidos($s->llaveAgenda(), $s->id);

            $s->load('responsable');
            $this->avisar($s, 'asignada', 'Te asignaron un seguimiento: '.$s->nombreCliente(), $this->resumen($s).' · lo reasignó '.$usuario->name);

            if ($viejo = User::find($anterior)) {
                $this->avisos->enviar($viejo, 'reasignada', 'Seguimiento reasignado: '.$s->nombreCliente(),
                    'Ahora lo lleva '.($s->responsable?->name ?? 'otra persona').'.', route('commercial.clientes.show', $s->customer_id).'#seguimientos', $s->llaveAgenda(), $usuario->id);
            }

            $seguimiento->setRawAttributes($s->getAttributes(), true);
        });
    }

    /** Cambios que no son fecha ni responsable: tipo, prioridad, nota, estado en proceso, recordatorios. */
    public function actualizar(ClienteSeguimiento $seguimiento, array $data, User $usuario): void
    {
        DB::transaction(function () use ($seguimiento, $data, $usuario) {
            $s = ClienteSeguimiento::whereKey($seguimiento->id)->lockForUpdate()->firstOrFail();
            $antes = $this->foto($s);

            $cambios = array_intersect_key($data, array_flip(['tipo', 'prioridad', 'nota']));
            if (array_key_exists('recordatorios', $data)) {
                $cambios['recordatorios'] = array_map('intval', (array) $data['recordatorios']);
            }
            if (isset($data['estado']) && in_array($data['estado'], ClienteSeguimiento::ABIERTOS, true) && $s->abierto()) {
                $cambios['estado'] = $data['estado'];
            }

            $s->update($cambios);
            [$a, $d] = Historial::diferencias($antes, $this->foto($s));

            if ($d) {
                Historial::registrar($s, isset($d['estado']) && count($d) === 1 ? 'estado' : 'editado', $usuario->id, $a, $d);
                $this->recordatorios->programar($s);
            }

            $seguimiento->setRawAttributes($s->getAttributes(), true);
        });
    }

    /** Se cancela (no se borra): queda en el historial con quién y por qué. */
    public function cancelar(ClienteSeguimiento $seguimiento, ?User $usuario, ?string $motivo = null): void
    {
        DB::transaction(function () use ($seguimiento, $usuario, $motivo) {
            $s = ClienteSeguimiento::whereKey($seguimiento->id)->lockForUpdate()->firstOrFail();

            if (! $s->abierto()) {
                return;
            }

            $antes = $this->foto($s);
            $s->update([
                'estado' => 'cancelado',
                'cancelado_en' => now(),
                'cancelado_por' => $usuario?->id,
                'motivo_cancelacion' => $motivo,
            ]);
            Historial::registrar($s, 'cancelado', $usuario?->id, $antes, $this->foto($s), $motivo);
            $this->recordatorios->cancelar($s);
            Avisos::marcarLeidos($s->llaveAgenda(), $s->id);

            if ($usuario && (int) $s->user_id !== $usuario->id) {
                $this->avisar($s, 'cancelada', 'Seguimiento cancelado: '.$s->nombreCliente(), $this->resumen($s).' · lo canceló '.$usuario->name.($motivo ? ' ('.$motivo.')' : ''));
            }

            $seguimiento->setRawAttributes($s->getAttributes(), true);
        });
    }

    /** Antes "eliminar": ahora se cancela para conservar el historial. */
    public function eliminar(ClienteSeguimiento $seguimiento, ?User $usuario = null): void
    {
        $this->cancelar($seguimiento, $usuario ?? auth()->user(), 'Eliminado desde la ficha del cliente');
    }

    /**
     * Regla explícita al archivar (desactivar) un cliente o prospecto: sus
     * seguimientos abiertos se CANCELAN con ese motivo; el historial queda.
     * Al reactivarlo no se reabren solos: se programan de nuevo si aplica.
     */
    public function cancelarAbiertosDe(Customer $cliente, ?User $usuario, string $motivo): int
    {
        $n = 0;
        foreach ($cliente->seguimientos()->pendientes()->get() as $s) {
            $this->cancelar($s, $usuario, $motivo);
            $n++;
        }

        return $n;
    }

    /**
     * El prospecto ya compró: pasa a cliente. Su historial y los
     * seguimientos que sigan pendientes se conservan tal cual.
     */
    public function convertir(Customer $cliente): void
    {
        $cliente->update(['etapa' => 'cliente']);
    }

    /** Los datos que se guardan en el historial. */
    private function foto(ClienteSeguimiento $s): array
    {
        return [
            'fecha' => $s->fecha?->toDateString(),
            'hora' => $s->horaCorta(),
            'user_id' => (int) $s->user_id,
            'tipo' => $s->tipo,
            'prioridad' => $s->prioridad,
            'estado' => $s->estado,
            'nota' => $s->nota,
            'recordatorios' => $s->recordatorios,
        ];
    }

    private function resumen(ClienteSeguimiento $s): string
    {
        return $s->tipoLabel().' · '.$s->fecha?->format('d/m/Y').($s->horaCorta() ? ' '.$s->horaCorta() : ' (sin hora)').($s->nota ? ' · '.$s->nota : '');
    }

    private function avisar(ClienteSeguimiento $s, string $tipo, string $titulo, string $texto): void
    {
        $s->loadMissing('responsable');
        $this->avisos->enviar($s->responsable, $tipo, $titulo, $texto, route('agenda.index', ['item' => $s->llaveAgenda()]), $s->llaveAgenda());
    }
}

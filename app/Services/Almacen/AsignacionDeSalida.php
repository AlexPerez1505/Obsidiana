<?php

namespace App\Services\Almacen;

use App\Models\OrdenSalida;
use App\Models\OrdenSalidaItem;
use App\Models\User;
use App\Models\VentaBitacora;
use App\Notifications\OrdenSalidaAsignada;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Quién va a preparar el equipo que ya se vendió.
 *
 * Se elige en la orden de salida (o desde la venta) entre las personas que
 * pueden preparar salidas, y a la elegida le llega el aviso en la campana
 * del sistema y por correo, con la liga directa a la orden.
 */
class AsignacionDeSalida
{
    /** Personas activas que pueden preparar salidas, por nombre. */
    public function candidatos(): Collection
    {
        return User::query()
            ->where('status', User::STATUS_APPROVED)
            ->orderBy('name')
            ->get()
            ->filter(fn (User $u) => $u->can('salidas.preparar'))
            ->values();
    }

    /**
     * Asigna (o quita, con null) a quien prepara.
     *
     * @return string mensaje para el usuario
     */
    public function asignar(OrdenSalida $orden, ?int $userId, User $quien): string
    {
        if ($orden->cerrada()) {
            throw ValidationException::withMessages(['user_id' => 'La orden ya se entregó o se canceló: ya no se asigna.']);
        }

        if ($userId === null) {
            if (! $orden->asignada_a) {
                return 'La orden no tenía a nadie asignado.';
            }
            $antes = $orden->asignadaA?->name;
            $orden->update(['asignada_a' => null, 'asignada_por' => $quien->id, 'asignada_en' => now()]);
            $this->bitacora($orden, "{$quien->name} quitó a {$antes} de la preparación de {$orden->folio}");

            return 'Se quitó la asignación.';
        }

        $persona = $this->candidatos()->firstWhere('id', $userId);
        if (! $persona) {
            throw ValidationException::withMessages(['user_id' => 'Esa persona no está activa o no tiene permiso para preparar salidas.']);
        }

        if ((int) $orden->asignada_a === $persona->id) {
            return "{$persona->name} ya estaba asignado a esta orden.";
        }

        $orden->update(['asignada_a' => $persona->id, 'asignada_por' => $quien->id, 'asignada_en' => now()]);
        // "Asignar todo": cada equipo de la orden queda con esa persona.
        $orden->items()->update(['asignado_a' => $persona->id, 'asignado_por' => $quien->id, 'asignado_en' => now()]);
        $this->bitacora($orden, "{$quien->name} asignó a {$persona->name} para preparar {$orden->folio}");
        $this->avisar($orden->fresh(), $persona);

        return "Listo: {$persona->name} prepara {$orden->folio}. Le llegó el aviso en el sistema y a su correo.";
    }

    /**
     * Quién prepara UN equipo de la orden (o nadie, con null). La orden
     * queda con esa persona solo si todos sus equipos la tienen a ella.
     *
     * @return string mensaje para el usuario
     */
    public function asignarItem(OrdenSalidaItem $item, ?int $userId, User $quien): string
    {
        // Ojo: $item->orden es la columna de posición, no la relación.
        $orden = OrdenSalida::findOrFail($item->orden_salida_id);
        if ($orden->cerrada()) {
            throw ValidationException::withMessages(['user_id' => 'La orden ya se entregó o se canceló: ya no se asigna.']);
        }

        if ($userId === null) {
            if (! $item->asignado_a) {
                return 'Ese equipo no tenía a nadie asignado.';
            }
            $antes = $item->asignadoA?->name;
            $item->update(['asignado_a' => null, 'asignado_por' => $quien->id, 'asignado_en' => now()]);
            $this->sincronizarOrden($orden, $quien);
            $this->bitacora($orden, "{$quien->name} quitó a {$antes} de preparar {$item->nombre} ({$orden->folio})");

            return "Se quitó a {$antes} de {$item->nombre}.";
        }

        $persona = $this->candidatos()->firstWhere('id', $userId);
        if (! $persona) {
            throw ValidationException::withMessages(['user_id' => 'Esa persona no está activa o no tiene permiso para preparar salidas.']);
        }

        if ((int) $item->asignado_a === $persona->id) {
            return "{$persona->name} ya prepara {$item->nombre}.";
        }

        $item->update(['asignado_a' => $persona->id, 'asignado_por' => $quien->id, 'asignado_en' => now()]);
        $this->sincronizarOrden($orden, $quien);
        $this->bitacora($orden, "{$quien->name} asignó a {$persona->name} para preparar {$item->nombre} ({$orden->folio})");
        $this->avisar($orden->fresh(), $persona, $item->fresh());

        return "Listo: {$persona->name} prepara {$item->nombre}. Le llegó el aviso en el sistema y a su correo.";
    }

    /**
     * La orden lleva a una persona solo si todos sus equipos la tienen; si
     * están repartidos, la orden queda sin una sola persona (cada equipo
     * dice quién lo prepara).
     */
    private function sincronizarOrden(OrdenSalida $orden, User $quien): void
    {
        $asignados = $orden->items()->pluck('asignado_a')->unique()->values();
        $unico = $asignados->count() === 1 ? $asignados->first() : null;

        if ((int) $orden->asignada_a !== (int) $unico) {
            $orden->update(['asignada_a' => $unico, 'asignada_por' => $quien->id, 'asignada_en' => now()]);
        }
    }

    /**
     * Campana + correo. Va después de responder (no hace esperar a quien
     * asigna) y un correo que falla no tumba nada.
     */
    private function avisar(OrdenSalida $orden, User $persona, ?OrdenSalidaItem $item = null): void
    {
        $orden->loadMissing(['venta.customer', 'items', 'asignadaPor']);

        \Illuminate\Support\defer(function () use ($orden, $persona, $item) {
            try {
                $persona->notify(new OrdenSalidaAsignada($orden, $item));
            } catch (\Throwable $e) {
                Log::warning('[Salidas] No salió el aviso de asignación', ['orden' => $orden->id, 'user_id' => $persona->id, 'error' => $e->getMessage()]);
            }
        });
    }

    private function bitacora(OrdenSalida $orden, string $texto): void
    {
        if ($orden->venta) {
            VentaBitacora::registrar($orden->venta, 'salida_asignada', $texto, ['orden_salida_id' => $orden->id, 'asignada_a' => $orden->asignada_a]);
        }
    }
}

<?php

namespace App\Services;

use App\Models\ClienteSeguimiento;
use App\Notifications\SeguimientoRecordatorio;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Manda los recordatorios de seguimiento que ya tocan.
 *
 * Lo llama el comando programado cada mañana y, como respaldo por si el
 * programador de tareas no está corriendo en el servidor, también se
 * dispara desde el sistema como máximo una vez cada 10 minutos.
 */
class Seguimientos
{
    private const CADA_MINUTOS = 10;

    /** @return int Cuántos avisos se mandaron. */
    public function recordarPendientes(): int
    {
        $enviados = 0;

        $pendientes = ClienteSeguimiento::porAvisar()
            ->with(['customer', 'responsable'])
            ->orderBy('fecha')
            ->get();

        foreach ($pendientes as $seguimiento) {
            $responsable = $seguimiento->responsable;

            if (! $responsable || ! $seguimiento->customer) {
                continue;
            }

            try {
                $responsable->notify(new SeguimientoRecordatorio($seguimiento));
            } catch (\Throwable $e) {
                // Si el correo no sale (servidor de correo caído), la
                // notificación del sistema ya quedó guardada: se registra
                // el problema y se sigue con los demás.
                Log::warning('No se pudo mandar el recordatorio de seguimiento por correo', [
                    'seguimiento_id' => $seguimiento->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $seguimiento->notificado_en = now();
            $seguimiento->save();
            $enviados++;
        }

        return $enviados;
    }

    /** Disparo de respaldo desde el sistema: solo si ya pasó el intervalo. */
    public function recordarSiToca(): void
    {
        if (! Cache::add('seguimientos:ultimo_aviso', now()->timestamp, now()->addMinutes(self::CADA_MINUTOS))) {
            return;
        }

        try {
            $this->recordarPendientes();
        } catch (\Throwable $e) {
            Log::warning('Falló el disparo de respaldo de recordatorios', ['error' => $e->getMessage()]);
        }
    }
}

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
    private const CADA_MINUTOS = 1;

    /**
     * @return int Cuántos avisos se mandaron.
     *
     * Desde la agenda, los avisos de seguimiento salen del mismo motor que
     * todos los recordatorios (App\Services\Agenda\Recordatorios), así no
     * hay dos sistemas mandando el mismo aviso.
     */
    public function recordarPendientes(): int
    {
        return app(\App\Services\Agenda\Recordatorios::class)->despachar();
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

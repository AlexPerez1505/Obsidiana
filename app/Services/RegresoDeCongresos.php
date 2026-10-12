<?php

namespace App\Services;

use App\Models\Congress;
use App\Models\User;
use App\Notifications\CongresoPiezasSinRegresar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Pide cierre de los congresos que ya pasaron y dejaron piezas fuera.
 *
 * Lo que resuelve: una pieza que se va a un congreso queda marcada como
 * que está allá. Si nadie la regresa ni la vende, esa marca se queda para
 * siempre y el inventario empieza a mentir: dice que la pieza está fuera
 * del almacén cuando lleva meses en el anaquel.
 *
 * Por eso el aviso insiste cada semana mientras siga habiendo piezas
 * pendientes, en vez de mandarse una sola vez y olvidarse.
 */
class RegresoDeCongresos
{
    /** Cada cuánto se vuelve a insistir con el mismo congreso. */
    private const INSISTIR_CADA_DIAS = 7;

    /** Cada cuánto puede dispararse el respaldo desde el propio sistema. */
    private const RESPALDO_CADA_MINUTOS = 30;

    /** @return int Cuántos congresos se avisaron. */
    public function avisarPendientes(): int
    {
        $avisados = 0;

        $candidatos = Congress::query()
            ->whereDate('fecha_finalizacion', '<', now()->startOfDay())
            ->where(function ($q) {
                $q->whereNull('aviso_regreso_en')
                    ->orWhere('aviso_regreso_en', '<=', now()->subDays(self::INSISTIR_CADA_DIAS));
            })
            ->get();

        foreach ($candidatos as $congress) {
            $piezas = $congress->unidadesPresentes()->count();

            /*
            | Ya se cerró solo (regresaron o vendieron todo): se limpia la
            | marca para que, si el congreso se reabre o se le vuelven a
            | asignar piezas, el aviso pueda salir otra vez.
            */
            if ($piezas === 0) {
                if ($congress->aviso_regreso_en) {
                    $congress->forceFill(['aviso_regreso_en' => null])->save();
                }

                continue;
            }

            $destinatarios = $this->aQuienAvisar($congress);

            if ($destinatarios->isEmpty()) {
                continue;
            }

            foreach ($destinatarios as $usuario) {
                try {
                    $usuario->notify(new CongresoPiezasSinRegresar($congress, $piezas));
                } catch (\Throwable $e) {
                    // Que falle el aviso de uno no debe tumbar el de los demás.
                    Log::warning('No se pudo avisar de las piezas sin regresar de un congreso', [
                        'congress_id' => $congress->id,
                        'user_id' => $usuario->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $congress->forceFill(['aviso_regreso_en' => now()])->save();
            $avisados++;
        }

        return $avisados;
    }

    /**
     * Se le avisa a quien fue al congreso, porque es quien sabe qué pasó
     * con el equipo. Si nadie quedó asignado, a los administradores: el
     * pendiente no puede quedarse sin dueño.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function aQuienAvisar(Congress $congress)
    {
        $asignados = $congress->notifiedUsers()->get();

        if ($asignados->isNotEmpty()) {
            return $asignados;
        }

        return User::where('is_admin', true)
            ->where('status', User::STATUS_APPROVED)
            ->get();
    }

    /**
     * Disparo de respaldo desde el propio sistema, por si el programador
     * de tareas del servidor no está corriendo. Con candado: como máximo
     * una vez cada media hora, aunque entren mil personas.
     */
    public function avisarSiToca(): void
    {
        if (! Cache::add('congresos:ultimo_aviso_regreso', now()->timestamp, now()->addMinutes(self::RESPALDO_CADA_MINUTOS))) {
            return;
        }

        try {
            $this->avisarPendientes();
        } catch (\Throwable $e) {
            Log::warning('Falló el disparo de respaldo del aviso de congresos', ['error' => $e->getMessage()]);
        }
    }
}

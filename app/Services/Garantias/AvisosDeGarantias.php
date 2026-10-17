<?php

namespace App\Services\Garantias;

use App\Models\Garantia;
use App\Models\GarantiaEvento;
use App\Models\GarantiaReclamacion;
use App\Models\GarantiaSustitucion;
use App\Notifications\AvisoDeGarantia;
use Illuminate\Support\Facades\Notification;

/**
 * Avisos diarios a la campana:
 *  - Garantías que vencen en 60, 30, 15 y 7 días.
 *  - Reclamaciones atrasadas (sin primera respuesta o pasadas de su límite).
 *  - Préstamos que el cliente debió regresar.
 *
 * Cada aviso queda en la bitácora del expediente; así no se repite el
 * mismo aviso aunque el comando corra varias veces el mismo día.
 */
class AvisosDeGarantias
{
    public function __construct(private AtencionDeReclamaciones $atencion) {}

    /** @return array{vencimientos:int, atrasadas:int, prestamos:int} */
    public function enviar(): array
    {
        $gestores = $this->atencion->quienesPueden('garantias.gestionar');
        $enviados = ['vencimientos' => 0, 'atrasadas' => 0, 'prestamos' => 0];

        foreach (config('garantias.avisos_vencimiento', [60, 30, 15, 7]) as $dias) {
            $garantias = Garantia::activas()->with(['customer', 'responsable'])
                ->whereDate('vence', today()->addDays((int) $dias))
                ->get();

            foreach ($garantias as $g) {
                $clave = 'vence-'.$dias;
                if ($this->yaAvisado($g->id, null, $clave)) {
                    continue;
                }

                $destino = $g->responsable ? collect([$g->responsable]) : $gestores;
                Notification::send($destino, new AvisoDeGarantia(
                    'Garantía por vencer en '.$dias.' días',
                    $g->equipoNombre().($g->no_serie ? ' · serie '.$g->no_serie : '').' de '.$g->clienteNombre()
                        .' vence el '.$g->vence->format('d/m/Y').'.',
                    route('garantias.show', $g),
                ));
                $g->registrar('alerta', 'Aviso de vencimiento: faltan '.$dias.' días.', ['clave' => $clave]);
                $enviados['vencimientos']++;
            }
        }

        foreach (GarantiaReclamacion::atrasadas()->with(['responsable', 'garantia'])->get() as $rec) {
            $clave = 'atraso-'.today()->toDateString();
            if ($this->yaAvisado(null, $rec->id, $clave)) {
                continue;
            }

            $destino = $rec->responsable ? collect([$rec->responsable]) : $gestores;
            Notification::send($destino, new AvisoDeGarantia(
                'Reclamación sin avance · '.$rec->folio,
                $rec->equipoNombre().' lleva '.$rec->diasAbierta().' día(s) abierta en "'.$rec->etapaLabel().'".',
                route('garantias.reclamaciones.show', $rec),
            ));
            $rec->registrar('alerta', 'Recordatorio: la reclamación pasó su tiempo de atención.', ['clave' => $clave]);
            $enviados['atrasadas']++;
        }

        $prestamos = GarantiaSustitucion::with('reclamacion.responsable')
            ->where('tipo', 'prestamo')->whereNull('devuelto_en')
            ->whereDate('devolver_antes', '<', today())->get();

        foreach ($prestamos as $p) {
            $clave = 'prestamo-'.$p->id.'-'.today()->toDateString();
            if ($this->yaAvisado(null, $p->reclamacion_id, $clave)) {
                continue;
            }

            $rec = $p->reclamacion;
            Notification::send($rec->responsable ? collect([$rec->responsable]) : $gestores, new AvisoDeGarantia(
                'Préstamo sin regresar · '.$rec->folio,
                $p->equipo.' debía regresar el '.$p->devolver_antes->format('d/m/Y').'.',
                route('garantias.reclamaciones.show', $rec),
            ));
            $rec->registrar('alerta', 'Recordatorio: el equipo de préstamo no ha regresado.', ['clave' => $clave]);
            $enviados['prestamos']++;
        }

        return $enviados;
    }

    private function yaAvisado(?int $garantiaId, ?int $reclamacionId, string $clave): bool
    {
        return GarantiaEvento::where('tipo', 'alerta')
            ->when($garantiaId, fn ($q) => $q->where('garantia_id', $garantiaId))
            ->when($reclamacionId, fn ($q) => $q->where('reclamacion_id', $reclamacionId))
            ->where('datos->clave', $clave)
            ->exists();
    }
}

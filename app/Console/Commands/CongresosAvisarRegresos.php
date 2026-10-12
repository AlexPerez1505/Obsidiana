<?php

namespace App\Console\Commands;

use App\Services\RegresoDeCongresos;
use Illuminate\Console\Command;

/**
 * Pide cierre de los congresos que ya terminaron y siguen con piezas
 * marcadas como que están allá. Está programado cada mañana en
 * routes/console.php.
 */
class CongresosAvisarRegresos extends Command
{
    protected $signature = 'congresos:avisar-regresos';

    protected $description = 'Avisa de los congresos que ya pasaron y tienen piezas sin regresar al almacén';

    public function handle(RegresoDeCongresos $servicio): int
    {
        $n = $servicio->avisarPendientes();

        $this->info($n === 0
            ? 'No hay congresos con piezas sin regresar.'
            : "Se avisó de {$n} congreso(s) con piezas sin regresar.");

        return self::SUCCESS;
    }
}

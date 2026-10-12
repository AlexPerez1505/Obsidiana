<?php

namespace App\Console\Commands;

use App\Services\Seguimientos;
use Illuminate\Console\Command;

/**
 * Manda los recordatorios de seguimiento de clientes y prospectos que
 * tocan hoy (o que quedaron vencidos sin avisar). Está programado cada
 * mañana en routes/console.php.
 */
class SeguimientosRecordar extends Command
{
    protected $signature = 'seguimientos:recordar';

    protected $description = 'Avisa por el sistema y por correo los seguimientos de clientes que tocan hoy';

    public function handle(Seguimientos $seguimientos): int
    {
        $n = $seguimientos->recordarPendientes();

        $this->info($n === 0 ? 'No hay seguimientos por avisar.' : "Se mandaron {$n} recordatorio(s).");

        return self::SUCCESS;
    }
}

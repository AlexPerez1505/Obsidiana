<?php

namespace App\Console\Commands;

use App\Services\Garantias\AvisosDeGarantias;
use Illuminate\Console\Command;

/**
 * Avisos diarios de garantías: vencimientos, reclamaciones atrasadas y
 * préstamos sin regresar. Programado en routes/console.php.
 */
class GarantiasAvisar extends Command
{
    protected $signature = 'garantias:avisar';

    protected $description = 'Avisa de garantías por vencer, reclamaciones atrasadas y préstamos sin regresar';

    public function handle(AvisosDeGarantias $avisos): int
    {
        $n = $avisos->enviar();

        $this->info("Vencimientos: {$n['vencimientos']} · reclamaciones atrasadas: {$n['atrasadas']} · préstamos: {$n['prestamos']}");

        return self::SUCCESS;
    }
}

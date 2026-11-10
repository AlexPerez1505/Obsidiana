<?php

namespace App\Console\Commands;

use App\Services\Promociones\Lanzador;
use Illuminate\Console\Command;

/**
 * Cada minuto: manda las campañas programadas que ya tocan, cierra las que
 * terminaron, marca envíos que se quedaron colgados y libera las reservas
 * de cupón que vencieron sin venta.
 */
class DespacharPromociones extends Command
{
    protected $signature = 'promociones:despachar';

    protected $description = 'Lanza campañas programadas, cierra las terminadas y libera reservas de cupón vencidas.';

    public function handle(Lanzador $lanzador): int
    {
        $r = $lanzador->tareasPeriodicas();

        $this->info("Campañas lanzadas: {$r['lanzadas']} · cerradas: {$r['cerradas']} · envíos colgados: {$r['atascados']} · reservas liberadas: {$r['liberadas']}");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\Garantias\GeneradorDeGarantias;
use Illuminate\Console\Command;

/**
 * Genera o actualiza las garantías de todas las ventas. Sirve para la alta
 * inicial del módulo y como respaldo diario (routes/console.php) por si
 * alguna entrega se registró sin pasar por los eventos de la venta.
 */
class GarantiasSincronizar extends Command
{
    protected $signature = 'garantias:sincronizar';

    protected $description = 'Genera o actualiza las garantías a partir de las ventas';

    public function handle(GeneradorDeGarantias $generador): int
    {
        $t = $generador->sincronizarTodas();

        $this->info("Ventas revisadas: {$t['ventas']} · garantías nuevas: {$t['creadas']} · "
            ."actualizadas: {$t['actualizadas']} · anuladas: {$t['anuladas']}");

        return self::SUCCESS;
    }
}

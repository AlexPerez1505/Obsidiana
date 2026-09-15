<?php

namespace App\Console\Commands;

use App\Jobs\EnviarConfirmacionPromocionJob;
use App\Models\Customer;
use Illuminate\Console\Command;

/**
 * Manda el mensaje de confirmación a los clientes que el asesor ya
 * autorizó pero que todavía no han contestado. Pensado para
 * programarse (ver routes/console.php), pero también se puede correr
 * a mano o dispararse desde el botón del panel de Promociones
 * (PromocionController::enviarConfirmaciones hace lo mismo).
 */
class EnviarConfirmacionesPromocion extends Command
{
    protected $signature = 'app:enviar-confirmaciones-promocion';

    protected $description = 'Manda el mensaje de confirmación de promociones a los clientes autorizados por el asesor que aún no han contestado.';

    public function handle(): int
    {
        $clientes = Customer::query()
            ->whereNotNull('promocion_autorizada_en')
            ->whereNull('promocion_confirmada_en')
            ->whereNull('promocion_revocada_en')
            ->whereDoesntHave('promoConfirmaciones', function ($q) {
                $q->where('mensaje_enviado_en', '>=', now()->subDays(3));
            })
            ->get();

        foreach ($clientes as $cliente) {
            EnviarConfirmacionPromocionJob::dispatch($cliente->id);
        }

        $this->info("Se encolaron {$clientes->count()} mensaje(s) de confirmación.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Notifications;

use App\Models\Congress;
use Illuminate\Notifications\Notification;

/**
 * "El Congreso de Endoscopia terminó y quedan 3 piezas allá": llega a la
 * campana de quien va al congreso (y de los administradores si nadie está
 * asignado).
 *
 * No es informativo nada más: es una petición de cierre. Mientras nadie
 * regrese o venda esas piezas, el inventario dice que están fuera del
 * almacén y quien las busque va a perder el viaje.
 */
class CongresoPiezasSinRegresar extends Notification
{
    public function __construct(
        private Congress $congress,
        private int $piezas,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $dias = (int) now()->startOfDay()->diffInDays($this->congress->fecha_finalizacion->startOfDay());

        return [
            'titulo' => '¿Regresaron las piezas del '.$this->congress->nombre.'?',
            'texto' => $this->piezas.' pieza(s) siguen marcadas como que están en el congreso, que terminó '
                .($dias === 0 ? 'hoy' : "hace {$dias} día(s)")
                .'. Regrésalas al almacén o regístralas como vendidas.',
            'url' => route('inventory.congresos.index', ['congreso' => $this->congress->id]),
            'icono' => 'congreso',
            'congress_id' => $this->congress->id,
            'piezas' => $this->piezas,
        ];
    }
}

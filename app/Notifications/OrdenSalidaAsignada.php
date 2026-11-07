<?php

namespace App\Notifications;

use App\Mail\OrdenSalidaAsignadaMail;
use App\Models\OrdenSalida;
use Illuminate\Notifications\Notification;

/**
 * "Te toca preparar OS-2026-0012 (VEN-2026-0040, Hospital X)."
 * Llega a la campana del sistema y al correo de quien va a preparar.
 */
class OrdenSalidaAsignada extends Notification
{
    public function __construct(public OrdenSalida $orden) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $o = $this->orden;
        $cliente = trim(($o->venta?->customer?->nombre ?? '').' '.($o->venta?->customer?->apellido ?? ''));

        return [
            'titulo' => 'Te toca preparar · '.$o->folio,
            'texto' => ($o->asignadaPor?->name ?? 'Alguien').' te asignó preparar el equipo de '.($o->venta?->folio ?? 'la venta')
                .($cliente ? ' ('.$cliente.')' : '').': '.$o->items->count().' partida(s).',
            'url' => route('inventory.salidas.show', $o),
            'icono' => 'salidas',
            'orden_salida_id' => $o->id,
        ];
    }

    public function toMail(object $notifiable): OrdenSalidaAsignadaMail
    {
        return (new OrdenSalidaAsignadaMail($this->orden, $notifiable->name))->to($notifiable->email);
    }
}

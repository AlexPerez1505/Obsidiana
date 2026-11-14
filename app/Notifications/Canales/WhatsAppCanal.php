<?php

namespace App\Notifications\Canales;

use App\Support\WhatsApp\AvisoInterno;
use App\Support\WhatsApp\MensajePlantilla;
use Illuminate\Notifications\Notification;

/**
 * Canal de notificaciones de Laravel que manda el aviso como plantilla de
 * WhatsApp. La notificación arma el mensaje en toWhatsApp(); si regresa
 * null (no aplica a esa persona), no sale nada.
 */
class WhatsAppCanal
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp') || ! AvisoInterno::puede($notifiable)) {
            return;
        }

        $mensaje = $notification->toWhatsApp($notifiable);

        if ($mensaje instanceof MensajePlantilla) {
            AvisoInterno::enviar($mensaje);
        }
    }
}

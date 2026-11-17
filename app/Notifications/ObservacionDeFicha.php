<?php

namespace App\Notifications;

use App\Models\FichaObservacion;
use App\Services\Agenda\Avisos;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Te dejaron una observación en la ficha técnica X": llega a la campana
 * (y por correo si hay salida configurada) de quien debe corregirla.
 */
class ObservacionDeFicha extends Notification
{
    public function __construct(public FichaObservacion $observacion) {}

    public function via(object $notifiable): array
    {
        return Avisos::hayCorreo() && filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $o = $this->observacion;

        return [
            'titulo' => 'Observación en la ficha · '.($o->ficha?->titulo ?? 'ficha técnica'),
            'texto' => ($o->autor?->name ?? 'Alguien').': '.mb_strimwidth($o->texto, 0, 160, '…'),
            'url' => $this->url(),
            'icono' => 'ficha',
            'observacion_id' => $o->id,
            'ficha_id' => $o->ficha_tecnica_id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $o = $this->observacion;

        return (new MailMessage)
            ->subject('Observación en la ficha técnica '.($o->ficha?->titulo ?? ''))
            ->greeting('Hola '.($notifiable->name ?? ''))
            ->line(($o->autor?->name ?? 'Alguien').' dejó una observación en la ficha técnica «'.($o->ficha?->titulo ?? '').'»:')
            ->line($o->texto)
            ->action('Ver la ficha', $this->url());
    }

    /** La lista de fichas abre sola las observaciones de esta ficha. */
    private function url(): string
    {
        return route('inventory.fichas.index', ['observaciones' => $this->observacion->ficha_tecnica_id]);
    }
}

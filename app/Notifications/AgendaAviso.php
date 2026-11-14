<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso interno de la agenda (campana del sistema y, si el usuario lo
 * activó, correo). Nunca se manda a clientes.
 *
 * No va a la cola: se manda desde el comando programado o desde la acción
 * del usuario, y si el correo falla se registra sin tirar el proceso
 * (ver App\Services\Agenda\Avisos).
 */
class AgendaAviso extends Notification
{
    public function __construct(
        public string $tipo,
        public string $titulo,
        public string $texto,
        public string $url,
        public ?string $item = null,
        public bool $porCorreo = false,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->porCorreo && filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'titulo' => $this->titulo,
            'texto' => $this->texto,
            'url' => $this->url,
            'icono' => 'agenda',
            'agenda_tipo' => $this->tipo,
            'agenda_item' => $this->item,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->titulo)
            ->greeting('Hola '.($notifiable->name ?? ''))
            ->line($this->texto)
            ->action('Abrir en la agenda', $this->url)
            ->line('Este aviso es interno; no se envió nada a clientes.');
    }
}

<?php

namespace App\Notifications;

use App\Notifications\Canales\WhatsAppCanal;
use App\Support\WhatsApp\AvisoInterno;
use App\Support\WhatsApp\MensajePlantilla;
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
        return $this->porCorreo && filled($notifiable->email) ? ['database', 'mail', WhatsAppCanal::class] : ['database', WhatsAppCanal::class];
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

    /**
     * Un seguimiento de cliente sale con su plantilla (tipo, cliente y
     * fecha); lo demás de la agenda, con la de recordatorio.
     */
    public function toWhatsApp(object $notifiable): ?MensajePlantilla
    {
        $ref = 'agenda:'.$this->tipo.':'.($this->item ?? '-');

        if ($this->item && str_starts_with($this->item, 's-')
            && in_array($this->tipo, ['recordatorio', 'vencido', 'asignada', 'reasignada', 'reprogramada'], true)
            && ($s = \App\Models\ClienteSeguimiento::with('customer')->find((int) substr($this->item, 2)))) {
            $cliente = trim(($s->customer->nombre ?? '').' '.($s->customer->apellido ?? '')) ?: 'Cliente';
            $cuando = trim(($s->fecha?->format('d/m/Y') ?? '').' '.substr((string) $s->hora, 0, 5));

            return AvisoInterno::mensaje($notifiable, 'seguimiento_cliente', [$s->tipoLabel(), $cliente, $cuando ?: 'sin fecha'], $this->url, $ref);
        }

        return AvisoInterno::mensaje($notifiable, 'agenda_recordatorio', [rtrim($this->titulo, ' .'), rtrim($this->texto, ' .')], $this->url, $ref);
    }
}

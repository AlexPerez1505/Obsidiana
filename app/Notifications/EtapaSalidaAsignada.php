<?php

namespace App\Notifications;

use App\Models\OrdenSalidaEtapa;
use App\Notifications\Canales\WhatsAppCanal;
use App\Services\Agenda\Avisos;
use App\Support\WhatsApp\AvisoInterno;
use App\Support\WhatsApp\MensajePlantilla;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Te asignaron Ingeniería de OS-2026-0012": a la campana, al correo (si
 * hay salida configurada) y por WhatsApp con la plantilla de órdenes de
 * salida.
 */
class EtapaSalidaAsignada extends Notification
{
    public function __construct(public OrdenSalidaEtapa $etapa) {}

    public function via(object $notifiable): array
    {
        $canales = ['database', WhatsAppCanal::class];
        if (Avisos::hayCorreo() && filled($notifiable->email)) {
            $canales[] = 'mail';
        }

        return $canales;
    }

    public function toDatabase(object $notifiable): array
    {
        $e = $this->etapa;
        $o = $e->orden;

        return [
            'titulo' => 'Te toca '.$e->nombre().' · '.$o->folio,
            'texto' => ($e->asignadaPor?->name ?? 'Alguien').' te asignó '.$e->nombre().' de '.($o->venta?->folio ?? 'la venta')
                .($this->cliente() ? ' ('.$this->cliente().')' : '').'. '.OrdenSalidaEtapa::DESCRIPCION[$e->etapa],
            'url' => $this->url(),
            'icono' => 'salidas',
            'orden_salida_id' => $o->id,
            'etapa' => $e->etapa,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $e = $this->etapa;

        return (new MailMessage)
            ->subject('Te toca '.$e->nombre().' · '.$e->orden->folio)
            ->greeting('Hola '.($notifiable->name ?? ''))
            ->line(($e->asignadaPor?->name ?? 'Alguien').' te asignó la etapa «'.$e->nombre().'» de la orden '.$e->orden->folio.' ('.($e->orden->venta?->folio ?? '').').')
            ->line(OrdenSalidaEtapa::DESCRIPCION[$e->etapa])
            ->action('Abrir la etapa', $this->url());
    }

    public function toWhatsApp(object $notifiable): ?MensajePlantilla
    {
        $e = $this->etapa;

        return AvisoInterno::mensaje($notifiable, 'orden_salida_asignada',
            [$e->asignadaPor?->name ?? 'Alguien', $e->orden->folio.' ('.$e->nombre().')', $e->orden->venta?->folio ?? 'sin folio'],
            $this->url(), 'salida-etapa:'.$e->id);
    }

    private function url(): string
    {
        return route('inventory.salidas.etapas.show', [$this->etapa->orden_salida_id, $this->etapa->etapa]);
    }

    private function cliente(): string
    {
        $c = $this->etapa->orden->venta?->customer;

        return trim(($c->nombre ?? '').' '.($c->apellido ?? ''));
    }
}

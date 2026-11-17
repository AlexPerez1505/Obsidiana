<?php

namespace App\Notifications;

use App\Models\OrdenSalidaEtapa;
use App\Models\User;
use App\Services\Agenda\Avisos;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Avance de una orden de salida: comenzó, terminó o se corrigió una etapa
 * (al asesor de la venta y a los administradores), o "ya te toca" a quien
 * tiene la siguiente.
 */
class EtapaSalidaActualizada extends Notification
{
    public function __construct(public OrdenSalidaEtapa $etapa, public string $accion, public User $quien) {}

    public function via(object $notifiable): array
    {
        return Avisos::hayCorreo() && filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'titulo' => $this->titulo(),
            'texto' => $this->texto(),
            'url' => $this->url(),
            'icono' => 'salidas',
            'orden_salida_id' => $this->etapa->orden_salida_id,
            'etapa' => $this->etapa->etapa,
            'accion' => $this->accion,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->titulo())
            ->greeting('Hola '.($notifiable->name ?? ''))
            ->line($this->texto())
            ->action($this->accion === 'te_toca' ? 'Abrir la etapa' : 'Ver en qué va', $this->url());
    }

    private function titulo(): string
    {
        $e = $this->etapa;
        $folio = $e->orden->folio;

        return match ($this->accion) {
            'iniciada' => "Comenzó {$e->nombre()} · {$folio}",
            'terminada' => $e->etapa === OrdenSalidaEtapa::ENTREGA ? "Entregado · {$folio}" : "Terminó {$e->nombre()} · {$folio}",
            'corregida' => "Se corrigió {$e->nombre()} · {$folio}",
            'te_toca' => "Ya te toca {$e->nombre()} · {$folio}",
            default => "Avance de {$folio}",
        };
    }

    private function texto(): string
    {
        $e = $this->etapa;
        $venta = $e->orden->venta?->folio ?? 'la venta';
        $c = $e->orden->venta?->customer;
        $cliente = trim(($c->nombre ?? '').' '.($c->apellido ?? ''));
        $de = $venta.($cliente ? " ({$cliente})" : '');

        return match ($this->accion) {
            'iniciada' => "{$this->quien->name} comenzó {$e->nombre()} del equipo de {$de}.",
            'terminada' => $e->etapa === OrdenSalidaEtapa::ENTREGA
                ? "{$this->quien->name} entregó el equipo de {$de}."
                : "{$this->quien->name} terminó {$e->nombre()} del equipo de {$de}.",
            'corregida' => "{$this->quien->name} corrigió {$e->nombre()} del equipo de {$de}.",
            'te_toca' => "{$this->quien->name} terminó la etapa anterior: ya puedes hacer {$e->nombre()} del equipo de {$de}.",
            default => "Hubo un avance en {$de}.",
        };
    }

    private function url(): string
    {
        return $this->accion === 'te_toca'
            ? route('inventory.salidas.etapas.show', [$this->etapa->orden_salida_id, $this->etapa->etapa])
            : route('salidas.seguimiento', $this->etapa->orden_salida_id);
    }
}

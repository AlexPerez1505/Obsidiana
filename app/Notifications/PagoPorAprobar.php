<?php

namespace App\Notifications;

use App\Mail\PagoPorAprobarMail;
use App\Models\CobroSolicitud;
use Illuminate\Notifications\Notification;

/**
 * "Juan subió un pago de $4,000 de VEN-2026-0007: revísalo."
 * Llega a la campana del sistema y al correo de cada administrador o
 * persona con NIP que puede aprobarlo, con el enlace directo.
 */
class PagoPorAprobar extends Notification
{
    public function __construct(public CobroSolicitud $solicitud) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $s = $this->solicitud;

        return [
            'titulo' => 'Pago por aprobar · '.$s->venta?->folio,
            'texto' => ($s->subidoPor?->name ?? 'Un vendedor').' subió $'.number_format((float) $s->monto, 2).' ('.$s->metodoLabel().'). Revísalo cuando se vea reflejado.',
            'url' => route('commercial.ventas.cobros.index', $s->venta_id).'#pagos-por-aprobar',
            'icono' => 'cobranza',
            'solicitud_id' => $s->id,
        ];
    }

    public function toMail(object $notifiable): PagoPorAprobarMail
    {
        return (new PagoPorAprobarMail($this->solicitud, $notifiable->name))->to($notifiable->email);
    }
}

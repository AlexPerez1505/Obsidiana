<?php

namespace App\Notifications;

use App\Mail\SeguimientoRecordatorioMail;
use App\Models\ClienteSeguimiento;
use Illuminate\Notifications\Notification;

/**
 * "Hoy toca hacerle cotización a Juan Pérez": llega a la campana del
 * sistema y al correo del responsable.
 *
 * No va a la cola a propósito: el aviso se manda desde el comando
 * programado (o desde el disparo de respaldo), y si el correo falla se
 * registra sin tirar el proceso; la notificación del sistema sí queda.
 */
class SeguimientoRecordatorio extends Notification
{
    public function __construct(public ClienteSeguimiento $seguimiento) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $s = $this->seguimiento;
        $cliente = trim(($s->customer->nombre ?? '').' '.($s->customer->apellido ?? '')) ?: 'Cliente';

        return [
            'titulo' => $s->tipoLabel().': '.$cliente,
            'texto' => ($s->vencido() ? 'Seguimiento vencido' : 'Seguimiento para hoy')
                .($s->nota ? ' · '.$s->nota : ''),
            'url' => route('commercial.clientes.show', $s->customer_id).'#seguimientos',
            'icono' => 'seguimiento',
            'seguimiento_id' => $s->id,
        ];
    }

    public function toMail(object $notifiable): SeguimientoRecordatorioMail
    {
        return (new SeguimientoRecordatorioMail($this->seguimiento, $notifiable->name))
            ->to($notifiable->email);
    }
}

<?php

namespace App\Notifications;

use App\Mail\JustificarAsistenciaMail;
use App\Models\AsistenciaJustificacion;
use Illuminate\Notifications\Notification;

/**
 * "El jueves 30/10 entraste 12:58 y no registraste salida: justifícalo."
 * Llega a la campana del sistema y al correo de la persona, con el enlace
 * para responder.
 *
 * No va a la cola: se manda en el momento en que RH lo pide. Si el correo
 * falla, quien lo manda lo registra sin tumbar la petición; la
 * notificación del sistema sí queda.
 */
class JustificarAsistencia extends Notification
{
    public function __construct(public AsistenciaJustificacion $justificacion) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $j = $this->justificacion;

        return [
            'titulo' => 'Justifica tu asistencia del '.$j->work_date->translatedFormat('l d/m'),
            'texto' => $j->detalle.'. Escribe el motivo para que RH lo revise.',
            'url' => route('asistencia.justificar', $j),
            'icono' => 'asistencia',
            'justificacion_id' => $j->id,
        ];
    }

    public function toMail(object $notifiable): JustificarAsistenciaMail
    {
        return (new JustificarAsistenciaMail($this->justificacion, $notifiable->name))
            ->to($notifiable->email);
    }
}

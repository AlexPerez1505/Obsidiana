<?php

namespace App\Notifications;

use App\Models\AsistenciaJustificacion;
use Illuminate\Notifications\Notification;

/**
 * Aviso de vuelta en la campana: la persona respondió (para RH) o RH ya
 * revisó su justificación (para la persona). Solo en el sistema: no hace
 * falta llenar el correo con cada paso.
 */
class JustificacionRespondida extends Notification
{
    public function __construct(public AsistenciaJustificacion $justificacion, public string $titulo, public string $texto, public string $url) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'titulo' => $this->titulo,
            'texto' => $this->texto,
            'url' => $this->url,
            'icono' => 'asistencia',
            'justificacion_id' => $this->justificacion->id,
        ];
    }
}

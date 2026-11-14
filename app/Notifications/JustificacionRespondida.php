<?php

namespace App\Notifications;

use App\Notifications\Canales\WhatsAppCanal;
use App\Support\WhatsApp\AvisoInterno;
use App\Support\WhatsApp\MensajePlantilla;
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
        return ['database', WhatsAppCanal::class];
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

    /**
     * Solo la respuesta de RH al empleado tiene plantilla; el aviso a RH de
     * que alguien ya justificó se queda en la campana.
     */
    public function toWhatsApp(object $notifiable): ?MensajePlantilla
    {
        if ((int) $notifiable->id !== (int) $this->justificacion->user_id) {
            return null;
        }

        $estado = preg_match('/^RH (\S+)/u', $this->titulo, $m)
            ? (['aceptó' => 'aceptada', 'rechazó' => 'rechazada'][$m[1]] ?? $m[1])
            : 'revisada';

        return AvisoInterno::mensaje($notifiable, 'asistencia_respuesta',
            [$estado.(filled($this->texto) ? ' ('.rtrim($this->texto, ' .').')' : '')], $this->url, 'justificacion:'.$this->justificacion->id);
    }
}

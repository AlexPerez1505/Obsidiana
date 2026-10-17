<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Aviso de la campana para el módulo de garantías: reclamación nueva,
 * gasto por aprobar, garantía por vencer o caso sin avance.
 */
class AvisoDeGarantia extends Notification
{
    public function __construct(
        private string $titulo,
        private string $texto,
        private string $url,
    ) {}

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
            'icono' => 'garantia',
        ];
    }
}

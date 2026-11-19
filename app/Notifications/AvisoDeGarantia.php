<?php

namespace App\Notifications;

use App\Notifications\Canales\WhatsAppCanal;
use App\Support\WhatsApp\AvisoInterno;
use App\Support\WhatsApp\MensajePlantilla;
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
        return ['database', WhatsAppCanal::class];
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

    /**
     * Los títulos traen el folio al final ("Nueva reclamación · GAR-..."):
     * el folio va como {{2}} y el motivo, con el detalle, como {{3}}.
     */
    public function toWhatsApp(object $notifiable): ?MensajePlantilla
    {
        [$motivo, $folio] = str_contains($this->titulo, ' · ')
            ? array_map('trim', explode(' · ', $this->titulo, 2))
            : [$this->titulo, null];

        return AvisoInterno::mensaje($notifiable, 'garantia_aviso',
            [$folio ?? $motivo, ($folio ? $motivo.'. ' : '').rtrim($this->texto, ' .')], $this->url, 'garantia');
    }
}

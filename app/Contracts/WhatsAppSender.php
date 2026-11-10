<?php

namespace App\Contracts;

use App\Support\WhatsApp\MensajePlantilla;
use App\Support\WhatsApp\WhatsAppEnvioResultado;

/**
 * Quien manda los mensajes de WhatsApp. Se elige con WHATSAPP_DRIVER:
 *   log  → LogWhatsAppSender: no manda nada, lo registra como simulado.
 *   meta → MetaCloudSender: API oficial de WhatsApp Business (Cloud API).
 *
 * Las promociones solo se mandan como plantilla aprobada: fuera de la
 * ventana de 24 h, WhatsApp no permite otro tipo de mensaje.
 */
interface WhatsAppSender
{
    public function enviarPlantilla(MensajePlantilla $mensaje): WhatsAppEnvioResultado;

    /** true si de verdad sale a WhatsApp. */
    public function esReal(): bool;
}

<?php

namespace App\Contracts;

use App\Support\WhatsApp\WhatsAppEnvioResultado;

/**
 * Lo que necesita cualquier proveedor de WhatsApp para conectarse al
 * sistema: mandar un mensaje a un teléfono y decir si se pudo o no.
 *
 * Hoy solo existe LogWhatsAppSender (no manda nada de verdad, solo lo
 * registra). El día que se conecte una cuenta real de WhatsApp Business
 * API (Meta Cloud API, Twilio, 360dialog...), se agrega una clase nueva
 * que implemente esto y se cambia el "driver" en config/services.php:
 * el resto del flujo (confirmaciones, campañas, jobs) no se toca.
 */
interface WhatsAppSender
{
    /**
     * Manda un mensaje. $contexto sirve para pasar datos propios del
     * proveedor si algún día hacen falta (ej. id de plantilla aprobada),
     * sin tener que cambiar la firma del método para todos.
     */
    public function enviar(string $telefono, string $mensaje, array $contexto = []): WhatsAppEnvioResultado;
}

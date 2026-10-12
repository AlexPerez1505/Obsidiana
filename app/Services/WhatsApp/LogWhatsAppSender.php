<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppSender;
use App\Support\WhatsApp\WhatsAppEnvioResultado;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Implementación por defecto, mientras no haya una cuenta real de
 * WhatsApp Business API conectada.
 *
 * No manda nada de verdad: solo lo escribe en el log de Laravel y
 * regresa éxito con un id inventado, para que todo el flujo de
 * confirmaciones y campañas se pueda probar y usar de principio a fin
 * (con envío manual real por fuera) sin quedar bloqueado esperando la
 * API. El día que se conecte una cuenta real, se implementa otra clase
 * (ej. MetaWhatsAppSender) y se cambia el driver en config/services.php.
 */
class LogWhatsAppSender implements WhatsAppSender
{
    public function enviar(string $telefono, string $mensaje, array $contexto = []): WhatsAppEnvioResultado
    {
        $messageId = 'log-'.Str::uuid();

        Log::info('[WhatsApp:log] Mensaje simulado (no se envió de verdad)', [
            'telefono' => $telefono,
            'mensaje' => $mensaje,
            'contexto' => $contexto,
            'message_id' => $messageId,
        ]);

        return WhatsAppEnvioResultado::exitoso($messageId);
    }
}

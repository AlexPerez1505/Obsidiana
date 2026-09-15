<?php

namespace App\Support\WhatsApp;

/**
 * Lo que regresa cualquier implementación de WhatsAppSender al intentar
 * mandar un mensaje. Es la misma forma sin importar el proveedor
 * (Meta Cloud API, Twilio, 360dialog...), para que el resto del sistema
 * (jobs, controladores) no dependa de los detalles de ninguno.
 */
class WhatsAppEnvioResultado
{
    private function __construct(
        public readonly bool $exito,
        public readonly ?string $messageId,
        public readonly ?string $error,
    ) {
    }

    public static function exitoso(?string $messageId): self
    {
        return new self(true, $messageId, null);
    }

    public static function fallido(string $error): self
    {
        return new self(false, null, $error);
    }
}

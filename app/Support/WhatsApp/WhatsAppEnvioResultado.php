<?php

namespace App\Support\WhatsApp;

/**
 * Lo que regresa cualquier proveedor al intentar mandar un mensaje.
 *
 * transitorio = vale la pena reintentar (red, límite de ritmo, 5xx).
 * simulado    = no salió a WhatsApp (modo de pruebas): no cuenta como enviado.
 */
class WhatsAppEnvioResultado
{
    private function __construct(
        public readonly bool $exito,
        public readonly ?string $messageId,
        public readonly ?string $error,
        public readonly ?string $codigo = null,
        public readonly bool $transitorio = false,
        public readonly bool $simulado = false,
    ) {
    }

    public static function exitoso(string $messageId): self
    {
        return new self(true, $messageId, null);
    }

    public static function simulado(string $messageId): self
    {
        return new self(true, $messageId, null, null, false, true);
    }

    public static function fallido(string $error, ?string $codigo = null, bool $transitorio = false): self
    {
        return new self(false, null, $error, $codigo, $transitorio);
    }
}

<?php

namespace App\Services\WhatsApp;

/**
 * Qué tan lista está la integración: qué falta exactamente para mandar de
 * verdad y para recibir los avisos de entrega y lectura.
 */
class EstadoWhatsApp
{
    public function real(): bool
    {
        return config('promociones.whatsapp.driver') === 'meta';
    }

    /** @return array<int, array{clave: string, ok: bool, texto: string}> */
    public function revision(): array
    {
        $c = config('promociones.whatsapp');

        return [
            ['clave' => 'WHATSAPP_DRIVER', 'ok' => $this->real(), 'texto' => $this->real()
                ? 'Envío real activado (meta).'
                : 'Modo de pruebas (log): nada sale a WhatsApp; los envíos quedan como "simulado".'],
            ['clave' => 'WHATSAPP_TOKEN', 'ok' => filled($c['token']), 'texto' => 'Token permanente de un usuario del sistema con permiso whatsapp_business_messaging.'],
            ['clave' => 'WHATSAPP_PHONE_NUMBER_ID', 'ok' => filled($c['phone_number_id']), 'texto' => 'Id del número de WhatsApp Business que envía.'],
            ['clave' => 'WHATSAPP_BUSINESS_ACCOUNT_ID', 'ok' => filled($c['business_account_id']), 'texto' => 'Id de la cuenta de WhatsApp Business (WABA), para revisar las plantillas.'],
            ['clave' => 'WHATSAPP_APP_SECRET', 'ok' => filled($c['app_secret']), 'texto' => 'Secreto de la app de Meta: valida la firma X-Hub-Signature-256 de cada aviso.'],
            ['clave' => 'WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'ok' => filled($c['verify_token']), 'texto' => 'Texto que inventas y escribes igual en Meta al suscribir el webhook.'],
            ['clave' => 'WHATSAPP_PLANTILLA_PROMO', 'ok' => filled($c['plantilla']), 'texto' => 'Nombre de la plantilla de marketing aprobada ('.($c['plantilla'] ?: 'sin definir').').'],
            ['clave' => 'PROMO_NUMEROS_PRUEBA', 'ok' => count(config('promociones.numeros_prueba')) > 0, 'texto' => 'Números autorizados para recibir pruebas.'],
        ];
    }

    /** ¿Se puede mandar de verdad? */
    public function listoParaEnviar(): bool
    {
        $c = config('promociones.whatsapp');

        return $this->real() && filled($c['token']) && filled($c['phone_number_id']) && filled($c['plantilla']);
    }

    /** @return array<int, string> */
    public function faltantes(): array
    {
        return array_values(array_map(fn ($r) => $r['clave'], array_filter($this->revision(), fn ($r) => ! $r['ok'])));
    }
}

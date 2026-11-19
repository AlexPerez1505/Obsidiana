<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppSender;
use App\Support\WhatsApp\MensajePlantilla;
use App\Support\WhatsApp\WhatsAppEnvioResultado;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * API oficial de WhatsApp Business (Cloud API de Meta).
 *
 * POST https://graph.facebook.com/{version}/{phone_number_id}/messages
 * con un mensaje tipo "template". El token nunca se escribe en el log.
 */
class MetaCloudSender implements WhatsAppSender
{
    /**
     * Errores de Meta que vale la pena reintentar más tarde: límites de
     * ritmo, "algo salió mal" y servicio no disponible. Los demás (número
     * sin WhatsApp, plantilla mal armada, el cliente bloqueó marketing…)
     * no se arreglan reintentando.
     */
    private const TRANSITORIOS = ['4', '80007', '130429', '131000', '131016', '131048', '131056', '133004'];

    public function enviarPlantilla(MensajePlantilla $m): WhatsAppEnvioResultado
    {
        $cfg = config('promociones.whatsapp');

        if (blank($cfg['token']) || blank($cfg['phone_number_id'])) {
            return WhatsAppEnvioResultado::fallido('WhatsApp no está configurado: falta WHATSAPP_TOKEN o WHATSAPP_PHONE_NUMBER_ID.', 'config');
        }

        $componentes = [];

        if ($m->imagenUrl) {
            $componentes[] = ['type' => 'header', 'parameters' => [['type' => 'image', 'image' => ['link' => $m->imagenUrl]]]];
        }

        if ($m->variables) {
            $componentes[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn ($v) => ['type' => 'text', 'text' => MensajePlantilla::limpiarVariable($v)],
                    array_values($m->variables)
                ),
            ];
        }

        if ($m->botonUrlSufijo) {
            $componentes[] = [
                'type' => 'button', 'sub_type' => 'url', 'index' => '0',
                'parameters' => [['type' => 'text', 'text' => $m->botonUrlSufijo]],
            ];
        }

        if ($m->botonBaja) {
            $componentes[] = [
                'type' => 'button', 'sub_type' => 'quick_reply', 'index' => '1',
                'parameters' => [['type' => 'payload', 'payload' => 'PROMO_BAJA']],
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $m->telefono,
            'type' => 'template',
            'template' => [
                'name' => $m->plantilla,
                'language' => ['code' => $m->idioma],
                'components' => $componentes,
            ],
        ];

        if ($m->referencia) {
            // Meta lo regresa tal cual en los avisos de estado.
            $payload['biz_opaque_callback_data'] = $m->referencia;
        }

        try {
            $r = Http::withToken($cfg['token'])
                ->acceptJson()
                ->timeout((int) ($cfg['timeout'] ?? 15))
                ->post($this->url($cfg['phone_number_id'].'/messages'), $payload);
        } catch (ConnectionException) {
            Log::warning('[WhatsApp] Sin conexión con Meta', ['telefono' => Telefono::ocultar($m->telefono)]);

            return WhatsAppEnvioResultado::fallido('No hubo conexión con WhatsApp. Se reintentará.', 'red', true);
        }

        if ($r->successful() && $r->json('messages.0.id')) {
            return WhatsAppEnvioResultado::exitoso((string) $r->json('messages.0.id'));
        }

        $codigo = (string) ($r->json('error.code') ?? $r->status());
        $detalle = (string) ($r->json('error.error_data.details') ?? $r->json('error.message') ?? 'Error desconocido de WhatsApp');
        $transitorio = $r->status() === 429 || $r->serverError() || in_array($codigo, self::TRANSITORIOS, true);

        Log::warning('[WhatsApp] Meta rechazó el mensaje', [
            'telefono' => Telefono::ocultar($m->telefono),
            'plantilla' => $m->plantilla,
            'http' => $r->status(),
            'codigo' => $codigo,
            'detalle' => mb_substr($detalle, 0, 300),
        ]);

        return WhatsAppEnvioResultado::fallido(mb_substr($detalle, 0, 500), $codigo, $transitorio);
    }

    public function esReal(): bool
    {
        return true;
    }

    /**
     * Las plantillas de la cuenta (nombre, estado, categoría, idioma), para
     * la pantalla de configuración. null si no se pudo consultar.
     */
    public function plantillas(): ?array
    {
        $cfg = config('promociones.whatsapp');
        if (blank($cfg['token']) || blank($cfg['business_account_id'])) {
            return null;
        }

        try {
            $r = Http::withToken($cfg['token'])->acceptJson()->timeout(10)
                ->get($this->url($cfg['business_account_id'].'/message_templates'), [
                    'fields' => 'name,status,category,language',
                    'limit' => 100,
                ]);
        } catch (ConnectionException) {
            return null;
        }

        return $r->successful() ? (array) $r->json('data', []) : null;
    }

    private function url(string $ruta): string
    {
        return 'https://graph.facebook.com/'.config('promociones.whatsapp.api_version').'/'.$ruta;
    }
}

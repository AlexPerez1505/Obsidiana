<?php

namespace App\Http\Controllers\Promociones;

use App\Http\Controllers\Controller;
use App\Services\Promociones\WebhookWhatsApp;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Webhook de la Cloud API de WhatsApp.
 *   GET  → verificación al suscribirlo en Meta (hub.verify_token / hub.challenge).
 *   POST → avisos firmados con X-Hub-Signature-256 (HMAC-SHA256 con el secreto de la app).
 * Sin firma válida no se procesa nada.
 */
class WhatsAppWebhookController extends Controller
{
    public function verificar(Request $request): Response
    {
        $token = (string) config('promociones.whatsapp.verify_token');

        // PHP convierte "hub.mode" en "hub_mode" al leer la consulta.
        if ($token !== ''
            && $request->query('hub_mode') === 'subscribe'
            && hash_equals($token, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }

        return response('Token de verificación inválido', 403);
    }

    public function recibir(Request $request, WebhookWhatsApp $webhook): Response
    {
        $cuerpo = $request->getContent();

        if (! $webhook->firmaValida($cuerpo, $request->header('X-Hub-Signature-256'))) {
            Log::warning('[WhatsApp] Webhook rechazado: firma inválida o WHATSAPP_APP_SECRET sin configurar', ['ip' => $request->ip()]);

            return response('Firma inválida', 403);
        }

        $payload = json_decode($cuerpo, true);
        if (! is_array($payload)) {
            return response('JSON inválido', 400);
        }

        $webhook->procesar($payload);

        // Meta solo necesita un 200 rápido; si no lo recibe, reintenta.
        return response('OK', 200);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private ?string $token;
    private ?string $phoneNumberId;
    private string $apiVersion;

    public function __construct()
    {
        $this->token = config('services.whatsapp.token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->apiVersion = config('services.whatsapp.api_version', 'v20.0');
    }

    /**
     * Indica si las credenciales de WhatsApp ya fueron configuradas en .env.
     */
    public function isConfigured(): bool
    {
        return filled($this->token) && filled($this->phoneNumberId);
    }

    private function baseUrl(string $path): string
    {
        return "https://graph.facebook.com/{$this->apiVersion}/{$path}";
    }

    /**
     * Sube una imagen al servidor de medios de WhatsApp y devuelve el media_id.
     */
    public function uploadMedia(string $absolutePath, string $mimeType): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $response = Http::withToken($this->token)
            ->attach('file', file_get_contents($absolutePath), basename($absolutePath))
            ->post($this->baseUrl("{$this->phoneNumberId}/media"), [
                'messaging_product' => 'whatsapp',
                'type' => $mimeType,
            ]);

        if ($response->failed()) {
            Log::warning('WhatsApp uploadMedia falló', ['response' => $response->json()]);

            return null;
        }

        return $response->json('id');
    }

    /**
     * Envía una plantilla de imagen + texto a un número de WhatsApp.
     *
     * @return array{success: bool, wamid: ?string, error: ?string}
     */
    public function sendImageTemplate(string $to, string $templateName, string $languageCode, ?string $mediaId, string $bodyText): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'wamid' => null,
                'error' => 'Credenciales de WhatsApp no configuradas en .env',
            ];
        }

        $components = [];

        if ($mediaId) {
            $components[] = [
                'type' => 'header',
                'parameters' => [
                    ['type' => 'image', 'image' => ['id' => $mediaId]],
                ],
            ];
        }

        $components[] = [
            'type' => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => $bodyText],
            ],
        ];

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components,
            ],
        ];

        $response = Http::withToken($this->token)
            ->post($this->baseUrl("{$this->phoneNumberId}/messages"), $payload);

        if ($response->failed()) {
            $error = $response->json('error.message') ?? 'Error desconocido al enviar el mensaje.';
            Log::warning('WhatsApp sendImageTemplate falló', ['to' => $to, 'response' => $response->json()]);

            return ['success' => false, 'wamid' => null, 'error' => $error];
        }

        $wamid = $response->json('messages.0.id');

        return ['success' => true, 'wamid' => $wamid, 'error' => null];
    }

    /**
     * Normaliza un teléfono mexicano a formato E.164 sin '+' (lo que espera la Cloud API).
     */
    public function normalizePhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === null || strlen($digits) < 10) {
            return null;
        }

        if (strlen($digits) === 10) {
            return '52'.$digits;
        }

        return $digits;
    }
}

<?php

namespace App\Services\Mensajeria;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Las llamadas a la Cloud API de Meta que usa Mensajería: mandar mensajes,
 * subir y bajar archivos, plantillas y números. Usa la misma configuración
 * que las campañas (config/promociones.php); el token nunca sale del
 * servidor ni se escribe en el log.
 */
class ApiWhatsApp
{
    /** Errores que se arreglan solos si se reintenta (ritmo, caídas de Meta). */
    public const TRANSITORIOS = ['4', '80007', '130429', '131000', '131016', '131048', '131056', '133004'];

    public function configurado(): bool
    {
        $c = config('promociones.whatsapp');

        return ($c['driver'] ?? 'log') === 'meta' && filled($c['token']) && filled($c['phone_number_id']);
    }

    public function numeroPrincipal(): string
    {
        return (string) config('promociones.whatsapp.phone_number_id');
    }

    /**
     * Manda un mensaje. Regresa ['ok' => bool, 'wamid' => ?, 'codigo' => ?, 'error' => ?, 'incierto' => bool]
     * "incierto": no hubo respuesta (se cayó la conexión): Meta pudo o no haberlo mandado.
     */
    public function enviar(string $phoneNumberId, array $payload): array
    {
        if (! $this->configurado()) {
            return ['ok' => false, 'wamid' => null, 'codigo' => 'config', 'error' => 'WhatsApp no está configurado en el servidor.', 'incierto' => false];
        }

        try {
            $r = $this->http(30)->post($this->url($phoneNumberId.'/messages'), ['messaging_product' => 'whatsapp', 'recipient_type' => 'individual'] + $payload);
        } catch (ConnectionException) {
            return ['ok' => false, 'wamid' => null, 'codigo' => 'red', 'error' => 'No hubo respuesta de WhatsApp: puede que sí se haya enviado.', 'incierto' => true];
        }

        if ($r->successful() && $r->json('messages.0.id')) {
            return ['ok' => true, 'wamid' => (string) $r->json('messages.0.id'), 'codigo' => null, 'error' => null, 'incierto' => false];
        }

        return ['ok' => false, 'wamid' => null] + $this->error($r) + ['incierto' => $r->serverError()];
    }

    /** Sube un archivo a Meta y regresa su media id. */
    public function subir(string $phoneNumberId, string $ruta, string $mime, string $nombre): array
    {
        try {
            $r = $this->http(120)
                ->attach('file', fopen($ruta, 'r'), $nombre, ['Content-Type' => $mime])
                ->post($this->url($phoneNumberId.'/media'), ['messaging_product' => 'whatsapp', 'type' => $mime]);
        } catch (ConnectionException) {
            return ['ok' => false, 'id' => null, 'codigo' => 'red', 'error' => 'No se pudo subir el archivo a WhatsApp (sin conexión).'];
        }

        if ($r->successful() && $r->json('id')) {
            return ['ok' => true, 'id' => (string) $r->json('id'), 'codigo' => null, 'error' => null];
        }

        return ['ok' => false, 'id' => null] + $this->error($r);
    }

    /** Baja el archivo de un media id. Regresa ['ok', 'contenido', 'mime', 'tamano', 'error']. */
    public function bajar(string $mediaId): array
    {
        try {
            $meta = $this->http(20)->get($this->url($mediaId));
            if (! $meta->successful() || ! $meta->json('url')) {
                return ['ok' => false, 'error' => $meta->status() === 404 || $meta->status() === 400
                    ? 'WhatsApp ya no tiene este archivo (los archivos recibidos duran 7 días).'
                    : ($this->error($meta)['error'])];
            }

            $bin = $this->http(120)->get((string) $meta->json('url'));
            if (! $bin->successful()) {
                return ['ok' => false, 'error' => 'No se pudo descargar el archivo de WhatsApp.'];
            }

            return [
                'ok' => true,
                'contenido' => $bin->body(),
                'mime' => (string) ($meta->json('mime_type') ?: $bin->header('Content-Type')),
                'tamano' => (int) ($meta->json('file_size') ?: strlen($bin->body())),
                'error' => null,
            ];
        } catch (ConnectionException) {
            return ['ok' => false, 'error' => 'Sin conexión con WhatsApp al bajar el archivo.'];
        }
    }

    /** Plantillas de la cuenta con sus partes (encabezado, cuerpo, pie, botones). Caché de 5 min. */
    public function plantillas(bool $fresco = false): ?array
    {
        $waba = (string) config('promociones.whatsapp.business_account_id');
        if (! $this->configurado() || $waba === '') {
            return null;
        }

        $clave = 'mensajeria:plantillas:'.$waba;
        if ($fresco) {
            Cache::forget($clave);
        }

        return Cache::remember($clave, 300, function () use ($waba) {
            try {
                $todas = [];
                $url = $this->url($waba.'/message_templates');
                $params = ['fields' => 'id,name,status,category,language,components,parameter_format,rejected_reason,quality_score', 'limit' => 100];

                for ($i = 0; $i < 10 && $url; $i++) {
                    $r = $this->http(20)->get($url, $params);
                    if (! $r->successful()) {
                        return null;
                    }
                    $todas = array_merge($todas, (array) $r->json('data', []));
                    $url = $r->json('paging.next');
                    $params = [];
                }

                return $todas;
            } catch (ConnectionException) {
                return null;
            }
        });
    }

    /** Números de la cuenta (para elegir bandeja). Caché de 10 min. */
    public function numeros(): array
    {
        $waba = (string) config('promociones.whatsapp.business_account_id');
        $principal = $this->numeroPrincipal();
        $base = $principal !== '' ? [$principal => ['id' => $principal, 'numero' => null, 'nombre' => null]] : [];

        if (! $this->configurado() || $waba === '') {
            return $base;
        }

        return Cache::remember('mensajeria:numeros:'.$waba, 600, function () use ($waba, $base) {
            try {
                $r = $this->http(15)->get($this->url($waba.'/phone_numbers'), ['fields' => 'id,display_phone_number,verified_name']);
                if (! $r->successful()) {
                    return $base;
                }
                foreach ((array) $r->json('data', []) as $n) {
                    $base[(string) $n['id']] = ['id' => (string) $n['id'], 'numero' => $n['display_phone_number'] ?? null, 'nombre' => $n['verified_name'] ?? null];
                }
            } catch (ConnectionException) {
            }

            return $base;
        });
    }

    /** Avisa a WhatsApp que ya se leyó (las palomitas azules del cliente). */
    public function marcarLeido(string $phoneNumberId, string $wamid): void
    {
        try {
            $this->http(10)->post($this->url($phoneNumberId.'/messages'), [
                'messaging_product' => 'whatsapp', 'status' => 'read', 'message_id' => $wamid,
            ]);
        } catch (\Throwable) {
            // No es crítico.
        }
    }

    private function error(Response $r): array
    {
        $codigo = (string) ($r->json('error.code') ?? $r->status());

        return [
            'codigo' => $codigo,
            'error' => mb_substr((string) ($r->json('error.error_data.details') ?? $r->json('error.message') ?? 'Error de WhatsApp ('.$r->status().')'), 0, 500),
        ];
    }

    private function http(int $timeout)
    {
        return Http::withToken((string) config('promociones.whatsapp.token'))->acceptJson()->timeout($timeout);
    }

    private function url(string $ruta): string
    {
        return 'https://graph.facebook.com/'.config('promociones.whatsapp.api_version').'/'.ltrim($ruta, '/');
    }
}

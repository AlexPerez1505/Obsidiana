<?php

namespace App\Services\Promociones;

use App\Models\CampanaDestinatario;
use App\Models\MktWebhookEvento;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * Avisos de WhatsApp (webhooks de la Cloud API):
 *   • statuses: sent / delivered / read / failed de cada mensaje.
 *   • messages: lo que contesta el cliente (botón "Ya no quiero promociones" o "BAJA").
 *
 * Meta reintenta y a veces repite avisos: cada uno deja una huella única y
 * la segunda vez se ignora. Los estados nunca retroceden (un "delivered"
 * que llega después de un "read" no lo baja a entregado).
 */
class WebhookWhatsApp
{
    /** Palabras con las que el cliente pide la baja por texto. */
    private const PALABRAS_BAJA = ['baja', 'stop', 'alto', 'cancelar', 'no mas promociones', 'no más promociones'];

    public function __construct(private readonly Consentimientos $consentimientos)
    {
    }

    /** Firma HMAC-SHA256 del cuerpo crudo con el secreto de la app (cabecera X-Hub-Signature-256). */
    public function firmaValida(string $cuerpo, ?string $firma): bool
    {
        $secreto = (string) config('promociones.whatsapp.app_secret');
        if ($secreto === '' || ! $firma) {
            return false;
        }

        return hash_equals('sha256='.hash_hmac('sha256', $cuerpo, $secreto), $firma);
    }

    /** @return array{estados: int, duplicados: int, bajas: int} */
    public function procesar(array $payload): array
    {
        $r = ['estados' => 0, 'duplicados' => 0, 'bajas' => 0];

        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $valor = (array) ($change['value'] ?? []);

                foreach ((array) ($valor['statuses'] ?? []) as $st) {
                    $this->estado((array) $st, $r);
                }
                foreach ((array) ($valor['messages'] ?? []) as $msg) {
                    $this->mensaje((array) $msg, $r);
                }
            }
        }

        return $r;
    }

    private function estado(array $st, array &$r): void
    {
        $wamid = (string) ($st['id'] ?? '');
        $estado = (string) ($st['status'] ?? '');
        if ($wamid === '' || $estado === '') {
            return;
        }

        if (! $this->primeraVez('estado', $wamid, $estado, (string) ($st['timestamp'] ?? ''))) {
            $r['duplicados']++;

            return;
        }

        $d = CampanaDestinatario::where('whatsapp_message_id', $wamid)->first();
        if (! $d) {
            return;
        }

        $cuando = isset($st['timestamp']) ? Carbon::createFromTimestamp((int) $st['timestamp']) : now();
        $rango = CampanaDestinatario::RANGO;
        $actual = $rango[$d->estado] ?? 0;

        $cambios = match ($estado) {
            'sent' => $actual < 1 ? ['estado' => CampanaDestinatario::ENVIADO, 'enviado_en' => $d->enviado_en ?? $cuando] : [],
            'delivered' => $actual < 2
                ? ['estado' => CampanaDestinatario::ENTREGADO, 'entregado_en' => $cuando]
                : ['entregado_en' => $d->entregado_en ?? $cuando],
            'read' => ['estado' => CampanaDestinatario::LEIDO, 'leido_en' => $cuando, 'entregado_en' => $d->entregado_en ?? $cuando],
            'failed' => $actual < 2 ? [
                'estado' => CampanaDestinatario::FALLO,
                'fallo_en' => $cuando,
                'error_codigo' => (string) ($st['errors'][0]['code'] ?? 'failed'),
                'error' => mb_substr((string) ($st['errors'][0]['error_data']['details'] ?? $st['errors'][0]['title'] ?? $st['errors'][0]['message'] ?? 'WhatsApp no pudo entregar el mensaje'), 0, 500),
            ] : [],
            default => [],
        };

        if ($cambios) {
            $d->update($cambios);
            $r['estados']++;
        }
    }

    private function mensaje(array $msg, array &$r): void
    {
        $id = (string) ($msg['id'] ?? '');
        $de = (string) ($msg['from'] ?? '');
        if ($id === '' || $de === '' || ! $this->primeraVez('mensaje', $id, '', '')) {
            $r['duplicados'] += $id !== '' ? 1 : 0;

            return;
        }

        $payload = (string) ($msg['button']['payload'] ?? $msg['interactive']['button_reply']['id'] ?? '');
        $texto = mb_strtolower(trim((string) ($msg['text']['body'] ?? $msg['button']['text'] ?? '')));

        $pideBaja = $payload === 'PROMO_BAJA'
            || in_array(trim($texto, " .!¡"), self::PALABRAS_BAJA, true)
            || str_contains($texto, 'ya no quiero promociones');

        if (! $pideBaja) {
            return;
        }

        foreach ($this->consentimientos->clientesConTelefono($de) as $cliente) {
            $this->consentimientos->revocar($cliente, 'whatsapp', 'Pidió la baja respondiendo por WhatsApp');
            $r['bajas']++;
        }
    }

    /** Guarda la huella; si ya existía, es un aviso repetido. */
    private function primeraVez(string $tipo, string $id, string $valor, string $ts): bool
    {
        try {
            MktWebhookEvento::create([
                'huella' => hash('sha256', $tipo.'|'.$id.'|'.$valor.'|'.$ts),
                'tipo' => $tipo,
                'wamid' => mb_substr($id, 0, 191),
                'valor' => $valor ?: null,
            ]);

            return true;
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062 || str_contains($e->getMessage(), 'UNIQUE')) {
                return false;
            }
            throw $e;
        }
    }
}

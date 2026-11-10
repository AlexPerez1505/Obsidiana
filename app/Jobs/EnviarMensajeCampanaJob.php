<?php

namespace App\Jobs;

use App\Contracts\WhatsAppSender;
use App\Models\Campana;
use App\Models\CampanaDestinatario;
use App\Services\Promociones\Lanzador;
use App\Services\Promociones\MensajeCampana;
use App\Services\WhatsApp\LogWhatsAppSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;

/**
 * Manda la promoción a UN destinatario y deja el resultado en su renglón.
 *
 * • No duplica: antes de llamar a WhatsApp "reclama" el renglón con un
 *   UPDATE condicionado (pendiente → enviando). Si otro proceso ya lo
 *   tomó, este no hace nada.
 * • Reintenta solo errores pasajeros (red, límites, 5xx), con espera
 *   creciente; los definitivos (número sin WhatsApp, plantilla rechazada,
 *   el cliente bloqueó el marketing) se marcan como fallo de inmediato.
 * • Revisa en el último momento la pausa, el consentimiento y la frecuencia.
 */
class EnviarMensajeCampanaJob implements ShouldQueue
{
    use Queueable;

    /** Reintentos de errores pasajeros (las esperas del limitador no cuentan aquí). */
    public int $maxExceptions = 3;

    public function __construct(public int $destinatarioId, public bool $prueba = false)
    {
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(6);
    }

    public function middleware(): array
    {
        // La prueba se manda al momento (dispatchSync): no pasa por el limitador de la cola.
        return $this->prueba ? [] : [new RateLimited('whatsapp')];
    }

    public function handle(WhatsAppSender $sender, MensajeCampana $mensajes, Lanzador $lanzador): void
    {
        $d = CampanaDestinatario::with(['campana.cupon', 'cliente', 'cupon'])->find($this->destinatarioId);

        if (! $d || $d->estado !== CampanaDestinatario::PENDIENTE) {
            return;
        }

        $campana = $d->campana;

        if (! $d->es_prueba) {
            if ($campana->estado === Campana::CANCELADA) {
                $d->update(['estado' => CampanaDestinatario::CANCELADO]);

                return;
            }
            if ($campana->estado !== Campana::ENVIANDO) {
                return; // pausada: se queda pendiente y se encola al reanudar
            }

            // Última barrera: pudo darse de baja después de armar la lista.
            if (! $d->cliente || ! $d->cliente->puedeRecibirPromociones()) {
                $d->update(['estado' => CampanaDestinatario::OMITIDO_CONSENTIMIENTO, 'error' => 'Sin consentimiento vigente al momento de enviar.']);
                $lanzador->cerrarSiTermino($campana);

                return;
            }

            if ($this->recibioOtraHacePoco($d)) {
                $d->update(['estado' => CampanaDestinatario::OMITIDO_FRECUENCIA, 'error' => 'Recibió otra promoción en las últimas '.config('promociones.frecuencia_horas').' h.']);
                $lanzador->cerrarSiTermino($campana);

                return;
            }
        }

        // Reclamo atómico: si dos procesos agarran el mismo envío, solo uno sigue.
        $tomado = CampanaDestinatario::whereKey($d->id)
            ->where('estado', CampanaDestinatario::PENDIENTE)
            ->update(['estado' => CampanaDestinatario::ENVIANDO, 'intentos' => $d->intentos + 1, 'updated_at' => now()]);

        if (! $tomado) {
            return;
        }
        $d->syncOriginalAttribute('estado');
        $d->estado = CampanaDestinatario::ENVIANDO;
        $d->syncOriginalAttribute('estado');

        // Mientras PROMO_SOLO_NUMEROS_PRUEBA esté encendido, solo los números
        // autorizados reciben de verdad; los demás quedan como simulados.
        if (! $d->es_prueba && config('promociones.solo_numeros_prueba') && ! $this->esNumeroDePrueba((string) $d->telefono)) {
            $sender = new LogWhatsAppSender();
        }

        $r = $sender->enviarPlantilla($mensajes->paraDestinatario($d));

        if ($r->exito) {
            $d->update([
                'estado' => $r->simulado ? CampanaDestinatario::SIMULADO : CampanaDestinatario::ENVIADO,
                'simulado' => $r->simulado,
                'whatsapp_message_id' => $r->messageId,
                'enviado_en' => now(),
                'error' => null,
                'error_codigo' => null,
            ]);
        } elseif ($r->transitorio && ! $d->es_prueba && $this->attempts() < 4) {
            // Por consulta: en memoria el renglón todavía dice "pendiente" y
            // Eloquent no guardaría el regreso desde "enviando".
            CampanaDestinatario::whereKey($d->id)->update(['estado' => CampanaDestinatario::PENDIENTE, 'error' => $r->error, 'error_codigo' => $r->codigo]);
            $this->release([60, 300, 900][min($this->attempts() - 1, 2)]);

            return;
        } else {
            $d->update([
                'estado' => CampanaDestinatario::FALLO,
                'error' => $r->error,
                'error_codigo' => $r->codigo,
                'fallo_en' => now(),
            ]);
        }

        if (! $d->es_prueba) {
            $lanzador->cerrarSiTermino($campana);
        }
    }

    public function failed(?\Throwable $e): void
    {
        CampanaDestinatario::whereKey($this->destinatarioId)
            ->whereIn('estado', [CampanaDestinatario::PENDIENTE, CampanaDestinatario::ENVIANDO])
            ->update([
                'estado' => CampanaDestinatario::FALLO,
                'fallo_en' => now(),
                'error_codigo' => 'reintentos',
                'error' => 'No se pudo enviar después de varios intentos.',
            ]);
    }

    private function recibioOtraHacePoco(CampanaDestinatario $d): bool
    {
        $horas = (int) config('promociones.frecuencia_horas');
        if ($horas <= 0) {
            return false;
        }

        return CampanaDestinatario::where('cliente_id', $d->cliente_id)
            ->where('id', '!=', $d->id)
            ->where('campana_id', '!=', $d->campana_id)
            ->where('es_prueba', false)
            ->whereIn('estado', [CampanaDestinatario::ENVIADO, CampanaDestinatario::ENTREGADO, CampanaDestinatario::LEIDO])
            ->where('enviado_en', '>=', now()->subHours($horas))
            ->exists();
    }

    private function esNumeroDePrueba(string $telefono): bool
    {
        foreach (config('promociones.numeros_prueba') as $n) {
            if (\App\Services\WhatsApp\Telefono::e164($n) === $telefono) {
                return true;
            }
        }

        return false;
    }
}

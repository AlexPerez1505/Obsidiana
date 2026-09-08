<?php

namespace App\Jobs;

use App\Contracts\WhatsAppSender;
use App\Models\CampanaDestinatario;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;

/**
 * Manda el mensaje de una campaña a UN destinatario, y deja el
 * resultado en su fila de campana_destinatarios (nunca se manda sin
 * dejar rastro de qué pasó).
 */
class EnviarMensajeCampanaJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $destinatarioId)
    {
    }

    /** Un mensaje por segundo: aquí es donde de verdad importa no rebasar el ritmo de la cuenta de WhatsApp. */
    public function middleware(): array
    {
        return [new RateLimited('whatsapp')];
    }

    public function handle(WhatsAppSender $sender): void
    {
        $destinatario = CampanaDestinatario::with(['cliente', 'campana'])->find($this->destinatarioId);

        if (! $destinatario || $destinatario->estado !== CampanaDestinatario::ESTADO_PENDIENTE) {
            return;
        }

        $cliente = $destinatario->cliente;

        // Última barrera antes de mandar: si en el momento de armar la
        // campaña calificaba pero ya no (revocó, o nunca llegó a
        // confirmar), se excluye aquí también, no solo al armar la lista.
        if (! $cliente || ! $cliente->puedeRecibirPromociones()) {
            $destinatario->update([
                'estado' => CampanaDestinatario::ESTADO_EXCLUIDO,
                'error' => 'El cliente ya no tiene el consentimiento confirmado al momento de enviar.',
            ]);

            return;
        }

        $resultado = $sender->enviar($cliente->telefono, $destinatario->campana->mensaje);

        if ($resultado->exito) {
            $destinatario->update([
                'estado' => CampanaDestinatario::ESTADO_ENVIADO,
                'whatsapp_message_id' => $resultado->messageId,
                'enviado_en' => now(),
            ]);

            return;
        }

        $destinatario->update([
            'estado' => CampanaDestinatario::ESTADO_FALLO,
            'error' => $resultado->error,
        ]);
    }
}

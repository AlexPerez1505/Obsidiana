<?php

namespace App\Jobs;

use App\Contracts\WhatsAppSender;
use App\Models\Customer;
use App\Models\PromoConfirmacion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;

/**
 * Le manda a un cliente el mensaje "¿confirmas que quieres recibir
 * promociones?" y deja el intento registrado en promo_confirmaciones.
 *
 * No cambia el estado de "confirmado" aquí: eso solo lo hace el webhook
 * (o el registro manual) cuando de verdad llega la respuesta del cliente.
 */
class EnviarConfirmacionPromocionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $clienteId)
    {
    }

    /** Un mensaje por segundo: WhatsApp Business API limita por ritmo, no solo por volumen. */
    public function middleware(): array
    {
        return [new RateLimited('whatsapp')];
    }

    public function handle(WhatsAppSender $sender): void
    {
        $cliente = Customer::find($this->clienteId);

        // Pudo haberse ido de baja o revocado entre que se encoló y que
        // le tocó su turno: se revisa otra vez justo antes de mandar.
        if (! $cliente || ! $cliente->promocionPendienteDeConfirmar()) {
            return;
        }

        $empresa = config('medibuy.empresa.nombre', 'nuestra empresa');
        $mensaje = "Hola {$cliente->nombre}, en {$empresa} nos autorizaste a enviarte promociones. "
            .'Contesta *SI* para confirmarlo o *NO* si ya no quieres recibir nada.';

        $resultado = $sender->enviar($cliente->telefono, $mensaje);

        PromoConfirmacion::create([
            'cliente_id' => $cliente->id,
            'canal' => 'whatsapp',
            'mensaje_enviado_en' => now(),
            'whatsapp_message_id' => $resultado->messageId,
        ]);
    }
}

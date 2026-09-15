<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada intento de pedirle al cliente que confirme si quiere promociones.
 *
 * Es el historial completo, no solo el último resultado: si se le
 * pregunta más de una vez (por ejemplo, si nunca contestó la primera),
 * cada intento queda como su propia fila, con lo que contestó tal cual
 * (para tener evidencia real si algún día hay un reclamo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_confirmaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();

            // Por ahora solo whatsapp, pero se deja abierto: cada canal
            // tiene sus propias reglas de frecuencia y de opt-out.
            $table->string('canal', 20)->default('whatsapp');

            $table->timestamp('mensaje_enviado_en')->nullable();
            $table->string('whatsapp_message_id')->nullable();

            // 'si' | 'no' | null (todavía no contesta, o contestó algo
            // que no se pudo interpretar).
            $table->string('respuesta', 10)->nullable();
            $table->text('respuesta_texto_crudo')->nullable();
            $table->timestamp('respondido_en')->nullable();

            $table->timestamps();

            $table->index(['cliente_id', 'respuesta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_confirmaciones');
    }
};

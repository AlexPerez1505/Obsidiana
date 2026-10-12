<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A quién le tocó cada campaña, y en qué quedó su envío. Es el log real:
 * sin esto no se podría saber a quién se le mandó qué ni por qué falló.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campana_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->constrained('campanas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();

            // pendiente -> enviado -> entregado -> leido (o fallo, o
            // excluido_sin_consentimiento si en el momento de mandar ya
            // no calificaba).
            $table->string('estado', 30)->default('pendiente');

            $table->string('whatsapp_message_id')->nullable();
            $table->timestamp('enviado_en')->nullable();
            $table->timestamp('entregado_en')->nullable();
            $table->timestamp('leido_en')->nullable();
            $table->text('error')->nullable();

            $table->timestamps();

            $table->unique(['campana_id', 'cliente_id']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campana_destinatarios');
    }
};

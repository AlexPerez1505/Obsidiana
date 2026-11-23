<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mensajería de WhatsApp: una conversación por número del cliente y por
 * bandeja (el número de la empresa que recibe), con sus mensajes.
 *
 * Los mensajes guardan el id de Meta (wamid) único: un webhook repetido no
 * los duplica. El estado solo avanza (enviado → entregado → leído).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_conversaciones', function (Blueprint $table) {
            $table->id();
            // La bandeja: el número de la empresa (phone_number_id de Meta).
            $table->string('phone_number_id', 40);
            $table->string('telefono', 20);            // del cliente, solo dígitos (E.164 sin +)
            $table->string('nombre_perfil')->nullable(); // el nombre que trae su WhatsApp
            $table->foreignId('customer_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->unsignedInteger('no_leidos')->default(0);
            $table->timestamp('ultima_actividad_en')->nullable();
            // Abre la ventana de 24 h para mensajes libres.
            $table->timestamp('ultimo_entrante_en')->nullable();
            $table->unsignedBigInteger('ultimo_mensaje_id')->nullable();
            $table->timestamps();

            $table->unique(['phone_number_id', 'telefono']);
            $table->index('ultima_actividad_en');
        });

        Schema::create('wa_mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('wa_conversaciones')->cascadeOnDelete();
            $table->string('wamid', 191)->nullable()->unique();
            // Clave que pone quien envía: sirve para reintentar sin duplicar
            // y para ligar el estado que manda Meta (biz_opaque_callback_data).
            $table->uuid('uuid')->nullable()->unique();
            $table->string('direccion', 10);           // entrante | saliente
            $table->string('tipo', 20);                // text, image, video, audio, sticker, document, location, contacts, interactive, button, reaction, template, unsupported…
            $table->text('cuerpo')->nullable();        // texto o pie de foto
            $table->json('datos')->nullable();         // ubicación, contactos, botones, plantilla renderizada…
            $table->string('responde_a', 191)->nullable(); // wamid del mensaje citado

            // Archivo adjunto.
            $table->string('media_id', 64)->nullable();
            $table->string('media_mime', 120)->nullable();
            $table->string('media_nombre')->nullable();
            $table->unsignedBigInteger('media_tamano')->nullable();
            $table->string('media_path')->nullable();
            $table->string('media_estado', 15)->nullable(); // pendiente | listo | fallo

            $table->string('estado', 12);              // recibido | enviando | enviado | entregado | leido | fallo
            $table->string('error_codigo', 20)->nullable();
            $table->string('error', 500)->nullable();
            $table->foreignId('enviado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('momento')->nullable();  // cuándo lo mandó quien lo mandó
            $table->timestamp('entregado_en')->nullable();
            $table->timestamp('leido_en')->nullable();
            $table->timestamps();

            $table->index(['conversacion_id', 'id']);
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_mensajes');
        Schema::dropIfExists('wa_conversaciones');
    }
};

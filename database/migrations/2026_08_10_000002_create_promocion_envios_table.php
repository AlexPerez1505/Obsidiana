<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promocion_envios', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('promocion_id');
            $table->foreign('promocion_id')->references('id')->on('promociones')->onDelete('cascade');

            $table->unsignedBigInteger('cliente_id');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');

            $table->string('canal')->default('whatsapp');
            $table->string('destino_usado')->nullable();
            $table->string('estado'); // enviado | fallido | sin_destino | omitido_sin_consentimiento
            $table->string('referencia_externa')->nullable(); // wamid de WhatsApp
            $table->text('error_detalle')->nullable();

            $table->timestamps();

            $table->unique(['promocion_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promocion_envios');
    }
};

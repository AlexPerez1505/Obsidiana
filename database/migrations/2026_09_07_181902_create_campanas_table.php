<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una campaña de promoción: el mensaje y a quién se le manda, como
 * registro completo. Nunca se manda "al aire": queda quién la armó,
 * cuándo, con qué filtro de audiencia y en qué quedó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campanas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('mensaje');

            // Filtro de a quién va dirigida (categoria_id, congreso_id...),
            // para poder repetir o auditar después con qué criterio se armó.
            $table->json('filtros')->nullable();

            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();

            $table->timestamp('programada_para')->nullable();

            // borrador -> en_cola -> enviando -> completada (o cancelada).
            $table->string('estado', 20)->default('borrador');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campanas');
    }
};

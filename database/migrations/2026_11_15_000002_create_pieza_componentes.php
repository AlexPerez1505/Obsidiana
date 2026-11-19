<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Componentes de una pieza.
 *
 * Un equipo puede llegar como set (p. ej. un sistema 180 con procesador y
 * fuente, y cada uno con su pigtail, interfaz, cables…) o por partes: primero
 * el procesador y a la semana lo demás. Cada componente lleva su propia serie
 * y sus fotos, puede colgar de otro componente y puede quedar "por llegar"
 * para registrarlo cuando llegue.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pieza_componentes')) {
            return;
        }

        Schema::create('pieza_componentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_serial_id')->constrained('producto_seriales')->cascadeOnDelete();
            $table->foreignId('padre_id')->nullable()->constrained('pieza_componentes')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->string('no_serie', 120)->nullable()->index();
            $table->string('estado', 20)->default('recibido');
            $table->dateTime('recibido_en')->nullable();
            $table->json('fotos')->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('capturado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recibido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pieza_componentes');
    }
};

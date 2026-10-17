<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evidencia de las órdenes de salida: fotos y videos del equipo preparado,
 * emplayado o entregado.
 *
 * Un renglón por archivo y sin tope de cantidad: cada foto se sube en su
 * propia petición, así que la orden puede juntar las que hagan falta sin
 * chocar con el límite de tamaño del servidor. Se puede seguir subiendo
 * después de entregada (la foto de la entrega a veces llega luego), pero
 * no a una orden cancelada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_salida_evidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_salida_id')->constrained('ordenes_salida')->cascadeOnDelete();
            $table->string('tipo', 10); // foto | video
            $table->string('path', 255);
            $table->string('nombre_original', 255)->nullable();
            $table->unsignedBigInteger('tamano')->nullable(); // bytes
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['orden_salida_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_salida_evidencias');
    }
};

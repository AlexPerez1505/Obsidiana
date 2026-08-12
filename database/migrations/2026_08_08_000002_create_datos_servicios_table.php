<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datos_servicios', function (Blueprint $table) {
            $table->id('id_dato_servicio');
            $table->foreignId('id_servicio')->constrained('registro_servicios', 'id_servicio')->cascadeOnDelete();
            $table->enum('origen_equipo', ['cliente', 'empresa', 'alquilado', 'prestado']);
            $table->foreignId('id_tipo_de_equipo')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('id_subtipo')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('id_marca')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('id_modelo')->constrained('productos')->cascadeOnDelete();
            $table->string('descripcion', 200)->nullable();
            $table->string('observaciones', 200)->nullable();
            $table->text('evidencia_1')->nullable();
            $table->text('evidencia_2')->nullable();
            $table->text('evidencia_3')->nullable();
            $table->text('video')->nullable();
            $table->text('firma_digital')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datos_servicios');
    }
};

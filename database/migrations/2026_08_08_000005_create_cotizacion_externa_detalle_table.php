<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_externa_detalle', function (Blueprint $table) {
            $table->id('id_cotizacion');
            $table->foreignId('id_cotizacion_servicios')->constrained('cotizacion_servicios')->cascadeOnDelete();
            $table->foreignId('id_tecnico')->constrained('users')->cascadeOnDelete();
            $table->foreignId('id_mantenimiento')->nullable()->constrained('mantenimiento_interno')->nullOnDelete();
            $table->decimal('costo_tecnico', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_externa_detalle');
    }
};

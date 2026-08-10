<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_servicio')->constrained('registro_servicios', 'id_servicio')->cascadeOnDelete();
            $table->enum('tipo_cotizacion', ['interno', 'externo']);
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado', 'en_proceso']);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('costo_envio', 12, 2)->default(0);
            $table->string('lugar', 200)->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->foreignId('id_plan_pagos')->nullable()->constrained('plan_pagos')->nullOnDelete();
            $table->foreignId('id_metodos_pagos')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_servicios');
    }
};

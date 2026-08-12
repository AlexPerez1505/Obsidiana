<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_interna_detalle', function (Blueprint $table) {
            $table->id('id_cotizacion');
            $table->foreignId('id_cotizacion_servicios')->constrained('cotizacion_servicios')->cascadeOnDelete();
            $table->foreignId('id_mantenimiento')->nullable();
            $table->foreignId('id_rol')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('id_refacciones')->constrained('refacciones')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_interna_detalle');
    }
};

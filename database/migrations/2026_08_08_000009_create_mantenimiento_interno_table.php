<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimiento_interno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cotizacion')->constrained('cotizacion_interna_detalle', 'id_cotizacion')->cascadeOnDelete();
            $table->enum('tipo_mantenimiento', ['interno', 'externo']);
            $table->foreignId('id_rol')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('id_tecnico_externo')->nullable()->constrained('tecnico_externo')->nullOnDelete();
            $table->text('url_ticket')->nullable();
            $table->text('evidencia_1')->nullable();
            $table->text('evidencia_2')->nullable();
            $table->text('evidencia_3')->nullable();
            $table->enum('estado_mantenimiento', ['en_espera', 'refacciones', 'finalizado', 'cancelado']);
            $table->enum('tipo_reparacion', ['preventivo', 'correctivo', 'mixto']);
            $table->foreignId('id_checklist')->nullable()->constrained('checklist')->nullOnDelete();
            $table->text('descripcion')->nullable();
            $table->string('fallas_encontradas', 100)->nullable();
            $table->foreignId('id_refacciones')->nullable()->constrained('refacciones')->nullOnDelete();
            $table->foreignId('id_carta_garantia')->nullable()->constrained('cartas_garantia')->nullOnDelete();
            $table->date('proximo_mantenimiento')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_interno');
    }
};

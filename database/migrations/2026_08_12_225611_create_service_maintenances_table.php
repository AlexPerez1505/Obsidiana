<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->enum('tipo_mantenimiento', ['interno', 'externo'])->default('externo');
            $table->enum('tipo_reparacion', ['preventivo', 'correctivo', 'mixto'])->nullable();
            $table->foreignId('tecnico_externo_id')->nullable()->constrained('tecnico_externo')->nullOnDelete();
            $table->json('checklist')->nullable();
            $table->text('descripcion')->nullable();
            $table->text('fallas_encontradas')->nullable();
            $table->json('refacciones')->nullable();
            $table->string('evidencia_1')->nullable();
            $table->string('evidencia_2')->nullable();
            $table->string('evidencia_3')->nullable();
            $table->date('proximo_mantenimiento')->nullable();
            $table->string('carta_garantia')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_maintenances');
    }
};

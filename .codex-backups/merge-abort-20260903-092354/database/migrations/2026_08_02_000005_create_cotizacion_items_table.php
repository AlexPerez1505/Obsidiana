<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cotizacion_items')) {
            return;
        }

        Schema::create('cotizacion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();
            $table->foreignId('paquete_id')->nullable()->constrained('paquetes')->nullOnDelete();
            $table->enum('tipo_item', ['equipo', 'paquete'])->default('equipo');

            // Snapshots (preservan el histórico ante cambios del catálogo)
            $table->string('nombre');
            $table->string('modelo')->nullable();
            $table->string('marca')->nullable();
            $table->string('imagen')->nullable();

            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('precio_unitario', 12, 2)->default(0);
            $table->decimal('sobreprecio', 12, 2)->default(0);
            $table->boolean('es_regalo')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_items');
    }
};

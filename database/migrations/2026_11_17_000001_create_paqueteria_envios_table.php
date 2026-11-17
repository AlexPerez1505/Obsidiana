<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paqueteria_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->nullOnDelete();
            $table->string('remision_folio', 80)->nullable()->index();
            $table->string('cliente', 255);
            $table->string('destino', 255);
            $table->string('codigo_postal', 20)->nullable();
            $table->string('direccion', 500)->nullable();
            $table->string('telefono_receptor', 80)->nullable();
            $table->string('paqueteria', 120);
            $table->string('paqueteria_personalizada', 120)->nullable();
            $table->string('tipo_guia', 20)->nullable();
            $table->string('numero_guia', 120)->nullable()->index();
            $table->decimal('capacidad_guia', 10, 2)->nullable();
            $table->date('fecha_envio');
            $table->date('fecha_entrega_estimada')->nullable();
            $table->decimal('costo', 12, 2)->nullable();
            $table->boolean('costo_pendiente')->default(false);
            $table->decimal('peso_real', 12, 2)->default(0);
            $table->decimal('peso_volumetrico', 12, 2)->default(0);
            $table->decimal('peso_cobro', 12, 2)->default(0);
            $table->unsignedInteger('paquetes_count')->default(0);
            $table->string('estado', 30)->default('por_enviar')->index();
            $table->json('articulos')->nullable();
            $table->json('paquetes')->nullable();
            $table->json('responsables')->nullable();
            $table->text('notas')->nullable();
            $table->string('ticket_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paqueteria_envios');
    }
};

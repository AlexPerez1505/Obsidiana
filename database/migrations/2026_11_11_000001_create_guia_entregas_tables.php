<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guia_entregas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_rastreo', 120)->unique();
            $table->decimal('peso_total', 10, 2)->nullable();
            $table->date('fecha_entrega');
            $table->text('contenido')->nullable();
            $table->string('destinatario')->nullable();
            $table->string('entregado_por')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('foto_path')->nullable();
            $table->string('firma_path')->nullable();
            $table->string('estado', 30)->default('entregado');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('guia_entrega_producto_serial', function (Blueprint $table) {
            $table->foreignId('guia_entrega_id')->constrained('guia_entregas')->cascadeOnDelete();
            $table->foreignId('producto_serial_id')->constrained('producto_seriales')->cascadeOnDelete();
            $table->primary(['guia_entrega_id', 'producto_serial_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guia_entrega_producto_serial');
        Schema::dropIfExists('guia_entregas');
    }
};

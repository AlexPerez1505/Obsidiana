<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_servicios', function (Blueprint $table) {
            $table->id('id_servicio');
            $table->foreignId('id_cliente')->constrained('clientes')->cascadeOnDelete();
            $table->enum('tipo_de_mantenimiento', ['preventivo', 'correctivo', 'predictivo', 'mejora', 'instalacion']);
            $table->foreignId('asesor')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_servicios');
    }
};

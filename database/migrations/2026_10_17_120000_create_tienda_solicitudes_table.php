<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Solicitudes de cotización que llegan desde la tienda en línea (sitio
| público en React). Los artículos se guardan como foto del momento: si el
| equipo cambia o se vende después, la solicitud sigue diciendo qué pidió.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tienda_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            // cotizacion = trae equipos; contacto = mensaje del formulario de Contacto.
            $table->string('tipo', 20)->default('cotizacion')->index();
            $table->string('motivo', 60)->nullable();
            $table->string('nombre', 120);
            $table->string('empresa', 160)->nullable();
            $table->string('correo', 160)->nullable();
            $table->string('telefono', 30);
            $table->string('ciudad', 120)->nullable();
            $table->string('especialidad', 120)->nullable();
            $table->text('mensaje')->nullable();
            $table->json('articulos')->nullable();
            $table->string('estado', 20)->default('nueva')->index();
            $table->foreignId('customer_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('atendida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('atendida_en')->nullable();
            $table->text('nota_interna')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tienda_solicitudes');
    }
};

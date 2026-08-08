<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // La tabla fue creada originalmente con un esquema distinto (precio_base, regalo, subtotal,
        // sin columna nombre) al que espera el modelo CotizacionItem. Como no tiene datos, se reconstruye
        // con el esquema correcto.
        Schema::dropIfExists('cotizacion_items');

        Schema::create('cotizacion_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cotizacion_id');
            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->onDelete('cascade');

            $table->unsignedBigInteger('producto_id')->nullable();
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('set null');

            $table->unsignedBigInteger('paquete_id')->nullable();
            $table->foreign('paquete_id')->references('id')->on('paquetes')->onDelete('set null');

            $table->string('nombre');
            $table->integer('cantidad')->default(1);
            $table->decimal('precio_original', 12, 2)->default(0);
            $table->decimal('sobreprecio', 12, 2)->default(0);
            $table->decimal('precio_final', 12, 2)->default(0);
            $table->boolean('es_regalo')->default(false);
            $table->decimal('subtotal_linea', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizacion_items');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->boolean('aplica_iva')->default(false)->after('iva');
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn('aplica_iva');
        });

        Schema::dropIfExists('cotizacion_items');
    }
};

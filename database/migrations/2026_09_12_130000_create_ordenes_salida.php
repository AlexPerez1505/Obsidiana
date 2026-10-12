<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Órdenes de salida: lo que almacén tiene que preparar cuando se vende.
 *
 * Cada venta con equipo físico genera una orden con un renglón por
 * partida. El renglón sabe si esa partida se emplaya o no (lo dice el
 * tipo de equipo del catálogo, y almacén puede corregirlo), y va marcando
 * preparado / emplayado hasta que todo queda listo y se firma la salida.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_types', function (Blueprint $table) {
            // Un accesorio o consumible no se emplaya; un equipo sí.
            $table->boolean('requiere_emplayado')->default(true)->after('description');
        });

        Schema::create('ordenes_salida', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->foreignId('venta_id')->unique()->constrained('ventas')->cascadeOnDelete();
            $table->string('estado', 20)->default('pendiente')->index();
            $table->text('notas')->nullable();

            $table->foreignId('preparada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('preparada_en')->nullable();

            // Salida firmada: quién entrega por almacén y quién se lo lleva.
            $table->foreignId('entregada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('entregada_en')->nullable();
            $table->string('recibe_nombre', 255)->nullable();
            $table->string('firma_entrega_path', 255)->nullable();
            $table->string('firma_recibe_path', 255)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('orden_salida_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_salida_id')->constrained('ordenes_salida')->cascadeOnDelete();
            $table->foreignId('venta_item_id')->nullable()->constrained('venta_items')->nullOnDelete();

            // Se congela el texto: si la venta se edita, la orden se resincroniza.
            $table->string('nombre', 255);
            $table->string('descripcion', 255)->nullable();
            $table->unsignedInteger('cantidad')->default(1);
            $table->text('no_series')->nullable();

            $table->boolean('requiere_emplayado')->default(true);
            $table->boolean('preparado')->default(false);
            $table->timestamp('preparado_en')->nullable();
            $table->boolean('emplayado')->default(false);
            $table->timestamp('emplayado_en')->nullable();
            $table->string('observaciones', 500)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_salida_items');
        Schema::dropIfExists('ordenes_salida');

        Schema::table('equipment_types', function (Blueprint $table) {
            $table->dropColumn('requiere_emplayado');
        });
    }
};

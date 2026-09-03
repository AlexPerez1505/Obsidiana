<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enlaza productos con el catalogo.
 *
 * Las columnas de texto (tipo_equipo, subtipo, marca, modelo) NO se eliminan:
 * mas de veinte vistas y PDFs de cotizaciones, facturas, paquetes y servicios
 * las leen directamente. El modelo Producto las mantiene sincronizadas a
 * partir de estas llaves, asi que ademas quedan como fotografia del nombre
 * que tenia el equipo cuando se emitio el documento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('equipment_type_id')->nullable()->after('id')
                ->constrained('equipment_types')->nullOnDelete();
            $table->foreignId('subtype_id')->nullable()->after('equipment_type_id')
                ->constrained('subtypes')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->after('subtype_id')
                ->constrained('brands')->nullOnDelete();
            $table->foreignId('equipment_model_id')->nullable()->after('brand_id')
                ->constrained('equipment_models')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipment_model_id');
            $table->dropConstrainedForeignId('brand_id');
            $table->dropConstrainedForeignId('subtype_id');
            $table->dropConstrainedForeignId('equipment_type_id');
        });
    }
};

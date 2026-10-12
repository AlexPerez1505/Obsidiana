<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La tabla `equipment` se agregó a 2026_08_29_000001_create_equipment_catalog_tables
 * después de que esa migración ya se había ejecutado en algunas bases de datos.
 * Como aquella migración está guardada con `hasTable`, nunca vuelve a correr.
 * Esta migración garantiza que `equipment` exista antes de que
 * 2026_08_29_000010_create_service_equipment_table le ponga llave foránea.
 * En bases donde ya existe, no hace nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('equipment')) {
            return;
        }

        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_type_id')->constrained('equipment_types')->cascadeOnDelete();
            $table->foreignId('subtype_id')->nullable()->constrained('subtypes')->nullOnDelete();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('equipment_model_id')->nullable()->constrained('equipment_models')->nullOnDelete();
            $table->string('type_description')->nullable();
            $table->string('subtype_description')->nullable();
            $table->string('brand_description')->nullable();
            $table->string('model_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // La tabla pertenece conceptualmente a create_equipment_catalog_tables;
        // ahí se elimina. Aquí no se borra para no perder datos al hacer rollback.
    }
};

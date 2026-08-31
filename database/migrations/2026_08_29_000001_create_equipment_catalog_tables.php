<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogo de equipo: tipo -> subtipo -> marca -> modelo.
 *
 * Los modelos Eloquent (EquipmentType, Subtype, Brand, EquipmentModel,
 * Equipment) y su controlador ya existian en el proyecto, pero nunca se
 * escribieron estas tablas. Esta migracion las crea.
 *
 * La marca depende del subtipo: una marca es global (Olympus es Olympus),
 * pero se declara a que subtipos aplica en la tabla pivote brand_subtype.
 * El modelo cuelga de la pareja subtipo + marca.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipment_types')) {
            Schema::create('equipment_types', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('subtypes')) {
            Schema::create('subtypes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('equipment_type_id')->constrained('equipment_types')->cascadeOnDelete();
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();

                // El mismo nombre puede repetirse entre tipos distintos (ej. "Adaptador"),
                // pero no dentro del mismo tipo.
                $table->unique(['equipment_type_id', 'name']);
            });
        }

        if (! Schema::hasTable('brands')) {
            Schema::create('brands', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('brand_subtype')) {
            Schema::create('brand_subtype', function (Blueprint $table) {
                $table->id();
                $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
                $table->foreignId('subtype_id')->constrained('subtypes')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['brand_id', 'subtype_id']);
            });
        }

        if (! Schema::hasTable('equipment_models')) {
            Schema::create('equipment_models', function (Blueprint $table) {
                $table->id();
                $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
                $table->foreignId('subtype_id')->nullable()->constrained('subtypes')->cascadeOnDelete();
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();

                $table->unique(['brand_id', 'subtype_id', 'name']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_models');
        Schema::dropIfExists('brand_subtype');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('subtypes');
        Schema::dropIfExists('equipment_types');
    }
};

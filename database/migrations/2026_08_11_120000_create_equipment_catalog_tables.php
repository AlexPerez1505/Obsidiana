<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        if (! Schema::hasTable('equipment_models')) {
            Schema::create('equipment_models', function (Blueprint $table) {
                $table->id();
                $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();
                $table->unique(['brand_id', 'name']);
            });
        }

        $this->seedCatalogFromProductos();
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_models');
        Schema::dropIfExists('subtypes');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('equipment_types');
    }

    private function seedCatalogFromProductos(): void
    {
        if (! Schema::hasTable('productos')) {
            return;
        }

        $productos = DB::table('productos')
            ->selectRaw('DISTINCT tipo_equipo, subtipo, marca, modelo')
            ->whereNotNull('tipo_equipo')
            ->where('tipo_equipo', '<>', '')
            ->get();

        foreach ($productos as $p) {
            $type = DB::table('equipment_types')->where('name', $p->tipo_equipo)->first();
            if (! $type) {
                $typeId = DB::table('equipment_types')->insertGetId([
                    'name' => $p->tipo_equipo,
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'id');
            } else {
                $typeId = $type->id;
            }

            if ($p->subtipo) {
                DB::table('subtypes')->insertOrIgnore([
                    'equipment_type_id' => $typeId,
                    'name' => $p->subtipo,
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (! $p->marca) {
                continue;
            }

            $brand = DB::table('brands')->where('name', $p->marca)->first();
            if (! $brand) {
                $brandId = DB::table('brands')->insertGetId([
                    'name' => $p->marca,
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'id');
            } else {
                $brandId = $brand->id;
            }

            if ($p->modelo) {
                DB::table('equipment_models')->insertOrIgnore([
                    'brand_id' => $brandId,
                    'name' => $p->modelo,
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fichas técnicas:
 *  - Se clasifican con el catálogo del equipo (tipo, subtipo, marca y
 *    modelo) y con eso se ligan solas al producto que coincide.
 *  - Notas y observaciones dirigidas a un usuario ("te equivocaste en…"),
 *    con su aviso y la marca de atendida.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fichas_tecnicas', function (Blueprint $table) {
            if (! Schema::hasColumn('fichas_tecnicas', 'equipment_type_id')) {
                $table->foreignId('equipment_type_id')->nullable()->after('paquete_id')->constrained('equipment_types')->nullOnDelete();
                $table->foreignId('subtype_id')->nullable()->after('equipment_type_id')->constrained('subtypes')->nullOnDelete();
                $table->foreignId('brand_id')->nullable()->after('subtype_id')->constrained('brands')->nullOnDelete();
                $table->foreignId('equipment_model_id')->nullable()->after('brand_id')->constrained('equipment_models')->nullOnDelete();
            }
        });

        // Las fichas que ya estaban ligadas a un producto toman su catálogo.
        DB::table('fichas_tecnicas as f')
            ->join('productos as p', 'p.id', '=', 'f.producto_id')
            ->whereNull('f.equipment_model_id')
            ->update([
                'f.equipment_type_id' => DB::raw('p.equipment_type_id'),
                'f.subtype_id' => DB::raw('p.subtype_id'),
                'f.brand_id' => DB::raw('p.brand_id'),
                'f.equipment_model_id' => DB::raw('p.equipment_model_id'),
            ]);

        if (! Schema::hasTable('ficha_observaciones')) {
            Schema::create('ficha_observaciones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ficha_tecnica_id')->constrained('fichas_tecnicas')->cascadeOnDelete();
                $table->foreignId('autor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('para_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('texto');
                $table->timestamp('atendida_en')->nullable();
                $table->foreignId('atendida_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['para_id', 'atendida_en']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ficha_observaciones');

        Schema::table('fichas_tecnicas', function (Blueprint $table) {
            foreach (['equipment_model_id', 'brand_id', 'subtype_id', 'equipment_type_id'] as $col) {
                if (Schema::hasColumn('fichas_tecnicas', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
        });
    }
};

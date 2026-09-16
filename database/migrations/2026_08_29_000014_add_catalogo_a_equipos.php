<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los equipos también salen del catálogo.
 *
 * Hasta hoy tipo, marca y modelo se escribían a mano en Equipos, mientras
 * que en Productos ya se elegían del catálogo. Eso significaba que la misma
 * torre podía quedar como "LAPAROSCOPIA" en un lado y "Laparoscopia" en el
 * otro, y no había forma de cruzarlas.
 *
 * Las columnas de texto se conservan porque cotizaciones, ventas, paquetes
 * y fichas técnicas leen $equipo->marca y compañía directamente, y los
 * documentos ya emitidos deben conservar el nombre que tenía ese día. El
 * modelo las mantiene sincronizadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            if (! Schema::hasColumn('equipos', 'equipment_type_id')) {
                $table->foreignId('equipment_type_id')->nullable()->after('id')
                    ->constrained('equipment_types')->nullOnDelete();
            }
            if (! Schema::hasColumn('equipos', 'subtype_id')) {
                $table->foreignId('subtype_id')->nullable()->after('equipment_type_id')
                    ->constrained('subtypes')->nullOnDelete();
            }
            if (! Schema::hasColumn('equipos', 'brand_id')) {
                $table->foreignId('brand_id')->nullable()->after('subtype_id')
                    ->constrained('brands')->nullOnDelete();
            }
            if (! Schema::hasColumn('equipos', 'equipment_model_id')) {
                $table->foreignId('equipment_model_id')->nullable()->after('brand_id')
                    ->constrained('equipment_models')->nullOnDelete();
            }

            // El subtipo no existía como texto; se agrega para que la ficha
            // del equipo lo pueda mostrar sin consultar el catálogo.
            if (! Schema::hasColumn('equipos', 'subtipo')) {
                $table->string('subtipo')->nullable()->after('tipo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            foreach (['equipment_type_id', 'subtype_id', 'brand_id', 'equipment_model_id'] as $column) {
                if (Schema::hasColumn('equipos', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
            if (Schema::hasColumn('equipos', 'subtipo')) {
                $table->dropColumn('subtipo');
            }
        });
    }
};

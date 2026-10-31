<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cotizacion_items')) {
            return;
        }

        Schema::table('cotizacion_items', function (Blueprint $table) {
            if (! Schema::hasColumn('cotizacion_items', 'equipo_id')) {
                $table->foreignId('equipo_id')->nullable()->after('cotizacion_id')
                    ->constrained('equipos')->nullOnDelete();
            }

            if (! Schema::hasColumn('cotizacion_items', 'tipo_item')) {
                $table->enum('tipo_item', ['equipo', 'paquete', 'producto'])->default('equipo')->after('paquete_id');
            }

            if (! Schema::hasColumn('cotizacion_items', 'modelo')) {
                $table->string('modelo')->nullable()->after('nombre');
            }

            if (! Schema::hasColumn('cotizacion_items', 'marca')) {
                $table->string('marca')->nullable()->after('modelo');
            }

            if (! Schema::hasColumn('cotizacion_items', 'imagen')) {
                $table->string('imagen')->nullable()->after('marca');
            }

            if (! Schema::hasColumn('cotizacion_items', 'precio_unitario')) {
                $table->decimal('precio_unitario', 12, 2)->default(0)->after('cantidad');
            }

            if (! Schema::hasColumn('cotizacion_items', 'orden')) {
                $table->unsignedInteger('orden')->default(0)->after('es_regalo');
            }
        });

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('cotizacion_items', 'tipo_item')) {
            DB::statement("ALTER TABLE cotizacion_items MODIFY tipo_item ENUM('equipo', 'paquete', 'producto') NOT NULL DEFAULT 'equipo'");
        }

        if (Schema::hasColumn('cotizacion_items', 'precio_unitario')) {
            if (Schema::hasColumn('cotizacion_items', 'precio_original')) {
                DB::table('cotizacion_items')
                    ->where('precio_unitario', 0)
                    ->update(['precio_unitario' => DB::raw('precio_original')]);
            } elseif (Schema::hasColumn('cotizacion_items', 'precio_final')) {
                DB::table('cotizacion_items')
                    ->where('precio_unitario', 0)
                    ->update(['precio_unitario' => DB::raw('precio_final')]);
            }
        }

        if (Schema::hasColumn('cotizacion_items', 'tipo_item')) {
            DB::statement("
                UPDATE cotizacion_items
                SET tipo_item = CASE
                    WHEN paquete_id IS NOT NULL THEN 'paquete'
                    WHEN producto_id IS NOT NULL THEN 'producto'
                    WHEN equipo_id IS NOT NULL THEN 'equipo'
                    ELSE tipo_item
                END
            ");
        }
    }

    public function down(): void
    {
        // No se eliminan columnas agregadas porque ya pueden contener datos de cotizaciones nuevas.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Números de serie únicos en todo el inventario.
 *
 * Hasta ahora el índice único era (producto_id, no_serie): dos productos
 * distintos —por ejemplo, el mismo subtipo/marca/modelo registrado en dos
 * filas— podían tener la misma serie. Se agrega una columna calculada con
 * la serie normalizada (MAYÚSCULAS, sin espacios a los lados, vacía = NULL)
 * y un índice único sobre ella, que aplica a cualquier camino que guarde.
 *
 * Si ya existen series repetidas no se toca ningún dato: la columna se crea
 * igual, el índice se deja pendiente y se avisa. `php artisan
 * inventario:series-duplicadas` las lista y, ya corregidas, crea el índice.
 *
 * También se crea la tabla de reservas del generador de series: lo que se
 * generó en una captura que aún no se guarda no se le vuelve a dar a otra.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('producto_seriales', 'no_serie_unico')) {
            DB::statement("ALTER TABLE producto_seriales ADD COLUMN no_serie_unico VARCHAR(255)
                GENERATED ALWAYS AS (NULLIF(UPPER(TRIM(no_serie)), '')) STORED AFTER no_serie");
        }

        $duplicadas = DB::table('producto_seriales')->whereNotNull('no_serie_unico')
            ->select('no_serie_unico')->groupBy('no_serie_unico')->havingRaw('COUNT(*) > 1')->count();

        if ($duplicadas === 0) {
            Schema::table('producto_seriales', fn (Blueprint $t) => $t->unique('no_serie_unico', 'producto_seriales_no_serie_unico_unique'));
        } else {
            echo "\n  AVISO: hay {$duplicadas} número(s) de serie repetido(s) en el inventario. El índice único quedó pendiente.\n"
                ."  Corrígelos y corre: php artisan inventario:series-duplicadas --indice\n";
        }

        Schema::create('serie_reservas', function (Blueprint $t) {
            $t->id();
            $t->string('serie', 255)->unique();
            $t->string('prefijo', 120)->index();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('expira_en')->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serie_reservas');
        if (collect(DB::select("SHOW INDEX FROM producto_seriales WHERE Key_name = 'producto_seriales_no_serie_unico_unique'"))->isNotEmpty()) {
            Schema::table('producto_seriales', fn (Blueprint $t) => $t->dropUnique('producto_seriales_no_serie_unico_unique'));
        }
        if (Schema::hasColumn('producto_seriales', 'no_serie_unico')) {
            Schema::table('producto_seriales', fn (Blueprint $t) => $t->dropColumn('no_serie_unico'));
        }
    }
};

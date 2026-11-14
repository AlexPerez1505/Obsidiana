<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las "categorías" sueltas dejan de existir para clientes, congresos y
 * promociones: todo se clasifica con el catálogo de equipo (tipo de equipo).
 *
 * La columna se sigue llamando `categoria_id` para no romper la API ni los
 * formularios, pero ahora apunta a `equipment_types`. Cada categoría vieja
 * se convierte en el tipo de equipo del mismo nombre (si no existe, se crea)
 * y los registros se re-apuntan a él, así no se pierde nada.
 */
return new class extends Migration
{
    private const TABLAS = ['clientes', 'congresos_eventos', 'promociones'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, fn (Blueprint $t) => $t->dropForeign(['categoria_id']));
        }

        // Un congreso ya no se borra en cascada si se quita su tipo de equipo.
        Schema::table('congresos_eventos', fn (Blueprint $t) => $t->unsignedBigInteger('categoria_id')->nullable()->change());

        // categoría vieja -> tipo de equipo
        $mapa = [];
        foreach (DB::table('categorias')->get(['id', 'nombre']) as $cat) {
            $nombre = mb_strtoupper(trim((string) $cat->nombre));
            if ($nombre === '') {
                continue;
            }

            $tipoId = DB::table('equipment_types')->whereRaw('UPPER(name) = ?', [$nombre])->value('id')
                ?? DB::table('equipment_types')->insertGetId([
                    'name' => $nombre,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            $mapa[$cat->id] = $tipoId;
        }

        // Fila por fila: los ids viejos y nuevos se pueden encimar.
        foreach (self::TABLAS as $tabla) {
            DB::table($tabla)->whereNotNull('categoria_id')->orderBy('id')->get(['id', 'categoria_id'])
                ->each(fn ($fila) => DB::table($tabla)->where('id', $fila->id)
                    ->update(['categoria_id' => $mapa[$fila->categoria_id] ?? null]));
        }

        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, fn (Blueprint $t) => $t->foreign('categoria_id')
                ->references('id')->on('equipment_types')->nullOnDelete());
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, fn (Blueprint $t) => $t->dropForeign(['categoria_id']));
            // Los tipos de equipo no tienen de regreso a qué categoría ir.
            DB::table($tabla)->update(['categoria_id' => null]);
            Schema::table($tabla, fn (Blueprint $t) => $t->foreign('categoria_id')
                ->references('id')->on('categorias')->nullOnDelete());
        }
    }
};

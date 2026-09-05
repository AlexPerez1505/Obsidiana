<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Equipo nuevo y equipo usado no se reciben igual.
 *
 * El usado necesita dejar por escrito en qué estado llegó, y cada pieza
 * física necesita su propia etiqueta para poder seguirla por los procesos
 * (revisión, hojalatería, mantenimiento) antes de poder venderse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->enum('condicion', ['nuevo', 'usado'])->default('nuevo')->after('item_name');
            // Respuestas del checklist de recepción; solo se llena en usados.
            $table->json('checklist_recepcion')->nullable()->after('notes');
            $table->string('estado_general', 20)->nullable()->after('checklist_recepcion');
        });

        Schema::table('producto_seriales', function (Blueprint $table) {
            // Etiqueta interna: es lo que va impreso en el QR pegado a la
            // pieza. Va aparte del número de serie del fabricante, que
            // puede no existir (accesorios) o venir repetido.
            $table->string('codigo', 30)->nullable()->unique()->after('producto_id');
            $table->enum('condicion', ['nuevo', 'usado'])->default('nuevo')->after('no_serie');
            $table->string('estado', 20)->default('disponible')->after('condicion');
            $table->index('estado');
        });

        // Lo que ya estaba registrado se toma como nuevo y disponible: es
        // como se venía comportando hasta hoy.
        DB::table('producto_seriales')->whereNull('codigo')->orderBy('id')->each(function ($fila) {
            DB::table('producto_seriales')->where('id', $fila->id)->update([
                'codigo' => 'MB-'.str_pad((string) $fila->id, 6, '0', STR_PAD_LEFT),
                'estado' => $fila->vendido ? 'vendido' : 'disponible',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn(['condicion', 'checklist_recepcion', 'estado_general']);
        });

        Schema::table('producto_seriales', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropUnique(['codigo']);
            $table->dropColumn(['codigo', 'condicion', 'estado']);
        });
    }
};

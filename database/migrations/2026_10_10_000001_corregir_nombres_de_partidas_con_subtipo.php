<?php

use App\Models\Producto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las partidas guardan una copia del nombre. Las creadas antes de usar el subtipo
 * quedaron con el tipo (p. ej. "ENDOSCOPIA VETERINARIA"). Se corrigen para que
 * muestren subtipo, marca y modelo del producto (GASTROSCOPIO · OLYMPUS · GIT-2378).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['cotizacion_items', 'venta_items'] as $tabla) {
            DB::table($tabla)->whereNotNull('producto_id')->orderBy('id')->each(function ($it) use ($tabla) {
                $p = Producto::find($it->producto_id);
                if (! $p || ! $p->subtipo) {
                    return;
                }
                DB::table($tabla)->where('id', $it->id)->update([
                    'nombre' => $p->nombreVisible(),
                    'marca' => $it->marca ?: $p->marca,
                    'modelo' => $it->modelo ?: $p->modelo,
                ]);
            });
        }

        // Renglones de órdenes de salida: la descripción es el nombre de la partida
        DB::table('orden_salida_items')->whereNotNull('venta_item_id')->orderBy('id')->each(function ($r) {
            $v = DB::table('venta_items')->where('id', $r->venta_item_id)->first();
            if ($v && $v->nombre) {
                DB::table('orden_salida_items')->where('id', $r->id)->update(['descripcion' => $v->nombre]);
            }
        });
    }

    public function down(): void
    {
    }
};

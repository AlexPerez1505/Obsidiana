<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separa "vendido" de "ya salió del almacén".
 *
 * El stock se descuenta al vender (para que nadie venda dos veces la misma
 * pieza), pero el equipo puede tardar días o semanas en salir: instalación
 * pendiente, el cliente no tiene listo el sitio, financiamiento en trámite.
 *
 * Con esta fecha, el movimiento nace como "Vendido" y pasa a "Salida"
 * cuando se firma la orden de salida, que es cuando de verdad se lo llevan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_movements', 'entregado_en')) {
                $table->timestamp('entregado_en')->nullable()->after('movement_date');
            }
        });

        $this->rellenarHistorico();
    }

    /**
     * Las salidas que ya existen no pueden quedar como falsos pendientes:
     *   - Si su orden de salida ya se firmó, se usa esa fecha.
     *   - Si no hay orden (ventas de antes de que existieran), se toma la
     *     fecha del movimiento: son historia, no pendientes de almacén.
     *   - Solo se dejan nulas las que tienen orden sin firmar, porque esas
     *     de verdad están pendientes.
     */
    private function rellenarHistorico(): void
    {
        if (! Schema::hasTable('ordenes_salida')) {
            DB::table('inventory_movements')
                ->where('movement_type', 'salida')
                ->whereNull('entregado_en')
                ->update(['entregado_en' => DB::raw('movement_date')]);

            return;
        }

        $salidas = DB::table('inventory_movements')
            ->where('movement_type', 'salida')
            ->whereNull('entregado_en')
            ->get(['id', 'reference', 'movement_date']);

        foreach ($salidas as $salida) {
            $orden = $salida->reference
                ? DB::table('ordenes_salida')
                    ->join('ventas', 'ventas.id', '=', 'ordenes_salida.venta_id')
                    ->where('ventas.folio', $salida->reference)
                    ->select('ordenes_salida.estado', 'ordenes_salida.entregada_en')
                    ->first()
                : null;

            // Con orden pendiente se queda nula: es un pendiente real.
            if ($orden && $orden->estado !== 'entregada') {
                continue;
            }

            DB::table('inventory_movements')
                ->where('id', $salida->id)
                ->update(['entregado_en' => $orden?->entregada_en ?: $salida->movement_date]);
        }
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn('entregado_en');
        });
    }
};

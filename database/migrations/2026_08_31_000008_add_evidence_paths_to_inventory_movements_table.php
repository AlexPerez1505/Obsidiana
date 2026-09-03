<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fotos que documentan cómo llegó un lote (caja, factura del proveedor,
     * estado del equipo...). Es evidencia del envío completo, no por unidad.
     */
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->json('evidence_paths')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn('evidence_paths');
        });
    }
};

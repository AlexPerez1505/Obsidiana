<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto individual de esta unidad (antes solo existía la evidencia del
     * lote completo en inventory_movements.evidence_paths). Se captura al
     * momento de la entrada, cuando el producto es_serializado.
     */
    public function up(): void
    {
        Schema::table('producto_seriales', function (Blueprint $table) {
            $table->string('foto_path')->nullable()->after('no_serie');
        });
    }

    public function down(): void
    {
        Schema::table('producto_seriales', function (Blueprint $table) {
            $table->dropColumn('foto_path');
        });
    }
};

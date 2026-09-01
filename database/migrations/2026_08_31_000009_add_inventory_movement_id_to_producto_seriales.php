<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Liga cada unidad con la entrada que la trajo, para poder ver la
     * evidencia (fotos del lote) de cómo llegó esa unidad específica.
     */
    public function up(): void
    {
        Schema::table('producto_seriales', function (Blueprint $table) {
            $table->foreignId('inventory_movement_id')->nullable()
                ->after('venta_item_id')
                ->constrained('inventory_movements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('producto_seriales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_movement_id');
        });
    }
};

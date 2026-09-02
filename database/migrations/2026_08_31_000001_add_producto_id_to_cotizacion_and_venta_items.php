<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizacion_items', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->after('paquete_id')->constrained('productos')->nullOnDelete();
        });

        Schema::table('venta_items', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->after('paquete_id')->constrained('productos')->nullOnDelete();
        });

        // MySQL no permite ALTER de enum sin redefinirlo por completo.
        DB::statement("ALTER TABLE cotizacion_items MODIFY tipo_item ENUM('equipo', 'paquete', 'producto') NOT NULL DEFAULT 'equipo'");
        DB::statement("ALTER TABLE venta_items MODIFY tipo_item ENUM('equipo', 'paquete', 'producto') NOT NULL DEFAULT 'equipo'");
    }

    public function down(): void
    {
        Schema::table('cotizacion_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('producto_id');
        });

        Schema::table('venta_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('producto_id');
        });

        DB::statement("ALTER TABLE cotizacion_items MODIFY tipo_item ENUM('equipo', 'paquete') NOT NULL DEFAULT 'equipo'");
        DB::statement("ALTER TABLE venta_items MODIFY tipo_item ENUM('equipo', 'paquete') NOT NULL DEFAULT 'equipo'");
    }
};

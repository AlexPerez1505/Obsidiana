<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mismo caso que productos: el precio de venta lo define un administrador,
 * así que un equipo dado de alta por alguien más queda sin precio hasta
 * que un admin lo ponga. Con NOT NULL el guardado tronaba.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE equipos MODIFY precio DECIMAL(12,2) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE equipos SET precio = 0 WHERE precio IS NULL');
        DB::statement("ALTER TABLE equipos MODIFY precio DECIMAL(12,2) NOT NULL DEFAULT '0.00'");
    }
};

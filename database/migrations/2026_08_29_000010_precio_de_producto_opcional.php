<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El precio deja de ser obligatorio en la fila del producto.
 *
 * Quien no es administrador ya no captura precio al registrar una entrada,
 * así que un modelo nuevo puede quedar sin precio hasta que un admin lo
 * defina. Con el default de 0.00 no había forma de distinguir "todavía no
 * tiene precio" de "no cuesta nada".
 */
return new class extends Migration
{
    public function up(): void
    {
        // Se hace en SQL directo para no depender de doctrine/dbal.
        DB::statement('ALTER TABLE productos MODIFY precio DECIMAL(12,2) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE productos SET precio = 0 WHERE precio IS NULL");
        DB::statement("ALTER TABLE productos MODIFY precio DECIMAL(12,2) NOT NULL DEFAULT '0.00'");
    }
};

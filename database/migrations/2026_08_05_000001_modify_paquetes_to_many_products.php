<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Paquetes: relacion muchos a muchos con productos
|--------------------------------------------------------------------------
| Originalmente esta migracion asumia que `paquetes` traia la columna
| producto_id (esquema viejo). La tabla vigente la crea
| 2026_08_02_000002_create_paquetes_table.php y ya no la tiene, asi que el
| drop se hace solo si la columna existe. Asi corre igual en una base nueva
| y en una que venia del esquema anterior.
*/

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('paquetes', 'producto_id')) {
            Schema::table('paquetes', function (Blueprint $table) {
                // La FK puede no existir aunque la columna si; por eso va aparte.
                try {
                    $table->dropForeign(['producto_id']);
                } catch (\Throwable $e) {
                    // No habia llave foranea que eliminar.
                }
            });

            Schema::table('paquetes', function (Blueprint $table) {
                $table->dropColumn('producto_id');
            });
        }

        // Tabla pivote para la relacion muchos a muchos
        if (! Schema::hasTable('paquete_producto')) {
            Schema::create('paquete_producto', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('paquete_id');
                $table->unsignedBigInteger('producto_id');
                $table->integer('cantidad')->default(1);
                $table->timestamps();

                $table->foreign('paquete_id')->references('id')->on('paquetes')->onDelete('cascade');
                $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');

                $table->unique(['paquete_id', 'producto_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('paquete_producto');

        if (! Schema::hasColumn('paquetes', 'producto_id')) {
            Schema::table('paquetes', function (Blueprint $table) {
                $table->unsignedBigInteger('producto_id')->nullable();
                $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            });
        }
    }
};

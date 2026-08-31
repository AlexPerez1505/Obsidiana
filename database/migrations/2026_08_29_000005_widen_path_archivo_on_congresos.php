<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un congreso trae varios documentos (manual del expositor, planos, kit de
 * patrocinio...), y el listado ya los pintaba como lista. La columna era
 * varchar(255), donde no cabe el JSON de varias rutas: se pasa a text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congresos_eventos', function (Blueprint $table) {
            $table->text('path_archivo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('congresos_eventos', function (Blueprint $table) {
            $table->string('path_archivo', 255)->nullable()->change();
        });
    }
};

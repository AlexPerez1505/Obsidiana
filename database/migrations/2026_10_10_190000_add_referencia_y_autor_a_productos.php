<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Dos datos nuevos del producto:
|
|   - referencia: opcional, la REF o número de parte del fabricante.
|   - created_by: quién lo dio de alta. Con eso quien lo registró puede
|     corregirlo o borrarlo durante sus primeras horas aunque no tenga
|     permiso de editar inventario (ver App\Support\EdicionPropia). Los
|     productos de antes quedan en null: para ellos no hay plazo propio.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('referencia', 120)->nullable()->after('descripcion');
            $table->foreignId('created_by')->nullable()->after('es_serializado')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('referencia');
        });
    }
};

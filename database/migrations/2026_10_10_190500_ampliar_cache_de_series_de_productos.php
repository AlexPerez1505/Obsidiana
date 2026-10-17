<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| productos.no_serie es un caché de búsqueda: Producto::recalcularStock()
| junta ahí las series de todas las piezas sin vender ("A1, A2, A3…").
|
| Como varchar(255) alcanzaba para unas 20 series: al registrar más piezas
| con serie de un mismo modelo (100 de un jalón, por ejemplo) el guardado
| tronaba con "Data too long for column 'no_serie'" y la entrada no se
| registraba. Como texto caben miles.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->text('no_serie')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('no_serie')->nullable()->change();
        });
    }
};

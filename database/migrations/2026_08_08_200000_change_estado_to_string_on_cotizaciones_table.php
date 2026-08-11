<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cambia 'estado' de boolean (vigente/cerrada) a string con dos valores posibles:
     * 'cotizacion' (solo presupuesto) o 'remision' (venta definitiva con seguimiento de pagos).
     */
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn('estado');
        });

        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->string('estado')->default('cotizacion')->after('plan_pago_id');
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn('estado');
        });

        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->boolean('estado')->default(true)->after('plan_pago_id');
        });
    }
};

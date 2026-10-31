<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recepción del equipo: en qué lo entregaron y si trae accesorios.
 *
 * Al registrar un servicio se necesita saber si el equipo llegó en maletín,
 * estuche, contenedor u otra cosa (queda registro de cómo entró), y si el
 * cliente incluyó accesorios, con detalle de cuáles, para que al devolverlo
 * no haya dudas de qué se recibió.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_equipment', function (Blueprint $table) {
            $table->string('transport_case', 20)->nullable()->after('observations');
            $table->string('transport_case_other')->nullable()->after('transport_case');
            $table->boolean('accessories_included')->nullable()->after('transport_case_other');
            $table->string('accessories_detail')->nullable()->after('accessories_included');
        });
    }

    public function down(): void
    {
        Schema::table('service_equipment', function (Blueprint $table) {
            $table->dropColumn(['transport_case', 'transport_case_other', 'accessories_included', 'accessories_detail']);
        });
    }
};

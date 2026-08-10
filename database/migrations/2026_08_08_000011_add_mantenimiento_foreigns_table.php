<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist', function (Blueprint $table) {
            $table->foreign('id_mantenimiento')->references('id')->on('mantenimiento_interno')->nullOnDelete();
        });

        Schema::table('cotizacion_interna_detalle', function (Blueprint $table) {
            $table->foreign('id_mantenimiento')->references('id')->on('mantenimiento_interno')->nullOnDelete();
        });

        Schema::table('cotizacion_externa_detalle', function (Blueprint $table) {
            $table->foreign('id_mantenimiento')->references('id')->on('mantenimiento_interno')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('checklist', function (Blueprint $table) {
            $table->dropForeign(['id_mantenimiento']);
        });

        Schema::table('cotizacion_interna_detalle', function (Blueprint $table) {
            $table->dropForeign(['id_mantenimiento']);
        });

        Schema::table('cotizacion_externa_detalle', function (Blueprint $table) {
            $table->dropForeign(['id_mantenimiento']);
        });
    }
};

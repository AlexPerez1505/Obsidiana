<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quién va a preparar el equipo vendido: se asigna en la orden de salida
 * y a esa persona le llega el aviso por correo y en la campana.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_salida', function (Blueprint $table) {
            if (! Schema::hasColumn('ordenes_salida', 'asignada_a')) {
                $table->foreignId('asignada_a')->nullable()->after('estado')->constrained('users')->nullOnDelete();
                $table->foreignId('asignada_por')->nullable()->after('asignada_a')->constrained('users')->nullOnDelete();
                $table->timestamp('asignada_en')->nullable()->after('asignada_por');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_salida', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_salida', 'asignada_a')) {
                $table->dropConstrainedForeignId('asignada_por');
                $table->dropConstrainedForeignId('asignada_a');
                $table->dropColumn('asignada_en');
            }
        });
    }
};

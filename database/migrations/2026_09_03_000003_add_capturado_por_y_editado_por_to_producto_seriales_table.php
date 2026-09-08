<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Auditoría ligera por unidad: quién la capturó y, si se corrigió el
     * serial o la foto después, quién hizo el último cambio. Alcanza con
     * el último editor + updated_at; si algún día se necesita el historial
     * completo de ediciones, se agrega una tabla aparte sin tocar esta.
     */
    public function up(): void
    {
        Schema::table('producto_seriales', function (Blueprint $table) {
            $table->foreignId('capturado_por')->nullable()->after('inventory_movement_id')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('editado_por')->nullable()->after('capturado_por')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('producto_seriales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('capturado_por');
            $table->dropConstrainedForeignId('editado_por');
        });
    }
};

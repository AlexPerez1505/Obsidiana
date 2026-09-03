<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fichas_tecnicas', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->after('equipo_id')->constrained('productos')->nullOnDelete();
            $table->foreignId('paquete_id')->nullable()->after('producto_id')->constrained('paquetes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fichas_tecnicas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('producto_id');
            $table->dropConstrainedForeignId('paquete_id');
        });
    }
};

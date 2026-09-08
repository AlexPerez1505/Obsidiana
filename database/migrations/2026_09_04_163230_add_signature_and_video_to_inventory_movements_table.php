<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Firma digital de quien registró la entrada (imagen del canvas) y
     * video de verificación del estado del producto.
     */
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('evidence_paths');
            $table->string('video_path')->nullable()->after('signature_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn(['signature_path', 'video_path']);
        });
    }
};

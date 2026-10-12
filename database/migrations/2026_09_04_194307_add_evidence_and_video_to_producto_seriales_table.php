<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Se revisa columna por columna: esta base ya ha quedado a medias
        // antes (migración registrada sin aplicarse, y al revés), y así
        // volver a correrla no truena.
        Schema::table('producto_seriales', function (Blueprint $table) {
            if (! Schema::hasColumn('producto_seriales', 'evidence_paths')) {
                $table->json('evidence_paths')->nullable()->after('foto_path');
            }

            if (! Schema::hasColumn('producto_seriales', 'video_path')) {
                $table->string('video_path')->nullable()->after('evidence_paths');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_seriales', function (Blueprint $table) {
            $table->dropColumn(['evidence_paths', 'video_path']);
        });
    }
};

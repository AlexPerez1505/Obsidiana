<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            // "subtipo" ya lo agregó 2026_08_29_000014_add_catalogo_a_equipos.
            if (! Schema::hasColumn('equipos', 'serie')) {
                $table->string('serie')->nullable()->after('modelo');
            }
            if (! Schema::hasColumn('equipos', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('descripcion');
            }
            if (! Schema::hasColumn('equipos', 'evidencia_2_path')) {
                $table->string('evidencia_2_path')->nullable()->after('imagen');
            }
            if (! Schema::hasColumn('equipos', 'evidencia_3_path')) {
                $table->string('evidencia_3_path')->nullable()->after('evidencia_2_path');
            }
            if (! Schema::hasColumn('equipos', 'video_path')) {
                $table->string('video_path')->nullable()->after('evidencia_3_path');
            }
            if (! Schema::hasColumn('equipos', 'firma')) {
                $table->longText('firma')->nullable()->after('video_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn(['serie', 'observaciones', 'evidencia_2_path', 'evidencia_3_path', 'video_path', 'firma']);
        });
    }
};

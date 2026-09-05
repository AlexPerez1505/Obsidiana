<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('subtipo')->nullable()->after('tipo');
            $table->string('serie')->nullable()->after('modelo');
            $table->text('observaciones')->nullable()->after('descripcion');
            $table->string('evidencia_2_path')->nullable()->after('imagen');
            $table->string('evidencia_3_path')->nullable()->after('evidencia_2_path');
            $table->string('video_path')->nullable()->after('evidencia_3_path');
            $table->longText('firma')->nullable()->after('video_path');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn(['subtipo', 'serie', 'observaciones', 'evidencia_2_path', 'evidencia_3_path', 'video_path', 'firma']);
        });
    }
};

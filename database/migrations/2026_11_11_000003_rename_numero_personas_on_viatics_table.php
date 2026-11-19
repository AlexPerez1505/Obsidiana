<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viatics', function (Blueprint $table) {
            $table->renameColumn('numero_personas', 'personas_viaje');
        });

        Schema::table('viatics', function (Blueprint $table) {
            $table->text('personas_viaje')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('viatics', function (Blueprint $table) {
            $table->unsignedInteger('numero_personas')->nullable()->after('place');
        });

        Schema::table('viatics', function (Blueprint $table) {
            $table->dropColumn('personas_viaje');
        });
    }
};

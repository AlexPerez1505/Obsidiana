<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viatics', function (Blueprint $table) {
            $table->unsignedInteger('numero_personas')->nullable()->after('place');
            $table->unsignedInteger('dias_hospedaje')->nullable()->after('lodging');
            $table->date('fecha_ida')->nullable()->after('expense_date');
            $table->date('fecha_regreso')->nullable()->after('fecha_ida');
        });
    }

    public function down(): void
    {
        Schema::table('viatics', function (Blueprint $table) {
            $table->dropColumn(['numero_personas', 'dias_hospedaje', 'fecha_ida', 'fecha_regreso']);
        });
    }
};

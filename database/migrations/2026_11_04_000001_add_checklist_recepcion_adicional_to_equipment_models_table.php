<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_models', function (Blueprint $table) {
            $table->json('checklist_recepcion_adicional')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('equipment_models', function (Blueprint $table) {
            $table->dropColumn('checklist_recepcion_adicional');
        });
    }
};

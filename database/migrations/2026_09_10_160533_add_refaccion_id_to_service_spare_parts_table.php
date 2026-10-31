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
        Schema::table('service_spare_parts', function (Blueprint $table) {
            $table->foreignId('refaccion_id')->nullable()->after('service_id')->constrained('refacciones')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_spare_parts', function (Blueprint $table) {
            $table->dropForeign(['refaccion_id']);
            $table->dropColumn('refaccion_id');
        });
    }
};

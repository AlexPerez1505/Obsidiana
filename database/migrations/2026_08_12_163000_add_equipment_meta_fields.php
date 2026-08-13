<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('base_serial', 150)->nullable()->after('serial_number');
            $table->date('acquisition_date')->nullable()->after('description');
            $table->string('registered_by', 150)->nullable()->after('acquisition_date');
            $table->text('observations')->nullable()->after('registered_by');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn(['base_serial', 'acquisition_date', 'registered_by', 'observations']);
        });
    }
};

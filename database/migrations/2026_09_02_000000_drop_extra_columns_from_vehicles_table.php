<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'engine_type',
        'fuel_type',
        'load_capacity',
        'mileage',
        'fuel_efficiency',
        'tank_cost',
        'acquisition_date',
        'last_maintenance',
        'next_maintenance',
        'last_verification',
        'next_verification',
    ];

    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                if (Schema::hasColumn('vehicles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('engine_type', 50)->nullable();
            $table->string('fuel_type', 30)->nullable();
            $table->decimal('load_capacity', 10, 2)->nullable();
            $table->integer('mileage')->nullable();
            $table->decimal('fuel_efficiency', 10, 2)->nullable();
            $table->decimal('tank_cost', 10, 2)->nullable();
            $table->date('acquisition_date')->nullable();
            $table->date('last_maintenance')->nullable();
            $table->date('next_maintenance')->nullable();
            $table->date('last_verification')->nullable();
            $table->date('next_verification')->nullable();
        });
    }
};

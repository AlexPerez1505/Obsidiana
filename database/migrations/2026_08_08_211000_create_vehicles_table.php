<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicles')) {
            return;
        }

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 20)->unique();
            $table->string('vin', 50)->nullable();
            $table->string('brand', 50)->nullable();
            $table->string('model', 50)->nullable();
            $table->integer('year')->nullable();
            $table->string('color', 50)->nullable();
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
            $table->json('photos')->nullable();
            $table->string('circulation_card_doc')->nullable();
            $table->string('verification_doc')->nullable();
            $table->string('tenancy_doc')->nullable();
            $table->string('insurance_doc')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

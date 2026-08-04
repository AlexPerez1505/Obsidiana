<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number')->unique();
            $table->string('vin')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->integer('year')->nullable();
            $table->string('color')->nullable();
            $table->string('engine_type')->nullable();
            $table->string('fuel_type')->nullable();
            $table->decimal('load_capacity', 8, 2)->nullable();
            $table->integer('mileage')->default(0);
            $table->decimal('fuel_efficiency', 6, 2)->nullable();
            $table->decimal('tank_cost', 8, 2)->nullable();
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
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

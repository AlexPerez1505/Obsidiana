<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained('services')->cascadeOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('equipment_type')->nullable();
            $table->string('equipment_subtype')->nullable();
            $table->string('equipment_brand')->nullable();
            $table->string('equipment_model')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('description')->nullable();
            $table->text('observations')->nullable();
            $table->string('technician_name')->nullable();
            $table->text('report')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_reports');
    }
};

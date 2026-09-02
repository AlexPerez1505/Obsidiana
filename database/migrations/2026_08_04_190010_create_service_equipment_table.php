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
        Schema::create('service_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->nullable()->unique();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment');
            $table->foreignId('equipment_type_id')->nullable()->constrained('equipment_types');
            $table->foreignId('subtype_id')->nullable()->constrained('subtypes');
            $table->foreignId('brand_id')->nullable()->constrained('brands');
            $table->foreignId('equipment_model_id')->nullable()->constrained('equipment_models');
            $table->string('type_text')->nullable();
            $table->string('subtype_text')->nullable();
            $table->string('brand_text')->nullable();
            $table->string('model_text')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('description')->nullable();
            $table->text('observations')->nullable();
            $table->string('evidence_1_path')->nullable();
            $table->string('evidence_2_path')->nullable();
            $table->string('evidence_3_path')->nullable();
            $table->string('video_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_equipment');
    }
};

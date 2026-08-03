<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_type_id')->constrained('equipment_types')->cascadeOnDelete();
            $table->foreignId('subtype_id')->constrained('subtypes')->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('equipment_model_id')->constrained('equipment_models')->cascadeOnDelete();
            $table->string('type_description', 120)->nullable();
            $table->string('subtype_description', 120)->nullable();
            $table->string('brand_description', 120)->nullable();
            $table->string('model_description', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};

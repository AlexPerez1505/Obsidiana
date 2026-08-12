<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('subtypes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_type_id')->constrained('equipment_types')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('description')->nullable();
            $table->timestamps();
            $table->unique(['equipment_type_id', 'name']);
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('equipment_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('description')->nullable();
            $table->timestamps();
            $table->unique(['brand_id', 'name']);
        });

        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->foreignId('equipment_type_id')->nullable()->constrained('equipment_types')->nullOnDelete();
            $table->foreignId('subtype_id')->nullable()->constrained('subtypes')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('equipment_model_id')->nullable()->constrained('equipment_models')->nullOnDelete();
            $table->string('serial_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('stock_current')->default(0);
            $table->unsignedInteger('stock_max')->default(0);
            $table->unsignedInteger('stock_min')->default(0);
            $table->string('warehouse', 100)->nullable();
            $table->string('assigned_to', 150)->nullable();
            $table->string('department', 100)->nullable();
            $table->date('service_date')->nullable();
            $table->date('next_maintenance')->nullable();
            $table->text('notes')->nullable();
            $table->string('voltage', 50)->nullable();
            $table->string('frequency', 50)->nullable();
            $table->string('power', 50)->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('dimensions', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->text('technical_specs')->nullable();
            $table->string('supplier', 150)->nullable();
            $table->string('contact', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status', 30)->default('Activo')->index();
            $table->string('thumb', 40)->default('tower');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('equipment_models');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('subtypes');
        Schema::dropIfExists('equipment_types');
    }
};

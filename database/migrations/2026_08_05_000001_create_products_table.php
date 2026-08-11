<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('category', 100)->nullable();
            $table->string('unit', 30)->default('Pza');
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('stock_current')->default(0);
            $table->unsignedInteger('stock_max')->default(0);
            $table->unsignedInteger('stock_min')->default(0);
            $table->string('warehouse', 100)->nullable();
            $table->string('type', 100)->nullable();
            $table->string('technical_category', 100)->nullable();
            $table->text('specifications')->nullable();
            $table->string('supplier', 150)->nullable();
            $table->string('supplier_code', 100)->nullable();
            $table->string('location', 150)->nullable();
            $table->string('warranty', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('Activo')->index();
            $table->string('thumb', 40)->default('scope');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

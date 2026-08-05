<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->enum('movement_type', ['entrada', 'salida', 'transferencia']);
            $table->enum('item_type', ['producto', 'equipo']);
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_code', 40)->nullable();
            $table->string('item_name');
            $table->string('warehouse');
            $table->string('destination_warehouse')->nullable();
            $table->unsignedInteger('quantity');
            $table->string('unit', 20)->default('Pza');
            $table->unsignedInteger('stock_before')->nullable();
            $table->unsignedInteger('stock_after')->nullable();
            $table->string('reference')->nullable();
            $table->string('supplier')->nullable();
            $table->date('movement_date');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['movement_type', 'movement_date']);
            $table->index(['item_type', 'item_id']);
            $table->index('warehouse');
            $table->index('destination_warehouse');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};

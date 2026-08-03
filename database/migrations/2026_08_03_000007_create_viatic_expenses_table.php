<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viatic_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viatic_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('label');
            $table->decimal('amount', 8, 2);
            $table->string('icon')->default('receipt');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viatic_expenses');
    }
};

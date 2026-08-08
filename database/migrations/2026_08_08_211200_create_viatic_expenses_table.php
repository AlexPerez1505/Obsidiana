<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('viatic_expenses')) {
            return;
        }

        Schema::create('viatic_expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('viatic_id')->constrained('viatics')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('label')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('icon', 30)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viatic_expenses');
    }
};

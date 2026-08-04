<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viatics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vehicle_name')->nullable();
            $table->string('place')->nullable();
            $table->decimal('tolls', 8, 2)->default(0);
            $table->decimal('fuel', 8, 2)->default(0);
            $table->decimal('meals', 8, 2)->default(0);
            $table->decimal('additional', 8, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('ticket_photo')->nullable();
            $table->date('expense_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viatics');
    }
};

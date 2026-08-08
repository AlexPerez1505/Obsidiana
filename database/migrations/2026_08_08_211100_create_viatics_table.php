<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('viatics')) {
            return;
        }

        Schema::create('viatics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('vehicle_name', 100)->nullable();

            $table->string('place')->nullable();
            $table->decimal('tolls', 12, 2)->default(0);
            $table->decimal('fuel', 12, 2)->default(0);
            $table->decimal('meals', 12, 2)->default(0);
            $table->decimal('additional', 12, 2)->default(0);

            $table->text('description')->nullable();
            $table->string('ticket_photo')->nullable();
            $table->date('expense_date')->nullable();
            $table->string('status', 20)->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viatics');
    }
};

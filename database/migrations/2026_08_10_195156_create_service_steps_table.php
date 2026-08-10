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
        Schema::create('service_steps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('purpose');
            $table->unsignedInteger('order')->default(0);
            $table->enum('service_type', ['interno', 'externo'])->default('externo');
            $table->boolean('requires_qr')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_steps');
    }
};

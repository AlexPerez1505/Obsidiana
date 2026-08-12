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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_number')->nullable()->unique();
            $table->foreignId('customer_id')->constrained('clientes');
            $table->enum('service_type', ['interno', 'externo']);
            $table->foreignId('internal_technician_id')->nullable()->constrained('users');
            $table->foreignId('external_technician_id')->nullable()->constrained('tecnico_externo');
            $table->foreignId('registered_by')->constrained('users');
            $table->foreignId('current_step_id')->nullable()->constrained('service_steps');
            $table->string('qr_token')->nullable()->unique();
            $table->timestamp('qr_expires_at')->nullable();
            $table->longText('signature')->nullable();
            $table->enum('status', ['registrado','en_progreso','validado','entregado','cancelado'])->default('registrado');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};

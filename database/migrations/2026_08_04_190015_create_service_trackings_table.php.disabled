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
        Schema::create('service_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('service_step_id')->constrained('service_steps');
            $table->enum('status', ['pendiente','iniciado','completado','rechazado'])->default('pendiente');
            $table->foreignId('performed_by')->nullable()->constrained('users');
            $table->string('qr_token')->nullable()->unique();
            $table->timestamp('qr_expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('evidence_1_path')->nullable();
            $table->string('evidence_2_path')->nullable();
            $table->string('evidence_3_path')->nullable();
            $table->string('video_path')->nullable();
            $table->longText('signature')->nullable();
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
        Schema::dropIfExists('service_trackings');
    }
};

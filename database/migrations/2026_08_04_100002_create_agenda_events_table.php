<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agenda_events')) {
            Schema::create('agenda_events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('event_type', 40)->default('training');
                $table->date('start_date');
                $table->date('end_date');
                $table->time('start_time')->nullable();
                $table->json('participants')->nullable();
                $table->text('notes')->nullable();
                $table->string('status', 30)->default('programado');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['start_date', 'end_date']);
                $table->index(['event_type', 'status']);
            });
        }

        if (! Schema::hasTable('agenda_event_days')) {
            Schema::create('agenda_event_days', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agenda_event_id')->constrained('agenda_events')->cascadeOnDelete();
                $table->date('event_date')->unique();
                $table->timestamps();

                $table->index('agenda_event_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_event_days');
        Schema::dropIfExists('agenda_events');
    }
};

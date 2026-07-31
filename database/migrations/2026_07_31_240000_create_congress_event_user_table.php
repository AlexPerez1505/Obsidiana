<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('congress_event_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('congress_event_id')->constrained('congress_events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['congress_event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('congress_event_user');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qué usuarios del sistema (no externos) están ligados a un congreso.
 *
 * El modelo Congress ya declaraba esta relación (notifiedUsers), pero la
 * tabla nunca llegó a crearse: la migración que la traía de nombre viejo
 * (congress_event_user) está deshabilitada. Esta la crea de una vez con
 * el nombre que el modelo espera.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evento_congreso_usuario')) {
            return;
        }

        Schema::create('evento_congreso_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('congress_event_id')->constrained('congresos_eventos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['congress_event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_congreso_usuario');
    }
};

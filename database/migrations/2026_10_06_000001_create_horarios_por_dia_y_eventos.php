<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Horarios distintos por día de la semana (ej. sábados de 9 a 10) y
 * eventos de empresa: vacaciones de todo el equipo, comidas o salidas donde
 * no se checa, y días con horario especial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_schedules', function (Blueprint $table) {
            // {"1": {"in": "09:00", "out": "18:00"}, "6": {"in": "09:00", "out": "10:00"}}
            // Un día sin entrada aquí usa time_in/time_out (el horario general).
            $table->json('day_hours')->nullable()->after('weekdays');
        });

        Schema::create('company_events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('type', 20);                 // descanso | sin_checar | horario_especial
            $table->date('date_from');
            $table->date('date_to');
            $table->string('scope', 12)->default('todos');   // todos | seleccion
            $table->json('user_ids')->nullable();
            $table->time('time_in')->nullable();        // solo horario_especial
            $table->time('time_out')->nullable();
            $table->unsignedSmallInteger('tolerance_minutes')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date_from', 'date_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_events');
        Schema::table('employee_schedules', fn (Blueprint $t) => $t->dropColumn('day_hours'));
    }
};

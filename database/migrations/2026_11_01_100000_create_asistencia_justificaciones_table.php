<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Justificaciones de asistencia: RH le pide a alguien que explique un día
 * sin registro o sin salida, la persona responde desde su notificación y
 * RH la acepta o la rechaza. Una por persona y día.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencia_justificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('work_date');
            // sin_registro | sin_salida
            $table->string('problema', 30);
            // Lo que se le dijo, tal cual: "Entró 12:58 y no registró salida".
            $table->string('detalle', 255);
            $table->foreignId('solicitada_por')->nullable()->constrained('users')->nullOnDelete();

            // pendiente → respondida → aceptada | rechazada
            $table->string('estado', 20)->default('pendiente');
            $table->text('motivo')->nullable();
            $table->time('hora_entrada')->nullable();
            $table->time('hora_salida')->nullable();
            $table->timestamp('respondida_en')->nullable();

            $table->foreignId('revisada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisada_en')->nullable();
            $table->string('nota_revision', 255)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'work_date']);
            $table->index(['work_date', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia_justificaciones');
    }
};

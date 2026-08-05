<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['asistencia', 'falta', 'vacaciones', 'permiso', 'incidencia']);
            $table->enum('status', ['pendiente', 'validada', 'aprobada', 'justificar', 'revision', 'rechazada', 'cancelada'])->default('pendiente');
            $table->string('area')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->unsignedSmallInteger('late_minutes')->nullable();
            $table->text('detail')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'start_date']);
            $table->index(['type', 'status']);
            $table->index('area');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_reports');
    }
};

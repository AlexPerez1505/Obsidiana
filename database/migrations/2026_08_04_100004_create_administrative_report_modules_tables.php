<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_attendances')) {
            Schema::create('employee_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('attendance_date');
                $table->time('check_in')->nullable();
                $table->time('check_out')->nullable();
                $table->unsignedSmallInteger('late_minutes')->default(0);
                $table->string('status', 30)->default('pendiente');
                $table->text('notes')->nullable();
                $table->timestamp('validated_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['user_id', 'attendance_date']);
                $table->index(['status', 'attendance_date']);
            });
        }

        if (! Schema::hasTable('employee_absences')) {
            Schema::create('employee_absences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('absence_date');
                $table->string('reason')->nullable();
                $table->text('justification')->nullable();
                $table->string('evidence_path')->nullable();
                $table->string('status', 30)->default('por_justificar');
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['user_id', 'absence_date']);
                $table->index(['status', 'absence_date']);
            });
        }

        if (! Schema::hasTable('employee_vacation_requests')) {
            Schema::create('employee_vacation_requests', function (Blueprint $table) {
                $table->id();
                $table->string('folio')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('start_date');
                $table->date('end_date');
                $table->unsignedSmallInteger('days_requested');
                $table->text('reason')->nullable();
                $table->string('status', 30)->default('pendiente');
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'start_date', 'end_date']);
                $table->index(['status', 'start_date']);
            });
        }

        if (! Schema::hasTable('employee_permission_requests')) {
            Schema::create('employee_permission_requests', function (Blueprint $table) {
                $table->id();
                $table->string('folio')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('permission_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('permission_type')->default('personal');
                $table->text('reason')->nullable();
                $table->string('status', 30)->default('pendiente');
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'permission_date']);
                $table->index(['status', 'permission_date']);
            });
        }

        if (! Schema::hasTable('employee_incidents')) {
            Schema::create('employee_incidents', function (Blueprint $table) {
                $table->id();
                $table->string('folio')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('incident_date');
                $table->string('incident_type')->default('retardo');
                $table->string('severity', 30)->default('normal');
                $table->text('description');
                $table->text('resolution')->nullable();
                $table->string('status', 30)->default('abierta');
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'incident_date']);
                $table->index(['status', 'incident_type']);
                $table->index('severity');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_incidents');
        Schema::dropIfExists('employee_permission_requests');
        Schema::dropIfExists('employee_vacation_requests');
        Schema::dropIfExists('employee_absences');
        Schema::dropIfExists('employee_attendances');
    }
};

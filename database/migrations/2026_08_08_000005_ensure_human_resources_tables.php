<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureUserHrColumns();
        $this->ensureEmployeeDocumentsTable();
        $this->ensureEmployeeShiftsTable();
        $this->ensureEmployeeAttendancesTable();
        $this->ensureEmployeeAbsencesTable();
        $this->ensureEmployeeVacationRequestsTable();
        $this->ensureEmployeePermissionRequestsTable();
        $this->ensureEmployeeIncidentsTable();
    }

    public function down(): void
    {
        //
    }

    private function ensureUserHrColumns(): void
    {
        $this->addColumnIfMissing('users', 'phone', fn (Blueprint $table) => $table->string('phone')->nullable()->after('name'));
        $this->addColumnIfMissing('users', 'position', fn (Blueprint $table) => $table->string('position')->nullable()->after('phone'));
        $this->addColumnIfMissing('users', 'payroll_number', fn (Blueprint $table) => $table->string('payroll_number')->nullable()->after('position'));
        $this->addColumnIfMissing('users', 'avatar', fn (Blueprint $table) => $table->string('avatar')->nullable()->after('payroll_number'));
    }

    private function ensureEmployeeDocumentsTable(): void
    {
        if (! Schema::hasTable('employee_documents')) {
            Schema::create('employee_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->string('file_path');
                $table->string('file_type')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->timestamps();
            });

            return;
        }

        $this->addColumnIfMissing('employee_documents', 'user_id', fn (Blueprint $table) => $table->foreignId('user_id')->nullable()->after('id'));
        $this->addColumnIfMissing('employee_documents', 'name', fn (Blueprint $table) => $table->string('name')->nullable()->after('user_id'));
        $this->addColumnIfMissing('employee_documents', 'file_path', fn (Blueprint $table) => $table->string('file_path')->nullable()->after('name'));
        $this->addColumnIfMissing('employee_documents', 'file_type', fn (Blueprint $table) => $table->string('file_type')->nullable()->after('file_path'));
        $this->addColumnIfMissing('employee_documents', 'file_size', fn (Blueprint $table) => $table->unsignedBigInteger('file_size')->nullable()->after('file_type'));
        $this->addTimestampsIfMissing('employee_documents');
    }

    private function ensureEmployeeShiftsTable(): void
    {
        if (! Schema::hasTable('employee_shifts')) {
            Schema::create('employee_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('shift_date');
                $table->time('time_in')->nullable();
                $table->time('time_out')->nullable();
                $table->string('note')->nullable();
                $table->string('status')->default('present');
                $table->timestamps();
            });

            return;
        }

        $this->addColumnIfMissing('employee_shifts', 'user_id', fn (Blueprint $table) => $table->foreignId('user_id')->nullable()->after('id'));
        $this->addColumnIfMissing('employee_shifts', 'shift_date', fn (Blueprint $table) => $table->date('shift_date')->nullable()->after('user_id'));
        $this->addColumnIfMissing('employee_shifts', 'time_in', fn (Blueprint $table) => $table->time('time_in')->nullable()->after('shift_date'));
        $this->addColumnIfMissing('employee_shifts', 'time_out', fn (Blueprint $table) => $table->time('time_out')->nullable()->after('time_in'));
        $this->addColumnIfMissing('employee_shifts', 'note', fn (Blueprint $table) => $table->string('note')->nullable()->after('time_out'));
        $this->addColumnIfMissing('employee_shifts', 'status', fn (Blueprint $table) => $table->string('status')->default('present')->after('note'));
        $this->addTimestampsIfMissing('employee_shifts');
    }

    private function ensureEmployeeAttendancesTable(): void
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

            return;
        }

        $this->addColumnIfMissing('employee_attendances', 'user_id', fn (Blueprint $table) => $table->foreignId('user_id')->nullable()->after('id'));
        $this->addColumnIfMissing('employee_attendances', 'validated_by', fn (Blueprint $table) => $table->foreignId('validated_by')->nullable()->after('user_id'));
        $this->addColumnIfMissing('employee_attendances', 'attendance_date', fn (Blueprint $table) => $table->date('attendance_date')->nullable()->after('validated_by'));
        $this->addColumnIfMissing('employee_attendances', 'check_in', fn (Blueprint $table) => $table->time('check_in')->nullable()->after('attendance_date'));
        $this->addColumnIfMissing('employee_attendances', 'check_out', fn (Blueprint $table) => $table->time('check_out')->nullable()->after('check_in'));
        $this->addColumnIfMissing('employee_attendances', 'late_minutes', fn (Blueprint $table) => $table->unsignedSmallInteger('late_minutes')->default(0)->after('check_out'));
        $this->addColumnIfMissing('employee_attendances', 'status', fn (Blueprint $table) => $table->string('status', 30)->default('pendiente')->after('late_minutes'));
        $this->addColumnIfMissing('employee_attendances', 'notes', fn (Blueprint $table) => $table->text('notes')->nullable()->after('status'));
        $this->addColumnIfMissing('employee_attendances', 'validated_at', fn (Blueprint $table) => $table->timestamp('validated_at')->nullable()->after('notes'));
        $this->addTimestampsIfMissing('employee_attendances');
        $this->addSoftDeletesIfMissing('employee_attendances');
    }

    private function ensureEmployeeAbsencesTable(): void
    {
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

            return;
        }

        $this->addColumnIfMissing('employee_absences', 'user_id', fn (Blueprint $table) => $table->foreignId('user_id')->nullable()->after('id'));
        $this->addColumnIfMissing('employee_absences', 'reviewed_by', fn (Blueprint $table) => $table->foreignId('reviewed_by')->nullable()->after('user_id'));
        $this->addColumnIfMissing('employee_absences', 'absence_date', fn (Blueprint $table) => $table->date('absence_date')->nullable()->after('reviewed_by'));
        $this->addColumnIfMissing('employee_absences', 'reason', fn (Blueprint $table) => $table->string('reason')->nullable()->after('absence_date'));
        $this->addColumnIfMissing('employee_absences', 'justification', fn (Blueprint $table) => $table->text('justification')->nullable()->after('reason'));
        $this->addColumnIfMissing('employee_absences', 'evidence_path', fn (Blueprint $table) => $table->string('evidence_path')->nullable()->after('justification'));
        $this->addColumnIfMissing('employee_absences', 'status', fn (Blueprint $table) => $table->string('status', 30)->default('por_justificar')->after('evidence_path'));
        $this->addColumnIfMissing('employee_absences', 'reviewed_at', fn (Blueprint $table) => $table->timestamp('reviewed_at')->nullable()->after('status'));
        $this->addColumnIfMissing('employee_absences', 'review_notes', fn (Blueprint $table) => $table->text('review_notes')->nullable()->after('reviewed_at'));
        $this->addTimestampsIfMissing('employee_absences');
        $this->addSoftDeletesIfMissing('employee_absences');
    }

    private function ensureEmployeeVacationRequestsTable(): void
    {
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

            return;
        }

        $this->addColumnIfMissing('employee_vacation_requests', 'folio', fn (Blueprint $table) => $table->string('folio')->nullable()->after('id'));
        $this->addColumnIfMissing('employee_vacation_requests', 'user_id', fn (Blueprint $table) => $table->foreignId('user_id')->nullable()->after('folio'));
        $this->addColumnIfMissing('employee_vacation_requests', 'reviewed_by', fn (Blueprint $table) => $table->foreignId('reviewed_by')->nullable()->after('user_id'));
        $this->addColumnIfMissing('employee_vacation_requests', 'start_date', fn (Blueprint $table) => $table->date('start_date')->nullable()->after('reviewed_by'));
        $this->addColumnIfMissing('employee_vacation_requests', 'end_date', fn (Blueprint $table) => $table->date('end_date')->nullable()->after('start_date'));
        $this->addColumnIfMissing('employee_vacation_requests', 'days_requested', fn (Blueprint $table) => $table->unsignedSmallInteger('days_requested')->default(0)->after('end_date'));
        $this->addColumnIfMissing('employee_vacation_requests', 'reason', fn (Blueprint $table) => $table->text('reason')->nullable()->after('days_requested'));
        $this->addColumnIfMissing('employee_vacation_requests', 'status', fn (Blueprint $table) => $table->string('status', 30)->default('pendiente')->after('reason'));
        $this->addColumnIfMissing('employee_vacation_requests', 'reviewed_at', fn (Blueprint $table) => $table->timestamp('reviewed_at')->nullable()->after('status'));
        $this->addColumnIfMissing('employee_vacation_requests', 'review_notes', fn (Blueprint $table) => $table->text('review_notes')->nullable()->after('reviewed_at'));
        $this->addTimestampsIfMissing('employee_vacation_requests');
        $this->addSoftDeletesIfMissing('employee_vacation_requests');
    }

    private function ensureEmployeePermissionRequestsTable(): void
    {
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

            return;
        }

        $this->addColumnIfMissing('employee_permission_requests', 'folio', fn (Blueprint $table) => $table->string('folio')->nullable()->after('id'));
        $this->addColumnIfMissing('employee_permission_requests', 'user_id', fn (Blueprint $table) => $table->foreignId('user_id')->nullable()->after('folio'));
        $this->addColumnIfMissing('employee_permission_requests', 'reviewed_by', fn (Blueprint $table) => $table->foreignId('reviewed_by')->nullable()->after('user_id'));
        $this->addColumnIfMissing('employee_permission_requests', 'permission_date', fn (Blueprint $table) => $table->date('permission_date')->nullable()->after('reviewed_by'));
        $this->addColumnIfMissing('employee_permission_requests', 'start_time', fn (Blueprint $table) => $table->time('start_time')->nullable()->after('permission_date'));
        $this->addColumnIfMissing('employee_permission_requests', 'end_time', fn (Blueprint $table) => $table->time('end_time')->nullable()->after('start_time'));
        $this->addColumnIfMissing('employee_permission_requests', 'permission_type', fn (Blueprint $table) => $table->string('permission_type')->default('personal')->after('end_time'));
        $this->addColumnIfMissing('employee_permission_requests', 'reason', fn (Blueprint $table) => $table->text('reason')->nullable()->after('permission_type'));
        $this->addColumnIfMissing('employee_permission_requests', 'status', fn (Blueprint $table) => $table->string('status', 30)->default('pendiente')->after('reason'));
        $this->addColumnIfMissing('employee_permission_requests', 'reviewed_at', fn (Blueprint $table) => $table->timestamp('reviewed_at')->nullable()->after('status'));
        $this->addColumnIfMissing('employee_permission_requests', 'review_notes', fn (Blueprint $table) => $table->text('review_notes')->nullable()->after('reviewed_at'));
        $this->addTimestampsIfMissing('employee_permission_requests');
        $this->addSoftDeletesIfMissing('employee_permission_requests');
    }

    private function ensureEmployeeIncidentsTable(): void
    {
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

            return;
        }

        $this->addColumnIfMissing('employee_incidents', 'folio', fn (Blueprint $table) => $table->string('folio')->nullable()->after('id'));
        $this->addColumnIfMissing('employee_incidents', 'user_id', fn (Blueprint $table) => $table->foreignId('user_id')->nullable()->after('folio'));
        $this->addColumnIfMissing('employee_incidents', 'reported_by', fn (Blueprint $table) => $table->foreignId('reported_by')->nullable()->after('user_id'));
        $this->addColumnIfMissing('employee_incidents', 'resolved_by', fn (Blueprint $table) => $table->foreignId('resolved_by')->nullable()->after('reported_by'));
        $this->addColumnIfMissing('employee_incidents', 'incident_date', fn (Blueprint $table) => $table->date('incident_date')->nullable()->after('resolved_by'));
        $this->addColumnIfMissing('employee_incidents', 'incident_type', fn (Blueprint $table) => $table->string('incident_type')->default('retardo')->after('incident_date'));
        $this->addColumnIfMissing('employee_incidents', 'severity', fn (Blueprint $table) => $table->string('severity', 30)->default('normal')->after('incident_type'));
        $this->addColumnIfMissing('employee_incidents', 'description', fn (Blueprint $table) => $table->text('description')->nullable()->after('severity'));
        $this->addColumnIfMissing('employee_incidents', 'resolution', fn (Blueprint $table) => $table->text('resolution')->nullable()->after('description'));
        $this->addColumnIfMissing('employee_incidents', 'status', fn (Blueprint $table) => $table->string('status', 30)->default('abierta')->after('resolution'));
        $this->addColumnIfMissing('employee_incidents', 'resolved_at', fn (Blueprint $table) => $table->timestamp('resolved_at')->nullable()->after('status'));
        $this->addTimestampsIfMissing('employee_incidents');
        $this->addSoftDeletesIfMissing('employee_incidents');
    }

    private function addColumnIfMissing(string $tableName, string $columnName, callable $definition): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($definition) {
            $definition($table);
        });
    }

    private function addTimestampsIfMissing(string $tableName): void
    {
        $this->addColumnIfMissing($tableName, 'created_at', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        $this->addColumnIfMissing($tableName, 'updated_at', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
    }

    private function addSoftDeletesIfMissing(string $tableName): void
    {
        $this->addColumnIfMissing($tableName, 'deleted_at', fn (Blueprint $table) => $table->softDeletes());
    }
};

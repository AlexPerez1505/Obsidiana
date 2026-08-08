<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_reports')) {
            Schema::create('employee_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('employee_name', 150)->nullable();
                $table->string('employee_initials', 10)->nullable();
                $table->enum('type', ['asistencia', 'falta', 'vacaciones', 'permiso', 'incidencia']);
                $table->string('label', 50)->nullable();
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
        } else {
            Schema::table('employee_reports', function (Blueprint $table) {
                if (! Schema::hasColumn('employee_reports', 'employee_name')) {
                    $table->string('employee_name', 150)->nullable()->after('created_by');
                }

                if (! Schema::hasColumn('employee_reports', 'employee_initials')) {
                    $table->string('employee_initials', 10)->nullable()->after('employee_name');
                }

                if (! Schema::hasColumn('employee_reports', 'label')) {
                    $table->string('label', 50)->nullable()->after('type');
                }
            });

            try {
                DB::statement('ALTER TABLE employee_reports MODIFY user_id BIGINT UNSIGNED NULL');
            } catch (\Throwable) {
                //
            }
        }

        $this->seedReportPageData();
    }

    public function down(): void
    {
        DB::table('employee_reports')
            ->where('metadata->source', 'admin_reportes_seed')
            ->delete();

        Schema::table('employee_reports', function (Blueprint $table) {
            if (Schema::hasColumn('employee_reports', 'label')) {
                $table->dropColumn('label');
            }

            if (Schema::hasColumn('employee_reports', 'employee_initials')) {
                $table->dropColumn('employee_initials');
            }

            if (Schema::hasColumn('employee_reports', 'employee_name')) {
                $table->dropColumn('employee_name');
            }
        });
    }

    private function seedReportPageData(): void
    {
        $creatorId = DB::table('users')->where('is_admin', true)->value('id')
            ?? DB::table('users')->value('id');
        $ricardoId = DB::table('users')->where('name', 'Ricardo')->value('id');
        $now = now();

        $rows = [
            [
                'user_id' => $ricardoId,
                'employee_name' => 'Ricardo',
                'employee_initials' => 'R',
                'area' => 'Gestion Administrativa',
                'type' => 'asistencia',
                'label' => 'Asistencia',
                'status' => 'validada',
                'start_date' => '2026-08-03',
                'end_date' => null,
                'check_in' => '08:02:00',
                'check_out' => '17:01:00',
                'late_minutes' => 2,
                'detail' => 'Entrada 08:02 - salida 17:01',
                'metadata' => ['attendance' => '94%', 'pending' => 0, 'summary_status' => 'Al dia'],
            ],
            [
                'employee_name' => 'Marina Sherlyn',
                'employee_initials' => 'MS',
                'area' => 'Marketing',
                'type' => 'vacaciones',
                'label' => 'Vacaciones',
                'status' => 'aprobada',
                'start_date' => '2026-08-05',
                'end_date' => '2026-08-09',
                'detail' => 'Periodo solicitado por descanso anual',
                'metadata' => ['attendance' => '96%', 'pending' => 1, 'summary_status' => 'Vacaciones'],
            ],
            [
                'employee_name' => 'Jose Alex',
                'employee_initials' => 'JA',
                'area' => 'Inventario',
                'type' => 'permiso',
                'label' => 'Permiso',
                'status' => 'pendiente',
                'start_date' => '2026-08-04',
                'detail' => 'Salida personal de 12:00 a 14:00',
                'metadata' => ['attendance' => '91%', 'pending' => 1, 'summary_status' => 'Permiso'],
            ],
            [
                'employee_name' => 'Andrea Ramirez',
                'employee_initials' => 'AR',
                'area' => 'Servicios',
                'type' => 'falta',
                'label' => 'Falta',
                'status' => 'justificar',
                'start_date' => '2026-07-31',
                'detail' => 'Sin registro de entrada',
                'metadata' => ['attendance' => '88%', 'pending' => 1, 'summary_status' => 'Justificar'],
            ],
            [
                'employee_name' => 'Dylan Santiago',
                'employee_initials' => 'DS',
                'area' => 'Comercial',
                'type' => 'incidencia',
                'label' => 'Incidencia',
                'status' => 'revision',
                'start_date' => '2026-07-30',
                'detail' => 'Retardo mayor a 20 minutos',
                'metadata' => ['attendance' => '90%', 'pending' => 1, 'summary_status' => 'Revision'],
            ],
            [
                'employee_name' => 'Fernanda Lopez',
                'employee_initials' => 'FL',
                'area' => 'Administracion',
                'type' => 'asistencia',
                'label' => 'Asistencia',
                'status' => 'validada',
                'start_date' => '2026-07-29',
                'detail' => 'Jornada completa validada',
                'metadata' => ['attendance' => '97%', 'pending' => 0, 'summary_status' => 'Al dia'],
            ],
        ];

        foreach ($rows as $row) {
            $metadata = array_merge($row['metadata'], ['source' => 'admin_reportes_seed']);
            unset($row['metadata']);

            $exists = DB::table('employee_reports')
                ->where('employee_name', $row['employee_name'])
                ->where('type', $row['type'])
                ->where('start_date', $row['start_date'])
                ->where('detail', $row['detail'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('employee_reports')->insert(array_merge([
                'user_id' => null,
                'created_by' => $creatorId,
                'end_date' => null,
                'check_in' => null,
                'check_out' => null,
                'late_minutes' => null,
                'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ], $row));
        }
    }
};

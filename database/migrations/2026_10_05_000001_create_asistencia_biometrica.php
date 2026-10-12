<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asistencia con lector de huella.
 *
 * Los empleados son los `users`; su número de checador es `users.checador_id`
 * (ya existía en el perfil de RH). Cada toque del lector se guarda tal cual
 * en `attendance_punches` y nunca se modifica; el resumen del día vive en
 * `employee_attendances`, que ya existía y aquí se amplía.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Lectores / PCs de checado. El agente local se autentica con una llave
        // (solo se guarda su hash); la pantalla del checador con otro token.
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('code', 40)->unique();            // RECEPCION-01
            $table->string('serial_number', 120)->nullable();
            $table->string('location', 150)->nullable();
            $table->string('api_key_hash', 64)->unique();
            $table->string('kiosk_token', 64)->unique();
            $table->string('status', 20)->default('active'); // active | inactive
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->string('agent_version', 40)->nullable();
            $table->timestamps();
        });

        // Plantillas biométricas. Se guarda el template del SDK (cifrado), jamás la imagen.
        Schema::create('employee_biometrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('finger_index')->default(1);   // 0-9
            $table->longText('template');                               // cifrado con Crypt
            $table->string('template_format', 30)->default('generic');  // zkteco, digitalpersona, secugen…
            $table->foreignId('device_id')->nullable()->constrained('biometric_devices')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamp('registered_at')->useCurrent();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'finger_index']);
        });

        // RH pide registrar una huella; el agente la recoge, captura las 3 lecturas y la sube.
        Schema::create('biometric_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('biometric_devices')->cascadeOnDelete();
            $table->unsignedTinyInteger('finger_index')->default(1);
            $table->string('status', 20)->default('pending');  // pending | capturing | done | failed | cancelled
            $table->string('message', 255)->nullable();
            $table->unsignedTinyInteger('step')->default(0);   // lecturas hechas (0-3)
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['device_id', 'status']);
        });

        // Cada toque del lector. Inmutable: una corrección se anula, no se edita.
        Schema::create('attendance_punches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();                    // idempotencia: el agente puede reenviar
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('biometric_devices')->nullOnDelete();
            $table->timestamp('punched_at')->index();
            $table->string('method', 20)->default('fingerprint');   // fingerprint | manual
            $table->unsignedTinyInteger('confidence')->nullable();  // 0-100
            $table->string('kind', 20)->nullable();                 // entrada | salida | regreso (lo que se mostró)
            $table->unsignedSmallInteger('seq')->default(1);        // número de checada del día
            $table->date('work_date')->index();                     // día laboral (zona local)
            $table->string('note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'work_date']);
        });

        // Horario base por empleado
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->json('weekdays');                                    // [1,2,3,4,5] (1 = lunes)
            $table->time('time_in')->default('09:00:00');
            $table->time('time_out')->default('18:00:00');
            $table->unsignedSmallInteger('tolerance_minutes')->default(5);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Lo que explica por qué alguien no checa: campo, home office, comisión, etc.
        Schema::create('attendance_modalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20);          // campo | home_office | comision | vacaciones | incapacidad | permiso
            $table->date('date_from');
            $table->date('date_to');
            $table->boolean('requires_fingerprint')->default(false);
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->string('destination', 150)->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'date_from', 'date_to']);
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name', 120);
            $table->timestamps();
        });

        Schema::table('employee_attendances', function (Blueprint $table) {
            $table->unsignedInteger('worked_minutes')->default(0)->after('late_minutes');
            $table->unsignedSmallInteger('punches_count')->default(0)->after('worked_minutes');
            $table->string('modality', 20)->nullable()->after('punches_count');
            $table->boolean('incomplete')->default(false)->after('modality');
        });

        Schema::table('users', function (Blueprint $table) {
            // Dato sensible: lo ve solo quien tenga el permiso de nómina
            $table->decimal('sueldo_quincenal', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('sueldo_quincenal'));
        Schema::table('employee_attendances', fn (Blueprint $t) => $t->dropColumn(['worked_minutes', 'punches_count', 'modality', 'incomplete']));
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('attendance_modalities');
        Schema::dropIfExists('employee_schedules');
        Schema::dropIfExists('attendance_punches');
        Schema::dropIfExists('biometric_enrollments');
        Schema::dropIfExists('employee_biometrics');
        Schema::dropIfExists('biometric_devices');
    }
};

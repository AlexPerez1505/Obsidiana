<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agenda empresarial y personal.
 *
 * - Los seguimientos de Clientes siguen en `cliente_seguimientos`: es la
 *   única fuente. Aquí solo se le agregan hora, prioridad, estado, origen,
 *   cancelación y la cadena "este nació al cerrar aquel". La agenda los lee
 *   de ahí directamente, así nunca hay copias que sincronizar.
 * - Las actividades y eventos que no son seguimientos viven en
 *   `agenda_actividades`, con sus participantes y destinatarios.
 * - Historial, recordatorios, comentarios y checklist sirven a los dos
 *   (relación polimórfica: 'seguimiento' o 'actividad').
 *
 * Nada de lo existente se borra ni se reescribe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cliente_seguimientos', function (Blueprint $t) {
            $t->time('hora')->nullable()->after('fecha');
            $t->string('prioridad', 10)->default('normal')->after('tipo');
            // pendiente | en_proceso | completado | cancelado
            $t->string('estado', 12)->default('pendiente')->after('prioridad')->index();
            // cliente: se programó desde Clientes · agenda: desde la Agenda
            $t->string('origen', 10)->default('cliente')->after('estado');
            $t->json('recordatorios')->nullable()->after('hora');
            $t->timestamp('cancelado_en')->nullable()->after('resultado');
            $t->foreignId('cancelado_por')->nullable()->after('cancelado_en')->constrained('users')->nullOnDelete();
            $t->string('motivo_cancelacion', 255)->nullable()->after('cancelado_por');
            $t->foreignId('anterior_id')->nullable()->after('motivo_cancelacion')->constrained('cliente_seguimientos')->nullOnDelete();
            $t->index(['user_id', 'estado', 'fecha']);
        });

        // Los que ya estaban hechos quedan como completados.
        DB::table('cliente_seguimientos')->whereNotNull('hecho_en')->update(['estado' => 'completado']);

        Schema::create('agenda_equipos', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 120)->unique();
            $t->string('descripcion', 255)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('agenda_equipo_usuario', function (Blueprint $t) {
            $t->foreignId('equipo_id')->constrained('agenda_equipos')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // El líder consulta las actividades del equipo.
            $t->boolean('es_lider')->default(false);
            $t->primary(['equipo_id', 'user_id']);
        });

        Schema::create('agenda_actividades', function (Blueprint $t) {
            $t->id();
            $t->string('titulo', 200);
            $t->text('descripcion')->nullable();
            $t->string('tipo', 20)->index();
            // tarea (pendiente/en_proceso/completado/cancelado) o
            // evento (programado/realizado/cancelado)
            $t->string('clase', 10);
            $t->string('estado', 12)->default('pendiente')->index();
            $t->date('fecha_inicio')->index();
            $t->time('hora_inicio')->nullable();
            $t->date('fecha_fin')->index();
            $t->time('hora_fin')->nullable();
            $t->boolean('todo_el_dia')->default(false);
            $t->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained('clientes')->nullOnDelete();
            $t->string('ubicacion', 255)->nullable();
            $t->string('enlace', 500)->nullable();
            $t->string('prioridad', 10)->default('normal');
            // privado | participantes | grupo | empresa
            $t->string('visibilidad', 14)->default('participantes')->index();
            // ¿Ocupa el horario de quienes participan? (los informativos no)
            $t->boolean('ocupa_horario')->default(true);
            $t->json('recordatorios')->nullable();
            // Repetición: todas las ocurrencias comparten serie_id y regla.
            $t->uuid('serie_id')->nullable()->index();
            $t->json('recurrencia')->nullable();
            $t->string('resultado', 1000)->nullable();
            $t->timestamp('completado_en')->nullable();
            $t->foreignId('completado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('cancelado_en')->nullable();
            $t->string('motivo_cancelacion', 255)->nullable();
            // Evento de la agenda anterior del que se importó (evita duplicarlo).
            $t->unsignedBigInteger('legado_id')->nullable()->unique();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('agenda_actividad_usuario', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actividad_id')->constrained('agenda_actividades')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // pendiente | aceptada | rechazada
            $t->string('respuesta', 10)->default('pendiente');
            $t->timestamp('respondido_en')->nullable();
            $t->string('comentario', 255)->nullable();
            $t->timestamps();
            $t->unique(['actividad_id', 'user_id']);
        });

        // Destinatarios de grupo: departamentos (roles) y equipos de trabajo.
        Schema::create('agenda_actividad_rol', function (Blueprint $t) {
            $t->foreignId('actividad_id')->constrained('agenda_actividades')->cascadeOnDelete();
            $t->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $t->primary(['actividad_id', 'role_id']);
        });

        Schema::create('agenda_actividad_equipo', function (Blueprint $t) {
            $t->foreignId('actividad_id')->constrained('agenda_actividades')->cascadeOnDelete();
            $t->foreignId('equipo_id')->constrained('agenda_equipos')->cascadeOnDelete();
            $t->primary(['actividad_id', 'equipo_id']);
        });

        Schema::create('agenda_historial', function (Blueprint $t) {
            $t->id();
            $t->morphs('item');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('accion', 30);
            $t->json('antes')->nullable();
            $t->json('despues')->nullable();
            $t->string('motivo', 255)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('agenda_recordatorios', function (Blueprint $t) {
            $t->id();
            $t->morphs('item');
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // recordatorio | vencido
            $t->string('tipo', 12)->default('recordatorio');
            $t->integer('minutos_antes')->default(0);
            $t->timestamp('enviar_en')->index();
            // pendiente | enviado | cancelado
            $t->string('estado', 10)->default('pendiente')->index();
            $t->timestamp('enviado_en')->nullable();
            $t->timestamps();
            // Reintentar programar no duplica: una sola fila por aviso.
            $t->unique(['item_type', 'item_id', 'user_id', 'tipo', 'minutos_antes'], 'agenda_rec_unico');
        });

        Schema::create('agenda_comentarios', function (Blueprint $t) {
            $t->id();
            $t->morphs('item');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('texto');
            $t->timestamps();
        });

        Schema::create('agenda_checklist', function (Blueprint $t) {
            $t->id();
            $t->morphs('item');
            $t->string('texto', 255);
            $t->boolean('hecho')->default(false);
            $t->foreignId('hecho_por')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedSmallInteger('orden')->default(0);
            $t->timestamps();
        });

        // Resumen diario enviado (uno por usuario y día, aunque se reintente).
        Schema::create('agenda_resumenes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->date('fecha');
            $t->timestamps();
            $t->unique(['user_id', 'fecha']);
        });

        Schema::table('users', function (Blueprint $t) {
            // {"correo": bool, "resumen": bool, "recordatorios": [15]}
            $t->json('agenda_preferencias')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('agenda_preferencias'));
        foreach (['agenda_resumenes', 'agenda_checklist', 'agenda_comentarios', 'agenda_recordatorios', 'agenda_historial',
            'agenda_actividad_equipo', 'agenda_actividad_rol', 'agenda_actividad_usuario', 'agenda_actividades',
            'agenda_equipo_usuario', 'agenda_equipos'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
        Schema::table('cliente_seguimientos', function (Blueprint $t) {
            $t->dropForeign(['cancelado_por']);
            $t->dropForeign(['anterior_id']);
            $t->dropIndex(['user_id', 'estado', 'fecha']);
            $t->dropColumn(['hora', 'prioridad', 'estado', 'origen', 'recordatorios', 'cancelado_en', 'cancelado_por', 'motivo_cancelacion', 'anterior_id']);
        });
    }
};

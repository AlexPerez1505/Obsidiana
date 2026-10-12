<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prospectos y seguimientos.
 *
 * Un prospecto es un cliente que todavía no compra: se registra igual, con
 * su etapa en "prospecto", y se le programan seguimientos ("cotizarle en
 * un mes", "llamarle el viernes"). El seguimiento avisa a su responsable
 * por el sistema y por correo el día que toca.
 *
 * También se crea la tabla estándar de notificaciones de Laravel, que es
 * lo que alimenta la campana del sistema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('etapa', 20)->default('cliente')->after('activo')->index();
        });

        Schema::create('cliente_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('clientes')->cascadeOnDelete();
            // Responsable: a quién se le avisa.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 20)->default('llamada');
            $table->date('fecha')->index();
            $table->string('nota', 500)->nullable();
            $table->timestamp('notificado_en')->nullable();
            $table->timestamp('hecho_en')->nullable();
            $table->foreignId('hecho_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resultado', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_seguimientos');

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('etapa');
        });
    }
};

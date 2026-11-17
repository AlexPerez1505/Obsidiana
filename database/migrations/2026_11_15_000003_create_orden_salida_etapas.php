<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las tres etapas de una orden de salida, cada una con su responsable:
 *
 *  1. Ingeniería: revisa e inspecciona el equipo antes de salir (checklist
 *     con IA según el equipo), anota lo que agrega de más y sube evidencia.
 *  2. Empaque: emplaya / empaqueta, confirma con qué piezas sale y sube
 *     evidencia. Al terminar, la orden queda lista para salir.
 *  3. Entrega: entrega en persona, en ruta o por paquetería, con evidencia
 *     y firmas. Al terminar, la orden se da por entregada.
 *
 * La evidencia sigue en orden_salida_evidencias, ahora con su etapa.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orden_salida_etapas')) {
            Schema::create('orden_salida_etapas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('orden_salida_id')->constrained('ordenes_salida')->cascadeOnDelete();
                $table->string('etapa', 20);                 // ingenieria | empaque | entrega
                $table->string('estado', 20)->default('pendiente'); // pendiente | en_curso | terminada
                $table->foreignId('asignada_a')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('asignada_por')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('asignada_en')->nullable();
                $table->json('checklist')->nullable();       // puntos con respuesta si|no|na y nota
                $table->boolean('checklist_ia')->default(false);
                $table->json('extras')->nullable();          // lo que agrega de más / piezas con las que sale
                $table->json('datos')->nullable();           // entrega: modalidad, paquetería, guía, recibe…
                $table->text('notas')->nullable();
                $table->string('firma_path')->nullable();
                $table->foreignId('iniciada_por')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('iniciada_en')->nullable();
                $table->foreignId('terminada_por')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('terminada_en')->nullable();
                $table->foreignId('editada_por')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('editada_en')->nullable();
                $table->timestamps();

                $table->unique(['orden_salida_id', 'etapa']);
            });
        }

        if (! Schema::hasColumn('orden_salida_evidencias', 'etapa')) {
            Schema::table('orden_salida_evidencias', function (Blueprint $table) {
                $table->string('etapa', 20)->nullable()->after('tipo')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orden_salida_evidencias', 'etapa')) {
            Schema::table('orden_salida_evidencias', function (Blueprint $table) {
                $table->dropColumn('etapa');
            });
        }

        Schema::dropIfExists('orden_salida_etapas');
    }
};

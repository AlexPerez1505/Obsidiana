<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La ruta de procesos de cada pieza.
 *
 * No todas pasan por lo mismo: un carro de curaciones puede necesitar solo
 * hojalatería, una torre solo mantenimiento, y un equipo golpeado los dos.
 * Por eso la ruta no vive en el código como una secuencia fija, sino aquí:
 * cada pieza trae su propia lista de pasos, en el orden que le toca.
 *
 * Una pieza sin pasos pendientes está disponible. Es esa la única regla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pieza_procesos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_serial_id')->constrained('producto_seriales')->cascadeOnDelete();

            $table->string('proceso', 30);
            $table->unsignedSmallInteger('orden')->default(0);

            // pendiente -> en_curso -> terminado. "omitido" es para cuando
            // se decide que ese paso ya no hacía falta, sin borrarlo: la
            // pieza pasó por ahí y quedó constancia de la decisión.
            $table->enum('estado', ['pendiente', 'en_curso', 'terminado', 'omitido'])->default('pendiente');

            $table->timestamp('iniciado_en')->nullable();
            $table->timestamp('terminado_en')->nullable();

            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notas')->nullable();

            // Por qué se metió este paso: lo propuso el checklist o lo puso
            // alguien a mano.
            $table->string('motivo', 255)->nullable();

            $table->timestamps();

            // La misma pieza no repite un proceso en su ruta.
            $table->unique(['producto_serial_id', 'proceso']);
            $table->index(['proceso', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pieza_procesos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de avances de un proceso.
 *
 * Un proceso no siempre se termina en un día: se va trabajando por partes
 * y cada día se deja lo que se hizo, con sus fotos. Aquí queda cada uno de
 * esos avances; el paso se cierra cuando todo quedó verificado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pieza_proceso_avances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pieza_proceso_id')->constrained('pieza_procesos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('nota')->nullable();
            $table->json('evidencias')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pieza_proceso_avances');
    }
};

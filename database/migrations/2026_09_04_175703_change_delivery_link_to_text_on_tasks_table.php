<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El link de entrega (Canva/Drive/YouTube) puede llegar a superar los
     * 255 caracteres (ej. links de Google con parámetros de tracking), así
     * que se amplía a TEXT para no tronar al guardar la tarea.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->text('delivery_link')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('delivery_link')->nullable()->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartas_garantia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo_equipo')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('id_subtipo')->constrained('productos')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('archivo_carta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartas_garantia');
    }
};

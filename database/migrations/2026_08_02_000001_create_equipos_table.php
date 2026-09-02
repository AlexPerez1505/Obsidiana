<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');            // Columna "Equipo" (ej. LAPAROSCOPIA)
            $table->string('modelo')->nullable();
            $table->string('marca')->nullable();
            $table->decimal('precio', 12, 2)->default(0);
            $table->string('imagen')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('sku')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};

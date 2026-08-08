<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellido')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('gmail')->nullable();
            $table->string('direccion')->nullable();
            $table->string('comentarios')->nullable();

            $table->unsignedBigInteger('congreso_id')->nullable();
            $table->foreign('congreso_id')->references('id')->on('congresos_eventos')->onDelete('set null');

            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('set null');

            $table->boolean('recibe_promocion')->default(false);
            $table->boolean('activo')->default(true);

            // Asesor = relación con la tabla usuarios (users)
            $table->unsignedBigInteger('asesor_id')->nullable();
            $table->foreign('asesor_id')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};

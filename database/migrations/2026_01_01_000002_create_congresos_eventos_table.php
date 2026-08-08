<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('congresos_eventos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->string('path_archivo')->nullable();

            $table->unsignedBigInteger('categoria_id');
            $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('cascade');

            $table->date('fecha_inicio');
            $table->date('fecha_finalizacion');
            $table->time('hora_montaje')->nullable();
            $table->time('hora_desmontaje')->nullable();

            $table->boolean('descarga_acceso')->default(false);
            $table->string('descarga_texto')->nullable();

            $table->boolean('acceso_subir')->default(false);
            $table->string('subir_texto')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->string('direccion')->nullable();
            $table->string('comments')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('congresos_eventos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promociones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('mensaje');
            $table->string('imagen_path')->nullable();
            $table->string('plantilla_whatsapp')->nullable();

            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('set null');

            $table->unsignedBigInteger('producto_id')->nullable();
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('set null');

            $table->unsignedBigInteger('paquete_id')->nullable();
            $table->foreign('paquete_id')->references('id')->on('paquetes')->onDelete('set null');

            $table->unsignedBigInteger('creado_por')->nullable();
            $table->foreign('creado_por')->references('id')->on('users')->onDelete('set null');

            $table->string('estado')->default('borrador'); // borrador | enviando | completada | fallida
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promociones');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_equipo');
            $table->string('subtipo')->nullable();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->decimal('precio', 12, 2)->default(0);
            $table->string('imagen_path')->nullable();
            $table->integer('stock')->default(0);
            $table->string('descripcion')->nullable();
            $table->string('proveedor')->nullable();
            $table->string('no_serie')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};

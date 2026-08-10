<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remision_os', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_mantenimiento')->constrained('mantenimiento_interno')->cascadeOnDelete();
            $table->foreignId('id_tipo_de_equipo')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('id_subtipo')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('id_marca')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('id_modelo')->constrained('productos')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('descripcion', 200)->nullable();
            $table->integer('stock')->default(0);
            $table->text('foto')->nullable();
            $table->decimal('precio', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remision_os');
    }
};

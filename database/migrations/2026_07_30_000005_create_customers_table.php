<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('telefono');
            $table->string('gmail')->nullable()->unique();
            $table->string('direccion')->nullable();
            $table->text('comentarios')->nullable();

            $table->foreignId('asesor')->nullable()->constrained('users');
            $table->foreignId('categorias_id')->nullable()->constrained('categories');
            $table->foreignId('congreso_conocido')->nullable()->constrained('congress_events');
            $table->boolean('recibe_promocion')->default(false);
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

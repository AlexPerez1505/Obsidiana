<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('congreso_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('congress_id')->constrained('congresos_eventos')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('rol')->nullable();
            $table->string('empresa')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('congreso_participantes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_mantenimiento')->constrained('mantenimiento_interno')->cascadeOnDelete();
            $table->integer('numero_checklist');
            $table->string('paso1');
            $table->string('paso2');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist');
    }
};

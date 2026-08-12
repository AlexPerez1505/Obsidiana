<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tecnico_externo', function (Blueprint $table) {
            $table->string('telefono', 30)->change();
            $table->string('nombre', 255)->change();
            $table->string('apellidos', 255)->change();
            $table->string('domicilio', 255)->change();
            $table->string('correo', 255)->change();
            $table->string('especialidad', 255)->change();
            $table->string('empresa', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tecnico_externo', function (Blueprint $table) {
            $table->string('telefono', 10)->change();
            $table->string('nombre', 100)->change();
            $table->string('apellidos', 100)->change();
            $table->string('domicilio', 100)->change();
            $table->string('correo', 100)->change();
            $table->string('especialidad', 100)->change();
            $table->string('empresa', 100)->change();
        });
    }
};

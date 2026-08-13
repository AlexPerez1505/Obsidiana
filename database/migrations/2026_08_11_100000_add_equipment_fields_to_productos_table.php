<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('estado')->default('Activo')->after('no_serie');
            $table->date('fecha_adquisicion')->nullable()->after('estado');
            $table->foreignId('user_id')->nullable()->after('fecha_adquisicion')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['estado', 'fecha_adquisicion', 'user_id']);
        });
    }
};

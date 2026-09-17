<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Al mandar un equipo a mantenimiento externo hay que decir a cuál cuenta
 * se le manda (puede haber más de una), para que cada quien vea solo lo
 * que le corresponde en la pantalla de Externo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('external_recipient_user_id')->nullable()
                ->after('external_technician_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('external_recipient_user_id');
        });
    }
};

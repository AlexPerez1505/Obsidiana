<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El técnico externo necesita dejar constancia de cómo le llegó el equipo
 * (evidencia y descripción), separado de lo que ya capturó quien registró
 * la orden internamente. Se agrega un estado nuevo para que se distinga
 * de un vistazo en la lista de "Externo" quién ya recibió y quién no.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE services MODIFY status ENUM('registrado', 'en_progreso', 'recibido_externo', 'validado', 'entregado', 'cancelado') NOT NULL DEFAULT 'registrado'");

        Schema::table('services', function (Blueprint $table) {
            $table->text('external_reception_notes')->nullable()->after('signature');
            $table->json('external_reception_evidence')->nullable()->after('external_reception_notes');
            $table->timestamp('external_received_at')->nullable()->after('external_reception_evidence');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['external_reception_notes', 'external_reception_evidence', 'external_received_at']);
        });

        DB::statement("ALTER TABLE services MODIFY status ENUM('registrado', 'en_progreso', 'validado', 'entregado', 'cancelado') NOT NULL DEFAULT 'registrado'");
    }
};

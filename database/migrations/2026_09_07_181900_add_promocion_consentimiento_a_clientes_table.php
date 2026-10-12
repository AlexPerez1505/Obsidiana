<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "recibe_promocion" solo decía sí/no, sin poder probar cuándo ni quién
 * lo preguntó, ni si el cliente de verdad lo confirmó él mismo.
 *
 * Ahora hay dos niveles:
 *  - El asesor pregunta y marca que el cliente dijo que sí (queda con
 *    fecha y quién lo capturó): es el registro interno.
 *  - El cliente confirma por su cuenta (por ahora respondiendo un
 *    mensaje de WhatsApp): es la prueba fuerte, la que de verdad
 *    habilita mandarle campañas.
 *
 * "recibe_promocion" se conserva como bandera rápida de consulta, pero
 * ahora se calcula a partir de estos campos, no se captura a mano.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->timestamp('promocion_autorizada_en')->nullable()->after('recibe_promocion');
            $table->foreignId('promocion_autorizada_por')->nullable()->after('promocion_autorizada_en')
                ->constrained('users')->nullOnDelete();

            $table->timestamp('promocion_confirmada_en')->nullable()->after('promocion_autorizada_por');
            $table->timestamp('promocion_revocada_en')->nullable()->after('promocion_confirmada_en');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promocion_autorizada_por');
            $table->dropColumn(['promocion_autorizada_en', 'promocion_confirmada_en', 'promocion_revocada_en']);
        });
    }
};

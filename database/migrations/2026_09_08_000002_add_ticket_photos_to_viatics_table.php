<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viatics', function (Blueprint $table) {
            // Varias fotos del ticket por viático. El campo "ticket_photo"
            // (una sola foto) se deja tal cual para no perder datos viejos,
            // pero de aquí en adelante se usa este arreglo.
            $table->json('ticket_photos')->nullable()->after('ticket_photo');
        });
    }

    public function down(): void
    {
        Schema::table('viatics', function (Blueprint $table) {
            $table->dropColumn('ticket_photos');
        });
    }
};

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
        Schema::table('service_maintenances', function (Blueprint $table) {
            $table->json('partidas_remision')->nullable()->after('refacciones');
            $table->decimal('envio', 12, 2)->nullable()->after('partidas_remision');
            $table->decimal('anticipo', 12, 2)->nullable()->after('envio');
            $table->boolean('requiere_iva')->default(false)->after('anticipo');
            $table->text('descripcion_general')->nullable()->after('requiere_iva');
            $table->string('os_pdf_path')->nullable()->after('descripcion_general');
            $table->dateTime('os_generated_at')->nullable()->after('os_pdf_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_maintenances', function (Blueprint $table) {
            $table->dropColumn([
                'partidas_remision',
                'envio',
                'anticipo',
                'requiere_iva',
                'descripcion_general',
                'os_pdf_path',
                'os_generated_at',
            ]);
        });
    }
};

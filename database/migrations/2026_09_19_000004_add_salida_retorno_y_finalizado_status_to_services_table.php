<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE services MODIFY COLUMN status ENUM('registrado','pendiente','aprobado','en_progreso','completado','entregado','validado','salida_retorno','finalizado','cancelado','rechazado') NOT NULL DEFAULT 'registrado'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE services MODIFY COLUMN status ENUM('registrado','pendiente','aprobado','en_progreso','completado','entregado','validado','cancelado','rechazado') NOT NULL DEFAULT 'registrado'");
    }
};

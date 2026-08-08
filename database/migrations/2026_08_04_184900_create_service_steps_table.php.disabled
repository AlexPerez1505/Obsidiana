<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_steps', function (Blueprint $table) {
            $table->id();
            $table->enum('service_type', ['interno', 'externo']);
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedTinyInteger('order')->default(0);
            $table->boolean('requires_qr')->default(true);
            $table->boolean('requires_signature')->default(false);
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });

        DB::table('service_steps')->insert([
            ['service_type' => 'interno', 'name' => 'Autorización admin', 'code' => 'autorizacion_admin', 'order' => 1, 'requires_qr' => true, 'requires_signature' => false, 'is_final' => false, 'created_at' => now(), 'updated_at' => now()],
            ['service_type' => 'interno', 'name' => 'Entrega / Cierre', 'code' => 'entrega_cierre', 'order' => 2, 'requires_qr' => true, 'requires_signature' => false, 'is_final' => true, 'created_at' => now(), 'updated_at' => now()],
            ['service_type' => 'externo', 'name' => 'Salida a mantenimiento foráneo', 'code' => 'salida_foranea', 'order' => 1, 'requires_qr' => true, 'requires_signature' => false, 'is_final' => false, 'created_at' => now(), 'updated_at' => now()],
            ['service_type' => 'externo', 'name' => 'Regreso de mantenimiento foráneo', 'code' => 'regreso_foranea', 'order' => 2, 'requires_qr' => true, 'requires_signature' => false, 'is_final' => false, 'created_at' => now(), 'updated_at' => now()],
            ['service_type' => 'externo', 'name' => 'Validación OS', 'code' => 'validacion_os', 'order' => 3, 'requires_qr' => true, 'requires_signature' => false, 'is_final' => false, 'created_at' => now(), 'updated_at' => now()],
            ['service_type' => 'externo', 'name' => 'Salida para cliente', 'code' => 'salida_cliente', 'order' => 4, 'requires_qr' => true, 'requires_signature' => false, 'is_final' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_steps');
    }
};

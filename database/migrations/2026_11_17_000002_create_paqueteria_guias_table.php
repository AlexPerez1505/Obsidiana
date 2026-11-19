<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paqueteria_guias', function (Blueprint $table) {
            $table->id();
            $table->string('paqueteria', 120);
            $table->string('numero_guia', 120)->unique();
            $table->decimal('capacidad_kg', 10, 2)->nullable();
            $table->string('estado', 20)->default('disponible')->index();
            $table->foreignId('envio_id')->nullable()->constrained('paqueteria_envios')->nullOnDelete();
            $table->timestamp('usada_at')->nullable();
            $table->timestamps();
        });

        DB::table('paqueteria_guias')->insert([
            ['paqueteria' => 'FedEx', 'numero_guia' => '8187 5409 6672', 'capacidad_kg' => 10, 'estado' => 'disponible', 'created_at' => now(), 'updated_at' => now()],
            ['paqueteria' => 'FedEx', 'numero_guia' => '8187 5409 6661', 'capacidad_kg' => 10, 'estado' => 'disponible', 'created_at' => now(), 'updated_at' => now()],
            ['paqueteria' => 'FedEx', 'numero_guia' => '8187 5409 6650', 'capacidad_kg' => 10, 'estado' => 'disponible', 'created_at' => now(), 'updated_at' => now()],
            ['paqueteria' => 'FedEx', 'numero_guia' => '8187 5409 6640', 'capacidad_kg' => 10, 'estado' => 'disponible', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('paqueteria_guias');
    }
};

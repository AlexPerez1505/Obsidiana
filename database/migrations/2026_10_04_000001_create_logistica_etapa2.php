<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logística, etapa 2: desviaciones de ruta, incidencias, kilometraje,
 * combustible y mantenimiento de la flota.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rutas', function (Blueprint $table) {
            $table->decimal('km_recorridos', 8, 1)->nullable()->after('duracion_s');
        });

        // El vehículo se salió del trazo planeado: se abre al salir y se cierra al volver
        Schema::create('ruta_desviaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruta_id')->constrained('rutas')->cascadeOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->unsignedInteger('max_distancia_m');
            $table->timestamp('inicio_en');
            $table->timestamp('fin_en')->nullable();
            $table->timestamps();
        });

        Schema::create('ruta_incidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruta_id')->constrained('rutas')->cascadeOnDelete();
            $table->foreignId('ruta_parada_id')->nullable()->constrained('ruta_paradas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 30);
            $table->text('descripcion')->nullable();
            $table->string('foto', 255)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('resuelta_en')->nullable();
            $table->foreignId('resuelta_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resolucion', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('vehiculo_combustible', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('ruta_id')->nullable()->constrained('rutas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            $table->decimal('litros', 8, 2);
            $table->decimal('monto', 10, 2);
            $table->unsignedInteger('odometro_km')->nullable();
            $table->boolean('tanque_lleno')->default(true);
            $table->string('estacion', 120)->nullable();
            $table->string('ticket', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('vehiculo_mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 30);                       // preventivo, correctivo, verificacion, seguro, otro
            $table->date('fecha');
            $table->unsignedInteger('odometro_km')->nullable();
            $table->decimal('costo', 10, 2)->default(0);
            $table->string('proveedor', 120)->nullable();
            $table->text('descripcion')->nullable();
            $table->date('proximo_fecha')->nullable();
            $table->unsignedInteger('proximo_km')->nullable();
            $table->string('comprobante', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_mantenimientos');
        Schema::dropIfExists('vehiculo_combustible');
        Schema::dropIfExists('ruta_incidencias');
        Schema::dropIfExists('ruta_desviaciones');

        Schema::table('rutas', function (Blueprint $table) {
            $table->dropColumn('km_recorridos');
        });
    }
};

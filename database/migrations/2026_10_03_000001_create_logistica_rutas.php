<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logística: rutas de reparto con varias paradas, rastreo GPS del chofer,
 * llegada automática por geocerca y evidencias de entrega.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutas', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->string('estado', 20)->default('borrador')->index();
            $table->date('fecha_programada')->nullable()->index();
            $table->time('hora_salida')->nullable();

            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('chofer_id')->nullable()->constrained('users')->nullOnDelete();

            // Punto de salida (bodega) y si el vehículo regresa a él
            $table->string('origen_nombre', 150)->nullable();
            $table->string('origen_direccion', 255)->nullable();
            $table->decimal('origen_lat', 10, 7)->nullable();
            $table->decimal('origen_lng', 10, 7)->nullable();
            $table->boolean('regresa_a_origen')->default(true);

            // Resultado de la optimización (distancias en metros, tiempo en segundos)
            $table->unsignedInteger('distancia_m')->nullable();
            $table->unsignedInteger('duracion_s')->nullable();
            $table->string('optimizada_con', 20)->nullable();   // google | local
            $table->timestamp('optimizada_en')->nullable();
            $table->longText('trazo_planeado')->nullable();     // JSON [[lat,lng],...]

            // Sin esto la parada no se puede cerrar: foto, receptor, firma y ubicación
            $table->boolean('exigir_evidencia')->default(true);

            $table->text('notas')->nullable();
            $table->timestamp('iniciada_en')->nullable();
            $table->timestamp('finalizada_en')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ruta_paradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruta_id')->constrained('rutas')->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->string('nombre', 150);
            $table->string('direccion', 255)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedSmallInteger('radio_m')->default(100);   // geocerca

            $table->foreignId('customer_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('orden_salida_id')->nullable()->constrained('ordenes_salida')->nullOnDelete();
            $table->string('contacto_nombre', 120)->nullable();
            $table->string('contacto_telefono', 30)->nullable();
            $table->time('ventana_desde')->nullable();
            $table->time('ventana_hasta')->nullable();
            $table->boolean('urgente')->default(false);
            $table->text('instrucciones')->nullable();

            $table->string('estado', 20)->default('pendiente')->index();
            $table->unsignedInteger('distancia_desde_anterior_m')->nullable();
            $table->unsignedInteger('duracion_desde_anterior_s')->nullable();
            $table->timestamp('eta')->nullable();

            $table->timestamp('llegada_en')->nullable();
            $table->string('llegada_origen', 10)->nullable();        // auto | manual
            $table->timestamp('salida_en')->nullable();
            $table->timestamp('completada_en')->nullable();
            $table->string('receptor_nombre', 150)->nullable();
            $table->string('firma_path', 255)->nullable();
            $table->string('motivo_no_entrega', 255)->nullable();
            $table->decimal('cierre_lat', 10, 7)->nullable();
            $table->decimal('cierre_lng', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('ruta_parada_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruta_parada_id')->constrained('ruta_paradas')->cascadeOnDelete();
            $table->string('descripcion', 255);
            $table->unsignedInteger('cantidad')->default(1);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('ruta_evidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruta_parada_id')->constrained('ruta_paradas')->cascadeOnDelete();
            $table->string('tipo', 20)->default('foto');   // foto | remision
            $table->string('archivo', 255);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->foreignId('subida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Bitácora de GPS: un punto cada pocos segundos mientras la ruta está en curso
        Schema::create('ruta_ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruta_id')->constrained('rutas')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->decimal('velocidad_kmh', 6, 1)->nullable();
            $table->decimal('precision_m', 7, 1)->nullable();
            $table->decimal('rumbo', 5, 1)->nullable();
            $table->timestamp('registrada_en');
            $table->index(['ruta_id', 'registrada_en']);
        });

        Schema::create('ruta_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruta_id')->constrained('rutas')->cascadeOnDelete();
            $table->foreignId('ruta_parada_id')->nullable()->constrained('ruta_paradas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 30)->index();
            $table->string('detalle', 255)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('ocurrio_en');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ruta_eventos');
        Schema::dropIfExists('ruta_ubicaciones');
        Schema::dropIfExists('ruta_evidencias');
        Schema::dropIfExists('ruta_parada_items');
        Schema::dropIfExists('ruta_paradas');
        Schema::dropIfExists('rutas');
    }
};

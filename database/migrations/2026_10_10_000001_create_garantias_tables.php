<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo de garantías: pólizas por unidad o componente, reclamaciones con
 * su flujo, costos, archivos, sustituciones y una bitácora inmutable.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Condiciones de cobertura reutilizables (las "pólizas tipo").
        Schema::create('garantia_coberturas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo', 30);
            $table->unsignedSmallInteger('meses')->default(12);
            $table->string('aplica_a', 20)->nullable(); // nuevo | usado | null = cualquiera
            $table->text('cubre')->nullable();
            $table->text('exclusiones')->nullable();
            $table->text('condiciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Una fila por unidad o componente cubierto.
        Schema::create('garantias', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->uuid('public_token')->unique();
            $table->string('origen', 20)->default('venta'); // venta | manual
            $table->foreignId('customer_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->unsignedBigInteger('venta_item_id')->nullable()->index();
            $table->unsignedBigInteger('producto_serial_id')->nullable()->index();
            $table->unsignedBigInteger('producto_id')->nullable()->index();
            $table->unsignedBigInteger('equipo_id')->nullable();
            $table->unsignedBigInteger('paquete_id')->nullable();
            $table->string('grupo')->nullable(); // "Torre de laparoscopía" cuando viene de un paquete
            $table->unsignedSmallInteger('unidad')->default(1); // n-ésima unidad sin serie del mismo renglón
            $table->foreignId('cobertura_id')->nullable()->constrained('garantia_coberturas')->nullOnDelete();

            $table->string('descripcion');
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('no_serie')->nullable()->index();
            $table->string('codigo_interno', 40)->nullable(); // etiqueta MB del QR
            $table->string('condicion', 20)->default('nuevo');
            $table->string('tipo', 30);
            $table->string('proveedor')->nullable();

            $table->unsignedSmallInteger('meses');
            $table->date('fecha_venta')->nullable();
            $table->date('fecha_entrega')->nullable();
            $table->date('inicio');
            $table->date('vence')->index();
            $table->boolean('fechas_manual')->default(false);

            $table->text('cubre')->nullable();
            $table->text('exclusiones')->nullable();
            $table->text('condiciones')->nullable();

            $table->decimal('precio_venta', 12, 2)->nullable();
            $table->decimal('costo_adquisicion', 12, 2)->nullable();

            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado', 20)->default('activa'); // activa | anulada | sustituida
            $table->text('motivo_anulacion')->nullable();
            $table->foreignId('anulada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('anulada_en')->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['venta_id', 'venta_item_id']);
        });

        Schema::create('garantia_reclamaciones', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->foreignId('garantia_id')->nullable()->constrained('garantias')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('no_serie')->nullable()->index();
            $table->string('equipo')->nullable(); // descripción cuando no se ligó a una garantía
            $table->string('canal', 20)->default('telefono');
            $table->string('contacto_nombre')->nullable();
            $table->string('contacto_telefono', 40)->nullable();
            $table->string('contacto_correo')->nullable();
            $table->text('sintomas');
            $table->string('prioridad', 10)->default('media');
            $table->boolean('es_seguridad')->default(false);
            $table->string('etapa', 20)->default('recibida');

            $table->text('diagnostico')->nullable();
            $table->text('pruebas')->nullable();
            $table->text('causa')->nullable();

            $table->string('dictamen', 20)->default('pendiente');
            $table->text('dictamen_motivo')->nullable();
            $table->text('dictamen_fundamento')->nullable();
            $table->foreignId('dictamen_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dictamen_en')->nullable();

            $table->string('resolucion', 20)->nullable();
            $table->text('resolucion_detalle')->nullable();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('entregado_a')->nullable();

            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recibida_en');
            $table->timestamp('primera_respuesta_en')->nullable();
            $table->timestamp('limite_en')->nullable();
            $table->timestamp('cerrada_en')->nullable();

            $table->unsignedTinyInteger('satisfaccion')->nullable();
            $table->text('comentario_cliente')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('garantia_costos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reclamacion_id')->constrained('garantia_reclamaciones')->cascadeOnDelete();
            $table->foreignId('garantia_id')->nullable()->constrained('garantias')->nullOnDelete();
            $table->string('concepto', 20);
            $table->string('descripcion')->nullable();
            $table->decimal('monto', 12, 2);
            $table->string('absorbe', 20)->default('medibuy'); // medibuy | fabricante | cliente | provision
            $table->decimal('recuperado', 12, 2)->default(0);
            $table->string('estado', 20)->default('aprobado'); // aprobado | pendiente | rechazado
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprobado_en')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('garantia_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garantia_id')->nullable()->constrained('garantias')->cascadeOnDelete();
            $table->foreignId('reclamacion_id')->nullable()->constrained('garantia_reclamaciones')->cascadeOnDelete();
            $table->string('tipo', 20)->default('evidencia');
            $table->string('nombre');
            $table->string('path');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Préstamos temporales y sustituciones definitivas.
        Schema::create('garantia_sustituciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reclamacion_id')->constrained('garantia_reclamaciones')->cascadeOnDelete();
            $table->foreignId('garantia_id')->nullable()->constrained('garantias')->nullOnDelete();
            $table->string('tipo', 20); // prestamo | definitiva
            $table->string('equipo');
            $table->string('no_serie_entregado')->nullable();
            $table->string('no_serie_anterior')->nullable();
            $table->date('entregado_en');
            $table->date('devolver_antes')->nullable();
            $table->date('devuelto_en')->nullable();
            $table->text('condiciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Bitácora: no se edita ni se borra.
        Schema::create('garantia_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garantia_id')->nullable()->constrained('garantias')->cascadeOnDelete();
            $table->foreignId('reclamacion_id')->nullable()->constrained('garantia_reclamaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 30);
            $table->text('descripcion');
            $table->json('datos')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Coberturas de partida: se editan en pantalla. Los plazos reales de
        // cada equipo salen de la venta; estos textos son el punto de inicio.
        $ahora = now();
        $base = 'Defectos de fabricación y fallas de funcionamiento en condiciones normales de uso, '
            .'incluyendo mano de obra y refacciones necesarias para la reparación.';
        $excl = 'Daño por golpes, caídas, líquidos, mal uso, uso fuera de especificación, reprocesamiento '
            .'inadecuado, intervención de terceros no autorizados, variaciones eléctricas y desgaste natural '
            .'de consumibles y accesorios.';

        DB::table('garantia_coberturas')->insert([
            ['nombre' => 'Garantía MediBuy · equipo nuevo', 'tipo' => 'medibuy', 'meses' => 12, 'aplica_a' => 'nuevo',
             'cubre' => $base, 'exclusiones' => $excl, 'condiciones' => null, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Garantía de reacondicionado', 'tipo' => 'reacondicionado', 'meses' => 6, 'aplica_a' => 'usado',
             'cubre' => $base, 'exclusiones' => $excl, 'condiciones' => null, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Garantía del fabricante', 'tipo' => 'fabricante', 'meses' => 12, 'aplica_a' => null,
             'cubre' => 'Según las condiciones publicadas por el fabricante.', 'exclusiones' => $excl, 'condiciones' => null, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Garantía por reparación', 'tipo' => 'reparacion', 'meses' => 3, 'aplica_a' => null,
             'cubre' => 'El trabajo realizado y las refacciones instaladas en la reparación.', 'exclusiones' => $excl, 'condiciones' => null, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('garantia_eventos');
        Schema::dropIfExists('garantia_sustituciones');
        Schema::dropIfExists('garantia_archivos');
        Schema::dropIfExists('garantia_costos');
        Schema::dropIfExists('garantia_reclamaciones');
        Schema::dropIfExists('garantias');
        Schema::dropIfExists('garantia_coberturas');
    }
};

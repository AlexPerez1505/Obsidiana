<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Marketing y promociones: campañas por WhatsApp con cupones que se usan
| en cotizaciones y ventas reales.
|
| Reutiliza lo que ya existía (clientes con su consentimiento, campanas y
| campana_destinatarios, que estaban vacías) y agrega cupones, su uso con
| reserva/consumo, la bitácora y los webhooks de WhatsApp.
*/
return new class extends Migration
{
    public function up(): void
    {
        // ---------- Cupones ----------
        Schema::create('mkt_cupones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('nombre');
            // general: cualquiera con consentimiento lo puede usar.
            // exclusivo: ligado a un cliente (cliente_id). Un exclusivo sin
            // cliente es una "plantilla" que la campaña copia por cliente.
            $table->string('tipo', 12)->default('general');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('origen_cupon_id')->nullable()->constrained('mkt_cupones')->nullOnDelete();
            $table->unsignedBigInteger('campana_id')->nullable()->index();

            $table->string('descuento_tipo', 12); // porcentaje | monto
            $table->decimal('descuento_valor', 12, 2);
            $table->decimal('compra_minima', 12, 2)->default(0);
            $table->decimal('tope_descuento', 12, 2)->nullable();

            $table->dateTime('inicia_en')->nullable();
            $table->dateTime('vence_en')->nullable();

            $table->unsignedInteger('usos_max')->nullable(); // null = sin límite
            $table->unsignedInteger('usos_por_cliente')->default(1);

            $table->string('aplica_a', 12)->default('todo'); // todo | seleccion
            $table->boolean('acumulable')->default(false);
            $table->boolean('solo_primera_compra')->default(false);

            // activo | pausado. "agotado" y "vencido" se calculan, no se guardan.
            $table->string('estado', 12)->default('activo');
            $table->text('condiciones')->nullable();

            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tipo', 'estado']);
        });

        // A qué productos / equipos / paquetes / tipos de equipo aplica.
        Schema::create('mkt_cupon_alcances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cupon_id')->constrained('mkt_cupones')->cascadeOnDelete();
            $table->string('tipo', 12); // producto | equipo | paquete | tipo_equipo
            $table->unsignedBigInteger('ref_id');
            $table->unique(['cupon_id', 'tipo', 'ref_id']);
        });

        // ---------- Campañas (la tabla ya existía, vacía) ----------
        Schema::table('campanas', function (Blueprint $table) {
            $table->string('titulo_oferta', 120)->nullable()->after('nombre');
            $table->string('plantilla', 120)->nullable()->after('mensaje');
            $table->string('imagen_path')->nullable()->after('plantilla');
            $table->foreignId('cupon_id')->nullable()->after('imagen_path')->constrained('mkt_cupones')->nullOnDelete();
            $table->boolean('cupon_exclusivo')->default(false)->after('cupon_id');
            $table->dateTime('vigencia_inicio')->nullable()->after('programada_para');
            $table->dateTime('vigencia_fin')->nullable()->after('vigencia_inicio');
            $table->foreignId('actualizado_por')->nullable()->after('creado_por')->constrained('users')->nullOnDelete();
            $table->foreignId('enviado_por')->nullable()->after('actualizado_por')->constrained('users')->nullOnDelete();
            $table->foreignId('cancelado_por')->nullable()->after('enviado_por')->constrained('users')->nullOnDelete();
            $table->dateTime('iniciada_en')->nullable();
            $table->dateTime('pausada_en')->nullable();
            $table->dateTime('terminada_en')->nullable();
            $table->dateTime('cancelada_en')->nullable();
            $table->dateTime('prueba_enviada_en')->nullable();
            $table->index('estado');
        });

        Schema::create('campana_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->constrained('campanas')->cascadeOnDelete();
            $table->string('tipo_item', 12); // producto | equipo | paquete
            $table->unsignedBigInteger('ref_id');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->unique(['campana_id', 'tipo_item', 'ref_id']);
        });

        // Los destinatarios: se suelta la llave única vieja para admitir
        // pruebas (sin cliente) y se cambia por una clave propia.
        foreach ([['dropForeign', ['cliente_id']], ['dropForeign', ['campana_id']], ['dropUnique', ['campana_id', 'cliente_id']]] as [$metodo, $cols]) {
            try {
                Schema::table('campana_destinatarios', fn (Blueprint $t) => $t->{$metodo}($cols));
            } catch (\Throwable) {
                // Ya no existía (base que se migró a medias).
            }
        }

        Schema::table('campana_destinatarios', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable()->change();
            $table->foreign('campana_id')->references('id')->on('campanas')->cascadeOnDelete();
            $table->foreign('cliente_id')->references('id')->on('clientes')->nullOnDelete();

            // "c{cliente}" para envíos reales (uno por cliente y campaña:
            // así nadie recibe la misma campaña dos veces) y "p{uuid}" para pruebas.
            $table->string('clave', 60)->after('cliente_id');
            $table->boolean('es_prueba')->default(false)->after('clave');
            $table->string('token', 48)->nullable()->unique()->after('es_prueba');
            $table->string('telefono', 20)->nullable()->after('token');
            $table->foreignId('cupon_id')->nullable()->after('telefono')->constrained('mkt_cupones')->nullOnDelete();
            $table->boolean('simulado')->default(false)->after('estado');
            $table->unsignedTinyInteger('intentos')->default(0)->after('simulado');
            $table->string('error_codigo', 20)->nullable()->after('error');
            $table->dateTime('fallo_en')->nullable()->after('leido_en');
            $table->unsignedInteger('clics')->default(0);
            $table->dateTime('primer_clic_en')->nullable();
            $table->dateTime('ultimo_clic_en')->nullable();

            $table->unique(['campana_id', 'clave']);
            $table->index('whatsapp_message_id');
            $table->index(['cliente_id', 'enviado_en']);
        });

        // ---------- Uso de cupones: reserva y consumo ----------
        Schema::create('mkt_cupon_usos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cupon_id')->constrained('mkt_cupones')->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('campana_id')->nullable()->constrained('campanas')->nullOnDelete();
            $table->foreignId('destinatario_id')->nullable()->constrained('campana_destinatarios')->nullOnDelete();
            $table->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->nullOnDelete();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();

            // reservado → consumido | liberado ; consumido → revertido
            $table->string('estado', 12);
            $table->decimal('subtotal_elegible', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->dateTime('reservado_en')->nullable();
            $table->dateTime('expira_en')->nullable();
            $table->dateTime('consumido_en')->nullable();
            $table->dateTime('cerrado_en')->nullable();
            $table->string('motivo_cierre')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cupon_id', 'estado']);
            $table->index(['cupon_id', 'cliente_id', 'estado']);
        });

        // ---------- Atribución en los documentos existentes ----------
        foreach (['cotizaciones', 'ventas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('cupon_id')->nullable()->after('descuento_monto')->constrained('mkt_cupones')->nullOnDelete();
                $table->string('cupon_codigo', 40)->nullable()->after('cupon_id');
                $table->decimal('cupon_descuento', 12, 2)->default(0)->after('cupon_codigo');
                $table->foreignId('campana_id')->nullable()->after('cupon_descuento')->constrained('campanas')->nullOnDelete();
            });
        }

        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->foreignId('campana_destinatario_id')->nullable()->after('campana_id')
                ->constrained('campana_destinatarios')->nullOnDelete();
        });

        // ---------- Bitácora (quién crea, edita, envía, cancela) ----------
        Schema::create('mkt_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->nullable()->constrained('campanas')->cascadeOnDelete();
            $table->foreignId('cupon_id')->nullable()->constrained('mkt_cupones')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 40);
            $table->string('detalle', 500)->nullable();
            $table->json('datos')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['campana_id', 'created_at']);
        });

        // Historial del consentimiento de cada cliente.
        Schema::create('mkt_consentimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('accion', 12); // otorgado | revocado
            $table->string('fuente', 30); // panel | enlace | whatsapp
            $table->string('nota', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['cliente_id', 'created_at']);
        });

        // Cada aviso de WhatsApp se procesa una sola vez (Meta reintenta).
        Schema::create('mkt_webhook_eventos', function (Blueprint $table) {
            $table->id();
            $table->string('huella', 64)->unique();
            $table->string('tipo', 20); // estado | mensaje
            $table->string('wamid', 191)->nullable()->index();
            $table->string('valor', 40)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        // Tolerante: si una vuelta anterior se quedó a la mitad, no truena.
        $fk = function (string $tabla, string $columna) {
            if (! Schema::hasColumn($tabla, $columna)) {
                return;
            }
            try {
                Schema::table($tabla, fn (Blueprint $t) => $t->dropForeign([$columna]));
            } catch (\Throwable) {
            }
            Schema::table($tabla, fn (Blueprint $t) => $t->dropColumn($columna));
        };
        $col = function (string $tabla, array $columnas) {
            $hay = array_values(array_filter($columnas, fn ($c) => Schema::hasColumn($tabla, $c)));
            if ($hay) {
                Schema::table($tabla, fn (Blueprint $t) => $t->dropColumn($hay));
            }
        };
        $idx = function (string $tabla, string $metodo, array $cols) {
            try {
                Schema::table($tabla, fn (Blueprint $t) => $t->{$metodo}($cols));
            } catch (\Throwable) {
            }
        };

        Schema::dropIfExists('mkt_webhook_eventos');
        Schema::dropIfExists('mkt_consentimientos');
        Schema::dropIfExists('mkt_eventos');

        $fk('cotizaciones', 'campana_destinatario_id');
        foreach (['cotizaciones', 'ventas'] as $tabla) {
            $fk($tabla, 'campana_id');
            $fk($tabla, 'cupon_id');
            $col($tabla, ['cupon_codigo', 'cupon_descuento']);
        }

        Schema::dropIfExists('mkt_cupon_usos');

        // MySQL no suelta un índice que sostiene una llave foránea: primero las llaves.
        $idx('campana_destinatarios', 'dropForeign', ['campana_id']);
        $idx('campana_destinatarios', 'dropForeign', ['cliente_id']);
        $idx('campana_destinatarios', 'dropUnique', ['campana_id', 'clave']);
        $idx('campana_destinatarios', 'dropIndex', ['whatsapp_message_id']);
        $idx('campana_destinatarios', 'dropIndex', ['cliente_id', 'enviado_en']);
        $fk('campana_destinatarios', 'cupon_id');
        $col('campana_destinatarios', ['clave', 'es_prueba', 'token', 'telefono', 'simulado', 'intentos', 'error_codigo',
            'fallo_en', 'clics', 'primer_clic_en', 'ultimo_clic_en']);

        // Se deja todo como estaba: llave única y foráneas originales.
        $idx('campana_destinatarios', 'unique', ['campana_id', 'cliente_id']);
        try {
            Schema::table('campana_destinatarios', function (Blueprint $t) {
                $t->foreign('campana_id')->references('id')->on('campanas')->cascadeOnDelete();
                $t->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
            });
        } catch (\Throwable) {
        }

        Schema::dropIfExists('campana_items');

        $idx('campanas', 'dropIndex', ['estado']);
        foreach (['cupon_id', 'actualizado_por', 'enviado_por', 'cancelado_por'] as $c) {
            $fk('campanas', $c);
        }
        $col('campanas', ['titulo_oferta', 'plantilla', 'imagen_path', 'cupon_exclusivo', 'vigencia_inicio', 'vigencia_fin',
            'iniciada_en', 'pausada_en', 'terminada_en', 'cancelada_en', 'prueba_enviada_en']);

        Schema::dropIfExists('mkt_cupon_alcances');
        Schema::dropIfExists('mkt_cupones');
    }
};

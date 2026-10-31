<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            if (! Schema::hasColumn('cotizaciones', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('folio')
                    ->constrained('clientes')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('cotizaciones', 'seller_id')) {
                $table->foreignId('seller_id')->nullable()->after('customer_id')
                    ->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('cotizaciones', 'congreso_id')) {
                $table->foreignId('congreso_id')->nullable()->after('seller_id')
                    ->constrained('congresos_eventos')->nullOnDelete();
            }

            if (! Schema::hasColumn('cotizaciones', 'lugar_propuesta')) {
                $table->string('lugar_propuesta')->nullable()->after('congreso_id');
            }

            if (! Schema::hasColumn('cotizaciones', 'nota_cliente')) {
                $table->text('nota_cliente')->nullable()->after('lugar_propuesta');
            }

            if (! Schema::hasColumn('cotizaciones', 'modalidad')) {
                $table->enum('modalidad', ['contado', 'financiamiento'])->default('contado')->after('nota_cliente');
            }

            if (! Schema::hasColumn('cotizaciones', 'aplica_iva')) {
                $table->boolean('aplica_iva')->default(false)->after('modalidad');
            }

            if (! Schema::hasColumn('cotizaciones', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0)->after('aplica_iva');
            }

            if (! Schema::hasColumn('cotizaciones', 'descuento_tipo')) {
                $table->enum('descuento_tipo', ['porcentaje', 'monto'])->nullable()->after('subtotal');
            }

            if (! Schema::hasColumn('cotizaciones', 'descuento_valor')) {
                $table->decimal('descuento_valor', 12, 2)->default(0)->after('descuento_tipo');
            }

            if (! Schema::hasColumn('cotizaciones', 'descuento_monto')) {
                $table->decimal('descuento_monto', 12, 2)->default(0)->after('descuento_valor');
            }

            if (! Schema::hasColumn('cotizaciones', 'envio')) {
                $table->decimal('envio', 12, 2)->default(0)->after('descuento_monto');
            }

            if (! Schema::hasColumn('cotizaciones', 'iva_monto')) {
                $table->decimal('iva_monto', 12, 2)->default(0)->after('envio');
            }

            if (! Schema::hasColumn('cotizaciones', 'valor_a_cuenta')) {
                $table->decimal('valor_a_cuenta', 12, 2)->default(0)->after('iva_monto');
            }

            if (! Schema::hasColumn('cotizaciones', 'total')) {
                $table->decimal('total', 12, 2)->default(0)->after('valor_a_cuenta');
            }

            if (! Schema::hasColumn('cotizaciones', 'total_contrato')) {
                $table->decimal('total_contrato', 12, 2)->default(0)->after('total');
            }

            if (! Schema::hasColumn('cotizaciones', 'plan_nombre')) {
                $table->string('plan_nombre')->nullable()->after('total_contrato');
            }

            if (! Schema::hasColumn('cotizaciones', 'num_meses')) {
                $table->unsignedInteger('num_meses')->default(0)->after('plan_nombre');
            }

            if (! Schema::hasColumn('cotizaciones', 'garantia_meses')) {
                $table->unsignedSmallInteger('garantia_meses')->default(6)->after('num_meses');
            }

            if (! Schema::hasColumn('cotizaciones', 'estado')) {
                $table->enum('estado', ['borrador', 'enviada', 'aceptada', 'rechazada', 'convertida'])
                    ->default('borrador')->after('garantia_meses');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $columns = [
                'estado',
                'garantia_meses',
                'num_meses',
                'plan_nombre',
                'total_contrato',
                'total',
                'valor_a_cuenta',
                'iva_monto',
                'envio',
                'descuento_monto',
                'descuento_valor',
                'descuento_tipo',
                'subtotal',
                'aplica_iva',
                'modalidad',
                'nota_cliente',
                'lugar_propuesta',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('cotizaciones', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('cotizaciones', 'congreso_id')) {
                $table->dropConstrainedForeignId('congreso_id');
            }

            if (Schema::hasColumn('cotizaciones', 'seller_id')) {
                $table->dropConstrainedForeignId('seller_id');
            }

            if (Schema::hasColumn('cotizaciones', 'customer_id')) {
                $table->dropConstrainedForeignId('customer_id');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nómina configurable por RH: reglas (bonos y descuentos automáticos según
 * la asistencia), conceptos fijos por persona, y quincenas cerradas con su
 * desglose congelado. Nada de esto vive en el código: lo define RH en pantalla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->string('value', 255)->nullable();
        });

        Schema::create('payroll_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('kind', 10);                          // bono | descuento
            $table->json('conditions');                          // [{metric, scope, op, value}] — todas deben cumplirse
            $table->string('repeat_mode', 10)->default('una_vez');   // una_vez | por_cada
            $table->string('repeat_metric', 30)->nullable();
            $table->unsignedSmallInteger('repeat_every')->nullable();
            $table->unsignedSmallInteger('repeat_max')->nullable();  // tope de repeticiones
            $table->string('calc', 12)->default('monto');        // monto | dias_sueldo | pct_sueldo
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('timing', 12)->default('periodo');    // periodo | cierre_mes
            $table->string('scope', 10)->default('todos');       // todos | incluir | excluir
            $table->json('user_ids')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('system_key', 40)->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Bonos o descuentos fijos de una persona (transporte, préstamo, comisión…)
        Schema::create('payroll_concepts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('kind', 10);                          // bono | descuento
            $table->string('name', 150);
            $table->decimal('amount', 10, 2);
            $table->string('recurrence', 14)->default('cada_periodo');   // cada_periodo | unica
            $table->date('period_start')->nullable();            // para "unica": la quincena en que aplica
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'recurrence']);
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 10)->default('cerrada');    // cerrada | pagada
            $table->decimal('total_sueldos', 12, 2)->default(0);
            $table->decimal('total_bonos', 12, 2)->default(0);
            $table->decimal('total_descuentos', 12, 2)->default(0);
            $table->decimal('total_neto', 12, 2)->default(0);
            $table->unsignedSmallInteger('employees')->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['period_start', 'period_end']);
        });

        Schema::create('payroll_run_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 150);
            $table->string('employee_number', 100)->nullable();
            $table->decimal('sueldo', 10, 2)->default(0);
            $table->decimal('bonos', 10, 2)->default(0);
            $table->decimal('descuentos', 10, 2)->default(0);
            $table->decimal('neto', 10, 2)->default(0);
            $table->json('snapshot');                            // métricas + renglones del desglose
            $table->timestamps();
        });

        $now = now();
        $bono = (float) config('asistencia.prenomina.bono_puntualidad', 300);

        DB::table('payroll_settings')->insert([
            ['key' => 'dias_sueldo', 'value' => '15'],   // un día de sueldo = sueldo quincenal ÷ 15
        ]);

        // Reglas de arranque: RH las edita, apaga o borra desde la pantalla
        DB::table('payroll_rules')->insert([
            [
                'name' => 'Descuento por falta', 'kind' => 'descuento', 'system_key' => 'falta',
                'description' => 'Cada falta de la quincena descuenta un día de sueldo.',
                'conditions' => json_encode([['metric' => 'faltas', 'scope' => 'periodo', 'op' => '>=', 'value' => 1]]),
                'repeat_mode' => 'por_cada', 'repeat_metric' => 'faltas', 'repeat_every' => 1, 'repeat_max' => null,
                'calc' => 'dias_sueldo', 'amount' => 1, 'timing' => 'periodo', 'scope' => 'todos', 'active' => true, 'sort' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'Tres retardos = una falta', 'kind' => 'descuento', 'system_key' => 'retardos_falta',
                'description' => 'Por cada 3 retardos en la quincena se descuenta un día de sueldo, como si hubiera faltado.',
                'conditions' => json_encode([['metric' => 'retardos', 'scope' => 'periodo', 'op' => '>=', 'value' => 3]]),
                'repeat_mode' => 'por_cada', 'repeat_metric' => 'retardos', 'repeat_every' => 3, 'repeat_max' => null,
                'calc' => 'dias_sueldo', 'amount' => 1, 'timing' => 'periodo', 'scope' => 'todos', 'active' => false, 'sort' => 2,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'Bono de puntualidad', 'kind' => 'bono', 'system_key' => 'puntualidad',
                'description' => 'Si en todo el mes no hubo retardos ni faltas, se paga al cerrar el mes.',
                'conditions' => json_encode([
                    ['metric' => 'retardos', 'scope' => 'mes', 'op' => '=', 'value' => 0],
                    ['metric' => 'faltas', 'scope' => 'mes', 'op' => '=', 'value' => 0],
                ]),
                'repeat_mode' => 'una_vez', 'repeat_metric' => null, 'repeat_every' => null, 'repeat_max' => null,
                'calc' => 'monto', 'amount' => $bono, 'timing' => 'cierre_mes', 'scope' => 'todos', 'active' => true, 'sort' => 3,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_lines');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_concepts');
        Schema::dropIfExists('payroll_rules');
        Schema::dropIfExists('payroll_settings');
    }
};

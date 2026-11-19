<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reglas que se pagan en una fecha (bono anual o bono único): timing = 'fecha'
 * con el día en que se paga y si se repite cada año.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_rules', 'pay_date')) {
                $table->date('pay_date')->nullable()->after('timing');
            }
            if (! Schema::hasColumn('payroll_rules', 'yearly')) {
                $table->boolean('yearly')->default(true)->after('pay_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_rules', function (Blueprint $table) {
            $table->dropColumn(['pay_date', 'yearly']);
        });
    }
};

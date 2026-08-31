<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Sin after(): las columnas 'position' y 'payroll_number' no existen
            // en la tabla users, y MySQL falla si el AFTER apunta a una columna
            // inexistente. El orden fisico de las columnas no afecta al modelo.
            $this->addColumnIfMissing($table, 'cargo', fn () => $table->string('cargo')->nullable());
            $this->addColumnIfMissing($table, 'checador_id', fn () => $table->string('checador_id')->nullable()->unique());
            $this->addColumnIfMissing($table, 'approval_pin_hash', fn () => $table->string('approval_pin_hash')->nullable()->after('password'));
            $this->addColumnIfMissing($table, 'curp', fn () => $table->string('curp', 18)->nullable()->unique()->after('cargo'));
            $this->addColumnIfMissing($table, 'ine', fn () => $table->string('ine')->nullable()->after('curp'));
            $this->addColumnIfMissing($table, 'acta_nacimiento', fn () => $table->string('acta_nacimiento')->nullable()->after('ine'));
            $this->addColumnIfMissing($table, 'licencia', fn () => $table->string('licencia')->nullable()->after('acta_nacimiento'));
            $this->addColumnIfMissing($table, 'domicilio', fn () => $table->text('domicilio')->nullable()->after('licencia'));
            $this->addColumnIfMissing($table, 'fecha_ingreso', fn () => $table->date('fecha_ingreso')->nullable()->after('domicilio'));
            $this->addColumnIfMissing($table, 'vacaciones_disponibles', fn () => $table->unsignedInteger('vacaciones_disponibles')->default(0)->after('fecha_ingreso'));
            $this->addColumnIfMissing($table, 'nombre_contacto_emergencia', fn () => $table->string('nombre_contacto_emergencia')->nullable()->after('vacaciones_disponibles'));
            $this->addColumnIfMissing($table, 'numero_contacto_emergencia', fn () => $table->string('numero_contacto_emergencia')->nullable()->after('nombre_contacto_emergencia'));
            $this->addColumnIfMissing($table, 'domicilio_contacto_emergencia', fn () => $table->text('domicilio_contacto_emergencia')->nullable()->after('numero_contacto_emergencia'));
            $this->addColumnIfMissing($table, 'nombre_contacto_emergencia_secundario', fn () => $table->string('nombre_contacto_emergencia_secundario')->nullable()->after('domicilio_contacto_emergencia'));
            $this->addColumnIfMissing($table, 'numero_contacto_emergencia_secundario', fn () => $table->string('numero_contacto_emergencia_secundario')->nullable()->after('nombre_contacto_emergencia_secundario'));
            $this->addColumnIfMissing($table, 'domicilio_contacto_emergencia_secundario', fn () => $table->text('domicilio_contacto_emergencia_secundario')->nullable()->after('numero_contacto_emergencia_secundario'));
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'cargo', 'checador_id', 'approval_pin_hash', 'curp', 'ine', 'acta_nacimiento',
                'licencia', 'domicilio', 'fecha_ingreso', 'vacaciones_disponibles',
                'nombre_contacto_emergencia', 'numero_contacto_emergencia', 'domicilio_contacto_emergencia',
                'nombre_contacto_emergencia_secundario', 'numero_contacto_emergencia_secundario',
                'domicilio_contacto_emergencia_secundario',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function addColumnIfMissing(Blueprint $table, string $column, callable $definer): void
    {
        if (! Schema::hasColumn('users', $column)) {
            $definer();
        }
    }
};

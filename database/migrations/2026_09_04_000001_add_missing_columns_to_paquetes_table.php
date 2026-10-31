<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('paquetes')) {
            return;
        }

        if (! Schema::hasColumn('paquetes', 'descripcion')) {
            Schema::table('paquetes', function (Blueprint $table) {
                $table->text('descripcion')->nullable()->after('nombre');
            });
        }

        if (! Schema::hasColumn('paquetes', 'precio')) {
            Schema::table('paquetes', function (Blueprint $table) {
                $table->decimal('precio', 12, 2)->default(0)->after('descripcion');
            });
        }

        if (! Schema::hasColumn('paquetes', 'imagen')) {
            Schema::table('paquetes', function (Blueprint $table) {
                $table->string('imagen')->nullable()->after('precio');
            });
        }

        if (! Schema::hasColumn('paquetes', 'activo')) {
            Schema::table('paquetes', function (Blueprint $table) {
                $table->boolean('activo')->default(true)->after('imagen');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('paquetes')) {
            return;
        }

        foreach (['activo', 'imagen', 'precio', 'descripcion'] as $column) {
            if (Schema::hasColumn('paquetes', $column)) {
                Schema::table('paquetes', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};

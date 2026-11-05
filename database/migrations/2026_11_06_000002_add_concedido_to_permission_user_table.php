<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permiso individual por usuario: concedido=1 se le da aunque su rol no
 * lo tenga; concedido=0 se le quita aunque su rol sí lo tenga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permission_user', function (Blueprint $table) {
            $table->boolean('concedido')->default(true)->after('permission_id');
        });
    }

    public function down(): void
    {
        Schema::table('permission_user', function (Blueprint $table) {
            $table->dropColumn('concedido');
        });
    }
};

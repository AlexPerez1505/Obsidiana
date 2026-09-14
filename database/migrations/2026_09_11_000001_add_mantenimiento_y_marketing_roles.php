<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Roles que un usuario puede elegir para su cuenta al registrarse.
 *
 * El rol solo etiqueta la cuenta; los permisos que trae se ajustan
 * después en Configuración → Roles y permisos.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['name' => 'mantenimiento', 'label' => 'Mantenimiento'],
            ['name' => 'marketing', 'label' => 'Marketing'],
        ] as $role) {
            DB::table('roles')->insertOrIgnore([
                'name' => $role['name'],
                'label' => $role['label'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->whereIn('name', ['mantenimiento', 'marketing'])->delete();
    }
};

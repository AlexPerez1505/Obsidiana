<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Marketing entra a Vehículos y Viáticos (los usa para viajes y eventos).
 *
 * Las llaves viven en CatalogoPermisos; aquí solo se garantiza que la
 * fila exista y quede asignada al rol, para que el rol funcione igual en
 * una base recién migrada que en una ya en uso.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permisos = [
            'vehiculos.ver',
            'vehiculos.editar',
            'viaticos.ver',
            'viaticos.editar',
        ];

        foreach ($permisos as $permiso) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permiso,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('roles')->updateOrInsert(
            ['name' => 'marketing'],
            ['label' => 'Marketing', 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
        );

        $roleId = DB::table('roles')->where('name', 'marketing')->value('id');
        $permissionIds = DB::table('permissions')->whereIn('name', $permisos)->pluck('id');

        DB::table('permission_role')->insertOrIgnore(
            $permissionIds->map(fn (int $permissionId) => [
                'permission_id' => $permissionId,
                'role_id' => $roleId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', 'marketing')->value('id');

        if ($roleId) {
            $permissionIds = DB::table('permissions')->whereIn('name', [
                'vehiculos.ver',
                'vehiculos.editar',
                'viaticos.ver',
                'viaticos.editar',
            ])->pluck('id');

            DB::table('permission_role')
                ->where('role_id', $roleId)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }
    }
};

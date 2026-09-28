<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Marketing tiene todas las funciones de Gestión de Inventario:
 * entradas/salidas, órdenes de salida, procesos, escaneo, productos,
 * fichas, congresos, catálogo y precios.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permisos = [
            'inventario.ver',
            'inventario.registrar',
            'inventario.editar',
            'inventario.eliminar',
            'inventario.escanear',
            'inventario.catalogo',
            'salidas.ver',
            'salidas.preparar',
            'procesos.ver',
            'procesos.trabajar',
            'procesos.descartar',
            'congresos.ver',
            'congresos.editar',
            'precios.ver',
            'precios.editar',
        ];

        foreach ($permisos as $permiso) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permiso,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleId = DB::table('roles')->where('name', 'marketing')->value('id');

        if (! $roleId) {
            return;
        }

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

        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')->whereIn('name', [
            'inventario.ver',
            'inventario.registrar',
            'inventario.editar',
            'inventario.eliminar',
            'inventario.escanear',
            'inventario.catalogo',
            'salidas.ver',
            'salidas.preparar',
            'procesos.ver',
            'procesos.trabajar',
            'procesos.descartar',
            'congresos.ver',
            'congresos.editar',
            'precios.ver',
            'precios.editar',
        ])->pluck('id');

        DB::table('permission_role')
            ->where('role_id', $roleId)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }
};

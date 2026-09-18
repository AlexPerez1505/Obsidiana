<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('roles')->updateOrInsert(
            ['name' => 'ventas'],
            [
                'label' => 'Ventas',
                'description' => 'Atiende clientes, cotiza, cierra ventas y cobra.',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $roleId = DB::table('roles')->where('name', 'ventas')->value('id');
        $permissionIds = DB::table('permissions')->whereIn('name', [
            'clientes.ver',
            'clientes.crear',
            'clientes.editar',
            'cotizaciones.ver',
            'cotizaciones.ver_todas',
            'cotizaciones.crear',
            'cotizaciones.editar',
            'ventas.ver',
            'ventas.ver_todas',
            'ventas.crear',
            'ventas.editar',
            'cobranza.ver',
            'cobranza.registrar',
            'facturacion.ver',
            'facturacion.crear',
            'inventario.ver',
            'precios.ver',
            'servicios.ver',
        ])->pluck('id');

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
        $roleId = DB::table('roles')->where('name', 'ventas')->value('id');

        if ($roleId) {
            DB::table('permission_role')->where('role_id', $roleId)->delete();
            DB::table('role_user')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
};

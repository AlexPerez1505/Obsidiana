<?php

use App\Support\CatalogoPermisos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Da de alta los permisos de la agenda y los concede para que nadie pierda
 * acceso: hoy cualquier usuario entra a la agenda, así que `agenda.ver` va
 * a todos los roles. El rol "supervisor" recibe además consultar y
 * reasignar al equipo, publicar eventos de empresa y ver la carga.
 * Se puede ajustar después en Roles y permisos. Solo agrega, no quita nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        $grupo = CatalogoPermisos::grupos()['agenda'] ?? null;
        if (! $grupo) {
            return;
        }

        foreach ($grupo['permisos'] as $nombre => $etiqueta) {
            DB::table('permissions')->updateOrInsert(['name' => $nombre], [
                'label' => $etiqueta,
                'description' => $grupo['titulo'],
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        $id = fn (string $n) => DB::table('permissions')->where('name', $n)->value('id');
        $conceder = function (int $rol, string $permiso) use ($id) {
            DB::table('permission_role')->insertOrIgnore(['role_id' => $rol, 'permission_id' => $id($permiso), 'created_at' => now(), 'updated_at' => now()]);
        };

        foreach (DB::table('roles')->pluck('id', 'name') as $nombre => $rol) {
            $conceder($rol, 'agenda.ver');
            if ($nombre === 'supervisor') {
                foreach (['agenda.ver_todos', 'agenda.reasignar', 'agenda.empresa', 'agenda.carga', 'agenda.equipos'] as $p) {
                    $conceder($rol, $p);
                }
            }
        }
    }

    public function down(): void
    {
        // Los permisos se quedan: quitarlos le quitaría acceso a roles reales.
    }
};

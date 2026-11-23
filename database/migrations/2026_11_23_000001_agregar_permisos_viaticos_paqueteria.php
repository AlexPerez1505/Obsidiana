<?php

use App\Support\CatalogoPermisos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $grupos = CatalogoPermisos::grupos();

        foreach (['administracion'] as $nombreGrupo) {
            $grupo = $grupos[$nombreGrupo] ?? null;
            if (! $grupo) {
                continue;
            }

            foreach ($grupo['permisos'] as $nombre => $etiqueta) {
                if (! str_starts_with($nombre, 'viaticos.') && ! str_starts_with($nombre, 'paqueteria.')) {
                    continue;
                }

                DB::table('permissions')->updateOrInsert(['name' => $nombre], [
                    'label' => $etiqueta,
                    'description' => $grupo['titulo'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // No se eliminan permisos: podrían haberse asignado a roles reales.
    }
};

<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Support\CatalogoPermisos;
use Illuminate\Console\Command;

/**
 * Pone la tabla de permisos al día con el catálogo del código.
 *
 * Se corre después de agregar o quitar un permiso en CatalogoPermisos.
 * Los que ya no existen en el código se avisan pero NO se borran solos:
 * borrarlos quitaría permisos concedidos a roles reales, y eso tiene que
 * ser una decisión, no un efecto secundario de un despliegue.
 */
class SincronizarPermisos extends Command
{
    protected $signature = 'permisos:sincronizar {--limpiar : Borra también los permisos que ya no están en el código}';

    protected $description = 'Sincroniza la tabla de permisos con el catálogo del código';

    public function handle(): int
    {
        $enCodigo = CatalogoPermisos::llaves();
        $nuevos = 0;
        $actualizados = 0;

        foreach (CatalogoPermisos::grupos() as $grupo) {
            foreach ($grupo['permisos'] as $llave => $etiqueta) {
                $permiso = Permission::firstOrNew(['name' => $llave]);
                $existia = $permiso->exists;

                $permiso->label = $etiqueta;
                $permiso->description = $grupo['titulo'];

                if (! $existia) {
                    $permiso->save();
                    $nuevos++;
                } elseif ($permiso->isDirty()) {
                    $permiso->save();
                    $actualizados++;
                }
            }
        }

        $this->info("Permisos nuevos: {$nuevos} · actualizados: {$actualizados} · total en el código: ".count($enCodigo));

        $sobrantes = Permission::whereNotIn('name', $enCodigo)->get();

        if ($sobrantes->isEmpty()) {
            return self::SUCCESS;
        }

        $this->warn('Hay '.$sobrantes->count().' permiso(s) en la base que ya no existen en el código:');

        foreach ($sobrantes as $p) {
            $this->line('  - '.$p->name.' (asignado a '.$p->roles()->count().' rol/es)');
        }

        if (! $this->option('limpiar')) {
            $this->line('Se conservan. Usa --limpiar si de verdad quieres borrarlos.');

            return self::SUCCESS;
        }

        Permission::whereIn('id', $sobrantes->pluck('id'))->delete();
        $this->info('Borrados.');

        return self::SUCCESS;
    }
}

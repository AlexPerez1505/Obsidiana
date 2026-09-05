<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Support\CatalogoPermisos;
use Illuminate\Console\Command;

/**
 * Crea los roles con los que arranca la operación.
 *
 * No es una configuración definitiva: es un punto de partida razonable para
 * no tener que marcar 39 casillas desde cero. Lo que quede de cada rol se
 * ajusta después en Configuración → Roles y permisos.
 *
 * Es idempotente y NO pisa lo ya configurado: si un rol ya existe, se
 * respetan sus permisos tal como estén. Con --rehacer se reescriben.
 */
class CrearRolesBase extends Command
{
    protected $signature = 'roles:base {--rehacer : Reescribe los permisos de los roles que ya existen}';

    protected $description = 'Crea los roles de arranque (ventas, almacén, marketing) con permisos razonables';

    /**
     * @return array<string, array{label: string, description: string, permisos: array<string>}>
     */
    private function plantillas(): array
    {
        return [
            'ventas' => [
                'label' => 'Ventas',
                'description' => 'Atiende clientes, cotiza, cierra ventas y cobra.',
                'permisos' => [
                    'clientes.ver', 'clientes.crear', 'clientes.editar',
                    'cotizaciones.ver', 'cotizaciones.crear', 'cotizaciones.editar',
                    'ventas.ver', 'ventas.crear',
                    'cobranza.ver', 'cobranza.registrar',
                    'facturacion.ver', 'facturacion.crear',
                    // Ve inventario y precios porque sin eso no puede cotizar.
                    'inventario.ver', 'precios.ver',
                    'servicios.ver',
                ],
            ],

            'almacen' => [
                'label' => 'Almacén',
                'description' => 'Recibe equipo, lo identifica y lo mueve por procesos.',
                'permisos' => [
                    'inventario.ver', 'inventario.registrar', 'inventario.editar',
                    'inventario.escanear', 'inventario.catalogo',
                    'procesos.ver', 'procesos.trabajar',
                    'servicios.ver',
                    // Sin precios.ver: registra y repara sin ver cuánto vale.
                ],
            ],

            'marketing' => [
                'label' => 'Marketing',
                'description' => 'Solo entra a Marketing y congresos.',
                'permisos' => [
                    'marketing.ver', 'marketing.editar',
                    'congresos.ver',
                ],
            ],
        ];
    }

    public function handle(): int
    {
        // Si el catálogo no está en la base, los sync() de abajo no encuentran
        // nada y los roles saldrían vacíos sin decir por qué.
        if (Permission::whereIn('name', CatalogoPermisos::llaves())->count() === 0) {
            $this->error('No hay permisos en la base. Corre primero: php artisan permisos:sincronizar');

            return self::FAILURE;
        }

        foreach ($this->plantillas() as $name => $plantilla) {
            $role = Role::firstOrNew(['name' => $name]);
            $eraNuevo = ! $role->exists;

            if (! $eraNuevo && ! $this->option('rehacer')) {
                $this->line("  <fg=yellow>=</> {$plantilla['label']} ya existe, se deja como está ({$role->permissions()->count()} permisos)");

                continue;
            }

            $role->fill([
                'label' => $plantilla['label'],
                'description' => $plantilla['description'],
                'is_active' => true,
            ])->save();

            $ids = Permission::whereIn('name', $plantilla['permisos'])->pluck('id');
            $role->permissions()->sync($ids);

            $faltantes = count($plantilla['permisos']) - $ids->count();
            $this->line(sprintf(
                '  <fg=green>%s</> %s · %d permisos%s',
                $eraNuevo ? '+' : '~',
                $plantilla['label'],
                $ids->count(),
                $faltantes > 0 ? " (<fg=red>{$faltantes} sin encontrar</>)" : ''
            ));
        }

        $this->newLine();
        $this->info('Listo. Ajusta lo que sobre o falte en Configuración → Roles y permisos.');

        return self::SUCCESS;
    }
}

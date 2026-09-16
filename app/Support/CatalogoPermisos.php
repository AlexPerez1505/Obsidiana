<?php

namespace App\Support;

/**
 * Todo lo que se puede permitir o negar en el sistema.
 *
 * El catálogo vive en código, no en la base, porque un permiso solo
 * significa algo si hay código que lo revisa. Guardarlos sueltos en una
 * tabla llevaba a permisos huérfanos que nadie consultaba y a llaves mal
 * escritas que jamás se cumplían.
 *
 * Lo que sí vive en la base es la asignación: qué rol tiene cuál permiso.
 * Se sincroniza con `php artisan permisos:sincronizar`.
 *
 * El administrador no aparece aquí: siempre puede todo, y eso se resuelve
 * antes de consultar permisos (ver AppServiceProvider).
 */
class CatalogoPermisos
{
    /**
     * Permisos agrupados por módulo, como se ven en la pantalla de roles.
     *
     * @return array<string, array{titulo: string, descripcion: string, permisos: array<string, string>}>
     */
    public static function grupos(): array
    {
        return [
            'clientes' => [
                'titulo' => 'Clientes',
                'descripcion' => 'El directorio comercial.',
                'permisos' => [
                    'clientes.ver' => 'Ver clientes y su detalle',
                    'clientes.crear' => 'Registrar clientes nuevos',
                    'clientes.editar' => 'Editar datos de un cliente',
                    'clientes.eliminar' => 'Eliminar clientes',
                ],
            ],

            'cotizaciones' => [
                'titulo' => 'Cotizaciones',
                'descripcion' => 'Propuestas antes de cerrar la venta.',
                'permisos' => [
                    'cotizaciones.ver' => 'Ver cotizaciones',
                    'cotizaciones.crear' => 'Crear cotizaciones',
                    'cotizaciones.editar' => 'Editar cotizaciones',
                    'cotizaciones.eliminar' => 'Eliminar cotizaciones',
                ],
            ],

            'ventas' => [
                'titulo' => 'Ventas',
                'descripcion' => 'Cierre de venta, contratos y garantías.',
                'permisos' => [
                    'ventas.ver' => 'Ver ventas',
                    'ventas.crear' => 'Registrar ventas',
                    'ventas.editar' => 'Editar ventas ya registradas',
                    'ventas.eliminar' => 'Eliminar ventas',
                ],
            ],

            'cobranza' => [
                'titulo' => 'Cobranza',
                'descripcion' => 'Lo que deben y los pagos que entran.',
                'permisos' => [
                    'cobranza.ver' => 'Ver saldos y quién debe',
                    'cobranza.registrar' => 'Registrar pagos y emitir recibos',
                    'cobranza.ajustar' => 'Mover fechas y montos del plan de pagos',
                ],
            ],

            'facturacion' => [
                'titulo' => 'Facturación',
                'descripcion' => 'Borradores de factura.',
                'permisos' => [
                    'facturacion.ver' => 'Ver facturas',
                    'facturacion.crear' => 'Crear borradores de factura',
                ],
            ],

            'inventario' => [
                'titulo' => 'Inventario',
                'descripcion' => 'Lo que entra, lo que hay y lo que se identifica.',
                'permisos' => [
                    'inventario.ver' => 'Ver inventario, productos y equipos',
                    'inventario.registrar' => 'Registrar entradas de equipo',
                    'inventario.editar' => 'Editar productos y equipos',
                    'inventario.eliminar' => 'Eliminar entradas y productos',
                    'inventario.escanear' => 'Usar la pistola lectora',
                    'inventario.catalogo' => 'Administrar el catálogo (tipos, marcas, modelos)',
                ],
            ],

            'procesos' => [
                'titulo' => 'Procesos',
                'descripcion' => 'Hojalatería, mantenimiento y limpieza.',
                'permisos' => [
                    'procesos.ver' => 'Ver las colas de trabajo',
                    'procesos.trabajar' => 'Tomar piezas y cerrar procesos',
                    'procesos.descartar' => 'Descartar un proceso que no hacía falta',
                ],
            ],

            'precios' => [
                'titulo' => 'Precios',
                'descripcion' => 'Aparte del resto: no todos deben ver cuánto cuesta.',
                'permisos' => [
                    'precios.ver' => 'Ver los precios de venta en Inventario',
                    'precios.editar' => 'Definir y cambiar precios de venta',
                ],
            ],

            'marketing' => [
                'titulo' => 'Marketing',
                'descripcion' => 'Guía de marca, calendario y flyers.',
                'permisos' => [
                    'marketing.ver' => 'Entrar a Marketing',
                    'marketing.editar' => 'Editar contenido de Marketing',
                    'marketing.aprobar' => 'Aprobar flyers',
                ],
            ],

            'servicios' => [
                'titulo' => 'Servicios',
                'descripcion' => 'Órdenes de servicio y técnicos.',
                'permisos' => [
                    'servicios.ver' => 'Ver historial de servicios',
                    'servicios.crear' => 'Crear órdenes de servicio',
                ],
            ],

            'administracion' => [
                'titulo' => 'Administración',
                'descripcion' => 'Quién entra al sistema y qué puede hacer.',
                'permisos' => [
                    'usuarios.ver' => 'Ver el panel de usuarios',
                    'usuarios.aprobar' => 'Aprobar o rechazar cuentas nuevas',
                    'usuarios.editar' => 'Editar usuarios y asignarles roles',
                    'roles.gestionar' => 'Crear roles y definir qué puede cada uno',
                    'congresos.ver' => 'Ver congresos',
                    'congresos.editar' => 'Crear y editar congresos',
                ],
            ],
        ];
    }

    /** Todas las llaves, aplanadas. */
    public static function llaves(): array
    {
        return collect(static::grupos())
            ->flatMap(fn (array $g) => array_keys($g['permisos']))
            ->all();
    }

    /** El texto de un permiso, para mostrarlo en pantalla. */
    public static function etiqueta(string $llave): string
    {
        foreach (static::grupos() as $grupo) {
            if (isset($grupo['permisos'][$llave])) {
                return $grupo['permisos'][$llave];
            }
        }

        return $llave;
    }

    /** ¿Existe este permiso? Evita guardar llaves inventadas. */
    public static function existe(string $llave): bool
    {
        return in_array($llave, static::llaves(), true);
    }
}

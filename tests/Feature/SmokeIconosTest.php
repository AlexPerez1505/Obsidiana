<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prueba temporal: tras reemplazar cientos de <svg> por componentes
 * <x-gravityui-*>, php -l solo valida el PHP embebido, no que Blade
 * compile el componente correctamente. Esta prueba visita las páginas
 * más afectadas para atrapar cualquier error 500 de compilación Blade
 * (ícono mal escrito, tag sin cerrar, etc.) antes de confiar en el
 * cambio. Se puede borrar una vez confirmado que todo carga bien.
 */
class SmokeIconosTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioAdmin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    /** @dataProvider nombresDeRuta */
    public function test_la_pagina_carga_sin_error_500(string $nombreRuta): void
    {
        $user = $this->usuarioAdmin();

        $response = $this->actingAs($user)->get(route($nombreRuta));

        $this->assertNotSame(500, $response->status(), "La ruta {$nombreRuta} regresó 500.");
    }

    public function test_ficha_de_usuario_carga_sin_error_500(): void
    {
        $user = $this->usuarioAdmin();

        $response = $this->actingAs($user)->get(route('admin.users.show', $user));

        $this->assertNotSame(500, $response->status());
    }

    public function test_permisos_carga_sin_error_500(): void
    {
        $user = $this->usuarioAdmin();

        $response = $this->actingAs($user)->get(route('admin.permissions.index'));

        $this->assertNotSame(500, $response->status());
    }

    public static function nombresDeRuta(): array
    {
        return [
            ['dashboard'],
            ['commercial.promociones.index'],
            ['commercial.promociones.campanas.index'],
            ['commercial.promociones.campanas.create'],
            ['commercial.clientes.index'],
            ['commercial.clientes.create'],
            ['commercial.cotizaciones.index'],
            ['commercial.cotizaciones.create'],
            ['commercial.ventas.index'],
            ['commercial.ventas.create'],
            ['commercial.facturas.index'],
            ['commercial.facturas.create'],
            ['commercial.cobranza.index'],
            ['inventory.movimientos.index'],
            ['inventory.movimientos.create'],
            ['inventory.productos.index'],
            ['inventory.productos.create'],
            ['inventory.procesos.index'],
            ['inventory.escaneo.index'],
            ['inventory.paquetes.index'],
            ['inventory.paquetes.create'],
            ['inventory.fichas.index'],
            ['inventory.fichas.create'],
            ['configuracion.roles.index'],
            ['configuracion.tipos_equipo.index'],
            ['configuracion.refaciones.index'],
            ['configuracion.catalogos.index'],
            ['admin.users.index'],
            ['admin.vehicles.index'],
            ['admin.agenda.index'],
            ['admin.viatics.index'],
            ['admin.materials.index'],
            ['admin.reports.index'],
            ['marketing.tareas.index'],
            ['marketing.tareas.create'],
            ['marketing.calendario.index'],
            ['marketing.aprobacion_flyers.index'],
            ['marketing.guia_de_marca.index'],
            ['gestion.servicios.historial'],
            ['account'],
        ];
    }
}

<?php

namespace Tests\Feature\Api\V1\Comercial;

use App\Models\Customer;
use App\Models\FichaTecnica;
use App\Models\Paquete;
use App\Models\Producto;
use App\Models\TokenApi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class BuscadoresTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function cliente(User $asesor, string $nombre): Customer
    {
        return Customer::create([
            'nombre' => $nombre,
            'apellido' => 'Prueba',
            'telefono' => (string) random_int(5500000000, 5599999999),
            'gmail' => strtolower($nombre).'@example.com',
            'direccion' => 'Centro',
            'etapa' => 'cliente',
            'activo' => true,
            'recibe_promocion' => false,
            'asesor_id' => $asesor->id,
        ]);
    }

    public function test_buscar_clientes_respeta_el_alcance_y_trae_historial(): void
    {
        $yo = $this->usuarioApi(['cotizaciones.crear']);
        $mio = $this->cliente($yo, 'Roberto');
        $this->cliente($this->usuarioApi(), 'Roberta');

        $this->conToken($yo)->getJson('/api/v1/cotizaciones/buscar/clientes?q=Robert')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mio->id)
            ->assertJsonPath('data.0.gmail', 'roberto@example.com')
            ->assertJsonPath('data.0.historial.compras', 0)
            ->assertJsonPath('data.0.historial.saldo', 0)
            ->assertJsonPath('data.0.historial.ultima_compra', null);
    }

    public function test_buscar_productos_trae_paquetes_y_luego_productos(): void
    {
        $yo = $this->usuarioApi(['ventas.crear']);
        $producto = Producto::create(['tipo_equipo' => 'Gastroscopio', 'marca' => 'Olympus', 'modelo' => 'GIF-180', 'precio' => 85000, 'stock' => 0]);
        $producto->agregarUnidades(2, ['SN-1', 'SN-2']);
        $ficha = FichaTecnica::create(['titulo' => 'Ficha GIF-180', 'activo' => true, 'producto_id' => $producto->id]);
        $paquete = Paquete::create(['nombre' => 'Gastroscopio completo', 'precio' => 120000, 'activo' => true]);
        Paquete::create(['nombre' => 'Gastroscopio viejo', 'precio' => 1, 'activo' => false]);

        $res = $this->conToken($yo)->getJson('/api/v1/cotizaciones/buscar/productos?q=Gastro');

        $res->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.tipo_item', 'paquete')
            ->assertJsonPath('data.0.paquete_id', $paquete->id)
            ->assertJsonPath('data.0.precio', 120000)
            ->assertJsonPath('data.1.tipo_item', 'producto')
            ->assertJsonPath('data.1.producto_id', $producto->id)
            ->assertJsonPath('data.1.precio', 85000)
            ->assertJsonPath('data.1.stock', 2)
            ->assertJsonCount(2, 'data.1.piezas')
            ->assertJsonPath('data.1.ficha.id', $ficha->id);
    }

    public function test_buscar_fichas_solo_activas(): void
    {
        $yo = $this->usuarioApi(['cotizaciones.editar']);
        FichaTecnica::create(['titulo' => 'Torre 4K', 'activo' => true]);
        FichaTecnica::create(['titulo' => 'Torre vieja', 'activo' => false]);

        $this->conToken($yo)->getJson('/api/v1/cotizaciones/buscar/fichas?q=Torre')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titulo', 'Torre 4K');
    }

    public function test_sin_poder_armar_documentos_da_403(): void
    {
        $soloVer = $this->usuarioApi(['cotizaciones.ver', 'clientes.ver']);

        $peticion = $this->conToken($soloVer);
        $peticion->getJson('/api/v1/cotizaciones/buscar/clientes')->assertForbidden();
        $peticion->getJson('/api/v1/cotizaciones/buscar/productos')->assertForbidden();
        $peticion->getJson('/api/v1/cotizaciones/buscar/fichas')->assertForbidden();

        // La llave recorta aunque el dueño sí pueda.
        $creador = $this->usuarioApi(['cotizaciones.crear', 'cotizaciones.ver']);
        $this->conToken($creador, ['cotizaciones.ver'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/cotizaciones/buscar/productos')->assertForbidden();
    }
}

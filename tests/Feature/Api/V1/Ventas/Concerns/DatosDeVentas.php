<?php

namespace Tests\Feature\Api\V1\Ventas\Concerns;

use App\Models\Customer;
use App\Models\EquipmentType;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\Ventas\RegistroDeVentas;

/**
 * Lo que casi todas las pruebas de ventas necesitan: un cliente, un
 * producto con piezas en stock y una venta registrada por el mismo camino
 * que la web y la API (RegistroDeVentas).
 */
trait DatosDeVentas
{
    protected function cliente(array $atributos = []): Customer
    {
        return Customer::create($atributos + ['nombre' => 'Cliente', 'apellido' => 'De Prueba']);
    }

    protected function productoConUnidades(int $cantidad, array $series = [], float $precio = 500): Producto
    {
        $tipo = EquipmentType::create(['name' => 'Torre de endoscopia '.uniqid()]);

        $producto = Producto::create([
            'equipment_type_id' => $tipo->id,
            'tipo_equipo' => $tipo->name,
            'marca' => 'MarcaTest',
            'modelo' => 'ModeloTest',
            'precio' => $precio,
            'stock' => 0,
        ]);

        $producto->agregarUnidades($cantidad, $series);

        return $producto->refresh();
    }

    /** Un renglón de producto en la forma en que se manda a guardar. */
    protected function renglonProducto(Producto $producto, int $cantidad, ?float $precio = null): array
    {
        return [
            'tipo_item' => 'producto',
            'producto_id' => $producto->id,
            'nombre' => trim($producto->marca.' '.$producto->modelo),
            'cantidad' => $cantidad,
            'precio_unitario' => $precio ?? (float) $producto->precio,
        ];
    }

    /** Un renglón de equipo (sin stock propio), útil para montos redondos. */
    protected function renglonEquipo(float $precio, string $nombre = 'Torre de cirugía'): array
    {
        return ['tipo_item' => 'equipo', 'nombre' => $nombre, 'cantidad' => 1, 'precio_unitario' => $precio];
    }

    /** Registra la venta como si la hubiera capturado $vendedor. */
    protected function ventaDe(User $vendedor, array $datos): Venta
    {
        $this->actingAs($vendedor);
        $venta = app(RegistroDeVentas::class)->crear($datos + ['modalidad' => 'contado']);
        $this->app['auth']->forgetGuards();

        return $venta->refresh();
    }

    /** Venta a plazos de $3,000 en tres pagos de $1,000 (hoy, +1 mes, +2 meses). */
    protected function ventaFinanciada(User $vendedor, ?Customer $cliente = null): Venta
    {
        return $this->ventaDe($vendedor, [
            'customer_id' => ($cliente ?? $this->cliente())->id,
            'modalidad' => 'financiamiento',
            'num_meses' => 2,
            'garantia_meses' => 12,
            'items' => [$this->renglonEquipo(3000)],
        ]);
    }
}

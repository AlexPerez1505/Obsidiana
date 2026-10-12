<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Inventario\PiezaResource;
use App\Http\Resources\Api\V1\Inventario\ProductoResource;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Services\Inventario\PinDeAprobacion;
use App\Services\Inventario\ProductosDeInventario;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Las piezas de un producto, una por una: agregar unidades, corregir la
 * serie o la foto de una, o quitar la que se dio de alta por error.
 *
 * @tags Inventario · Productos
 */
class SerialController extends Controller
{
    public function __construct(private ProductosDeInventario $productos) {}

    /**
     * Agregar piezas a un producto.
     *
     * Crea `cantidad` piezas nuevas. Sus series van en `series_texto` (una
     * por línea o separadas por coma) o en `series[]`; si solo viene una se
     * numera en secuencia, y si no viene ninguna quedan sin serie.
     */
    public function store(Request $request, Producto $producto): JsonResponse
    {
        $data = $request->validate(ProductosDeInventario::REGLAS_AGREGAR_UNIDADES + ProductoController::REGLAS_SERIES);

        $this->productos->agregarUnidades($producto, (int) $data['cantidad'], ProductoController::seriesTexto($data));

        return (new ProductoResource($producto->fresh()->load('seriales')))
            ->additional(['message' => "Se agregaron {$data['cantidad']} unidad(es) al inventario."])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Corregir una pieza.
     *
     * Cambia su `no_serie` (si no se manda se conserva; vacío lo borra) y/o
     * su `foto` (multipart por POST con `_method=PUT`). Pide `password`: el
     * PIN de aprobación o, si no tiene, la contraseña, porque altera un dato
     * que quedó como evidencia.
     */
    public function update(Request $request, Producto $producto, ProductoSerial $serial): PiezaResource
    {
        $this->exigirDelProducto($producto, $serial);

        $data = $request->validate(ProductosDeInventario::REGLAS_ACTUALIZAR_SERIAL);

        if (! PinDeAprobacion::valido($request->user(), $data['password'])) {
            throw ValidationException::withMessages(['password' => PinDeAprobacion::ERROR]);
        }

        // La web siempre manda la serie actual; por la API, no mandarla
        // significa dejarla como está.
        $noSerie = array_key_exists('no_serie', $data) ? $data['no_serie'] : $serial->no_serie;

        $this->productos->actualizarSerial($serial, $noSerie, $request->file('foto'));

        return (new PiezaResource($serial->fresh()))
            ->additional(['message' => 'Unidad actualizada correctamente.']);
    }

    /**
     * Quitar una pieza.
     *
     * Solo una que nunca se vendió (p. ej. dada de alta por error); si ya
     * se vendió responde 409. El stock se recalcula.
     */
    public function destroy(Producto $producto, ProductoSerial $serial): JsonResponse
    {
        $this->exigirDelProducto($producto, $serial);

        $this->productos->eliminarSerial($serial);

        return response()->json(['message' => 'Unidad eliminada del inventario.']);
    }

    /** La pieza tiene que ser de ese producto: si no, para la API no existe. */
    private function exigirDelProducto(Producto $producto, ProductoSerial $serial): void
    {
        if ((int) $serial->producto_id !== (int) $producto->id) {
            throw (new ModelNotFoundException)->setModel(ProductoSerial::class, [$serial->id]);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Inventario\PiezaResource;
use App\Http\Resources\Api\V1\Inventario\ProductoResource;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Services\Inventario\ProductosDeInventario;
use App\Support\EdicionPropia;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Las piezas de un producto: agregar unidades, corregir la serie o las
 * fotos de una (o las series de muchas a la vez), o quitar la que se dio de
 * alta por error.
 *
 * Corregir y quitar lo puede quien tiene inventario.editar /
 * inventario.eliminar, o quien registró la pieza durante sus primeras
 * EdicionPropia::HORAS horas (si la llave trae inventario.registrar).
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
        abort_unless(EdicionPropia::puedeAgregarPiezas($request->user(), $producto), 403, EdicionPropia::mensaje('agregar piezas a'));

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
     * Cambia su `no_serie` (si no se manda se conserva; vacío lo borra) y
     * sus fotos: `fotos[]` se suman a las que ya tiene (máximo 3 en total) y
     * `quitar_fotos[]` (rutas tal como vienen en la pieza) quita las que ya
     * no sirven. Con archivos, manda multipart por POST con `_method=PUT`.
     * Ya no pide PIN ni contraseña.
     */
    public function update(Request $request, Producto $producto, ProductoSerial $serial): PiezaResource
    {
        $this->exigirDelProducto($producto, $serial);

        abort_unless(EdicionPropia::puedeEditarPieza($request->user(), $serial), 403, EdicionPropia::mensaje());

        $data = $request->validate(ProductosDeInventario::REGLAS_ACTUALIZAR_SERIAL);

        // No mandar la serie significa dejarla como está.
        $noSerie = array_key_exists('no_serie', $data) ? $data['no_serie'] : $serial->no_serie;

        $fotos = array_values(array_filter([...($request->file('fotos') ?? []), $request->file('foto')]));

        $this->productos->actualizarSerial($serial, $noSerie, $fotos, $data['quitar_fotos'] ?? []);

        return (new PiezaResource($serial->fresh()))
            ->additional(['message' => 'Unidad actualizada correctamente.']);
    }

    /**
     * Quitar una pieza.
     *
     * Solo una que nunca se vendió (p. ej. dada de alta por error); si ya
     * se vendió responde 409. El stock se recalcula.
     */
    public function destroy(Request $request, Producto $producto, ProductoSerial $serial): JsonResponse
    {
        $this->exigirDelProducto($producto, $serial);

        abort_unless(EdicionPropia::puedeEliminarPieza($request->user(), $serial), 403, EdicionPropia::mensaje('eliminar'));

        $this->productos->eliminarSerial($serial);

        return response()->json(['message' => 'Unidad eliminada del inventario.']);
    }

    /**
     * Corregir varias series a la vez.
     *
     * `series` es un objeto `{ "id de la pieza": "serie" }` con solo las que
     * cambian (vacío o null deja la pieza sin serie). Se guardan juntas o
     * ninguna: si alguna pieza no es del producto, no la puede corregir el
     * usuario (403) o quedarían dos con la misma serie (422), no se guarda
     * nada. Responde cuántas cambiaron.
     */
    public function lote(Request $request, Producto $producto): JsonResponse
    {
        $data = $request->validate(ProductosDeInventario::REGLAS_SERIES_EN_LOTE);

        $ajenas = $producto->seriales()->whereIn('id', array_keys($data['series']))->get()
            ->reject(fn (ProductoSerial $s) => EdicionPropia::puedeEditarPieza($request->user(), $s));

        abort_if($ajenas->isNotEmpty(), 403, EdicionPropia::mensaje());

        $cambiadas = $this->productos->actualizarSeriesEnLote($producto, $data['series']);

        return response()->json([
            'message' => $cambiadas ? "Se guardaron {$cambiadas} número(s) de serie." : 'No había series nuevas que guardar.',
            'cambiadas' => $cambiadas,
        ]);
    }

    /** La pieza tiene que ser de ese producto: si no, para la API no existe. */
    private function exigirDelProducto(Producto $producto, ProductoSerial $serial): void
    {
        if ((int) $serial->producto_id !== (int) $producto->id) {
            throw (new ModelNotFoundException)->setModel(ProductoSerial::class, [$serial->id]);
        }
    }
}

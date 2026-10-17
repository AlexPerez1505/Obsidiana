<?php

namespace App\Services\Inventario;

use App\Http\Controllers\Concerns\ManejaSeriesDeProducto;
use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\User;
use App\Support\PrecioVisible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Productos del inventario y sus unidades (piezas con su propio serial).
 *
 * Es el alta directa, sin evidencia (la que pasa por Entrada/Salida vive en
 * EntradasDeInventario). La usan la pantalla web y la API, para que las
 * dos acumulen, numeren series y recalculen stock exactamente igual. Los
 * datos que no cuadran se avisan con ValidationException sobre el campo
 * del formulario; lo que choca con el estado de la pieza, con
 * OperacionRechazada.
 */
class ProductosDeInventario
{
    use ManejaSeriesDeProducto;

    public const REGLAS_GENERAR_SERIES = [
        'equipment_type_id' => ['nullable', 'exists:equipment_types,id'],
        'subtype_id' => ['nullable', 'exists:subtypes,id'],
        'brand_id' => ['nullable', 'exists:brands,id'],
        'equipment_model_id' => ['nullable', 'exists:equipment_models,id'],
        'cantidad' => ['required', 'integer', 'min:1', 'max:5000'],
    ];

    public const REGLAS_AGREGAR_UNIDADES = [
        'cantidad' => ['required', 'integer', 'min:1'],
        'series_texto' => ['nullable', 'string'],
    ];

    /** Cuántas fotos de evidencia lleva cada pieza, como en la entrada. */
    public const MAX_FOTOS_PIEZA = 3;

    /*
    | Ya no pide PIN ni contraseña: quién puede corregir lo decide
    | EdicionPropia (permiso, o el plazo de quien la registró).
    |
    | `fotos` se SUMAN a las que ya tiene la pieza (hasta 3 en total) y
    | `quitar_fotos` dice cuáles de las actuales se van. `foto` (una sola)
    | se sigue aceptando por compatibilidad y cuenta como una foto más.
    */
    public const REGLAS_ACTUALIZAR_SERIAL = [
        'no_serie' => ['nullable', 'string', 'max:255'],
        'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        'fotos' => ['nullable', 'array', 'max:3'],
        'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        'quitar_fotos' => ['nullable', 'array'],
        'quitar_fotos.*' => ['string'],
    ];

    /** Varias series a la vez: [id de la pieza => número de serie]. */
    public const REGLAS_SERIES_EN_LOTE = [
        'series' => ['required', 'array', 'min:1', 'max:1000'],
        'series.*' => ['nullable', 'string', 'max:255'],
    ];

    /** Datos generales del producto (alta y edición). */
    public static function reglas(): array
    {
        return [
            'equipment_type_id' => ['required', 'exists:equipment_types,id'],
            'subtype_id' => ['nullable', 'exists:subtypes,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'equipment_model_id' => ['nullable', 'exists:equipment_models,id'],
            'precio' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            // Opcional: la REF o número de parte del fabricante.
            'referencia' => ['nullable', 'string', 'max:120'],
            'no_serie' => ['nullable', 'string', 'max:255'],
            'imagen' => ['nullable', 'image', 'max:5120'], // max 5MB
            'es_serializado' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Búsqueda del listado (tipo, marca, modelo o serie) y, si se pide, solo
     * lo que se llevaron a congresos: piezas que siguen contando como stock
     * (allá se pueden vender), pero que no están físicamente en el anaquel.
     */
    public function filtrar(Builder $query, ?string $busqueda, ?string $ubicacion): Builder
    {
        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('tipo_equipo', 'like', "%{$busqueda}%")
                    ->orWhere('marca', 'like', "%{$busqueda}%")
                    ->orWhere('modelo', 'like', "%{$busqueda}%")
                    ->orWhere('referencia', 'like', "%{$busqueda}%")
                    ->orWhere('no_serie', 'like', "%{$busqueda}%");
            });
        }

        if ($ubicacion === 'congreso') {
            $query->whereHas('serialesDisponibles', fn ($q) => $q->whereNotNull('congress_id'));
        }

        return $query;
    }

    /** Piezas sin vender que andan en algún congreso. */
    public function piezasEnCongreso(): int
    {
        return ProductoSerial::whereNotNull('congress_id')->where('vendido', false)->count();
    }

    /**
     * Guarda un producto nuevo.
     *
     * Cada unidad que se da de alta queda como su propia fila en
     * producto_seriales, con su propio número de serie. Si el modelo ya
     * existe en el inventario, no se crea otra fila de producto: las
     * unidades nuevas se agregan a la que ya hay.
     *
     * $data es lo validado con reglas() y ya pasado por el filtro de precio.
     *
     * @return array{producto: Producto, mensaje: string}
     *
     * @throws ValidationException
     */
    public function registrar(array $data, ?string $seriesTexto, ?UploadedFile $imagen): array
    {
        $series = $this->parsearSeries($seriesTexto ?? '');
        $series = $this->autocompletarSecuencia($series, (int) $data['stock']);

        if ($series->isNotEmpty() && $series->count() !== (int) $data['stock']) {
            throw ValidationException::withMessages([
                'series_texto' => 'Capturaste '.$series->count()." número(s) de serie, pero el stock dice {$data['stock']}. Deben coincidir, o deja el campo vacío si no vas a registrar series.",
            ]);
        }

        if ($imagen) {
            $data['imagen_path'] = $imagen->store('productos', 'public');
        }

        $cantidad = (int) $data['stock'];
        unset($data['stock'], $data['no_serie']);

        try {
            try {
                return $this->crearOAcumular($data, $cantidad, $series->all());
            } catch (QueryException $e) {
                if (! $this->esErrorDeDuplicado($e)) {
                    throw $e;
                }

                // Dos altas del mismo modelo casi al mismo tiempo: la otra
                // ganó la carrera. Se reintenta una vez contra la fila que
                // ya quedó creada.
                return $this->crearOAcumular($data, $cantidad, $series->all());
            }
        } catch (QueryException $e) {
            if (! $this->esErrorDeDuplicado($e)) {
                throw $e;
            }

            // Si sigue fallando, ya no es una carrera: el serial de verdad
            // está repetido para ese producto.
            throw ValidationException::withMessages([
                'series_texto' => 'Uno de esos números de serie ya existe para este producto. Revísalos y vuelve a intentar.',
            ]);
        }
    }

    /**
     * Actualiza los datos generales. El stock y los números de serie se
     * manejan aparte, unidad por unidad.
     */
    public function actualizar(Producto $producto, array $data, ?UploadedFile $imagen): Producto
    {
        unset($data['stock'], $data['no_serie']);

        if ($imagen) {
            if ($producto->imagen_path) {
                Storage::disk('public')->delete($producto->imagen_path);
            }
            $data['imagen_path'] = $imagen->store('productos', 'public');
        }

        $producto->update($data);

        return $producto;
    }

    /**
     * Agrega unidades (con o sin serial) a un producto que ya existe.
     *
     * @throws ValidationException
     */
    public function agregarUnidades(Producto $producto, int $cantidad, ?string $seriesTexto): void
    {
        $series = $this->parsearSeries($seriesTexto ?? '');
        $series = $this->autocompletarSecuencia($series, $cantidad);

        if ($series->isNotEmpty() && $series->count() !== $cantidad) {
            throw ValidationException::withMessages([
                'series_texto' => 'Capturaste '.$series->count()." número(s) de serie, pero la cantidad dice {$cantidad}. Deben coincidir, o deja el campo vacío.",
            ]);
        }

        try {
            $producto->agregarUnidades($cantidad, $series->all());
        } catch (QueryException $e) {
            if (! $this->esErrorDeDuplicado($e)) {
                throw $e;
            }

            throw ValidationException::withMessages([
                'series_texto' => 'Uno de esos números de serie ya existe para este producto. Revísalos y vuelve a intentar.',
            ]);
        }
    }

    /**
     * Corrige el número de serie y las fotos de una pieza ya guardada.
     *
     * Las fotos nuevas se suman a las que tiene (no las reemplazan) hasta
     * MAX_FOTOS_PIEZA; para cambiar una se quita la vieja y se sube otra.
     * Quién puede hacerlo lo revisa quien llama (EdicionPropia).
     *
     * @param  array<int, UploadedFile>  $fotos
     * @param  array<int, string>  $quitar  rutas de las fotos actuales que se van
     *
     * @throws ValidationException si el serial ya existe o pasan de 3 fotos
     */
    public function actualizarSerial(ProductoSerial $serial, ?string $noSerie, array $fotos = [], array $quitar = []): void
    {
        $nuevaSerie = trim((string) ($noSerie ?? '')) ?: null;
        $disco = config('filesystems.fotos_disk', 'public');

        $actuales = array_values(array_filter($serial->evidence_paths ?: [$serial->foto_path]));

        // Solo se quita lo que de verdad es de esta pieza: una ruta ajena
        // que llegara en la petición no debe borrar archivos de otros.
        $quitar = array_values(array_intersect($actuales, $quitar));
        $quedan = array_values(array_diff($actuales, $quitar));

        if (count($quedan) + count($fotos) > self::MAX_FOTOS_PIEZA) {
            throw ValidationException::withMessages([
                'fotos' => 'Cada pieza lleva máximo '.self::MAX_FOTOS_PIEZA.' fotos. Ya tiene '.count($quedan)
                    .'; quita alguna antes de subir otra.',
            ]);
        }

        $subidas = [];

        try {
            DB::transaction(function () use ($serial, $nuevaSerie, $fotos, $quedan, $quitar, $disco, &$subidas) {
                $cambios = ['editado_por' => auth()->id()];

                if ($nuevaSerie !== $serial->no_serie) {
                    $cambios['no_serie'] = $nuevaSerie;
                }

                if ($fotos || $quitar) {
                    foreach ($fotos as $foto) {
                        $subidas[] = $foto->store('productos/seriales', $disco);
                    }

                    $todas = array_values(array_merge($quedan, $subidas));
                    $cambios['evidence_paths'] = $todas ?: null;
                    $cambios['foto_path'] = $todas[0] ?? null;
                }

                $serial->update($cambios);
                $serial->producto->recalcularStock();
            });
        } catch (QueryException $e) {
            Storage::disk($disco)->delete($subidas);

            if (! $this->esErrorDeDuplicado($e)) {
                throw $e;
            }

            throw ValidationException::withMessages(['no_serie' => 'Ese número de serie ya existe para este producto.']);
        }

        // Ya guardado el cambio, se borran del disco las que se quitaron.
        if ($quitar) {
            Storage::disk($disco)->delete($quitar);
        }
    }

    /**
     * Corrige muchas series de un mismo producto de una sola vez.
     *
     * Con 100 piezas del mismo equipo no tiene sentido abrir una por una:
     * se mandan solo las que cambiaron ([id => serie]) y se guardan juntas.
     * Antes de tocar nada se revisa que no queden dos piezas del producto
     * con la misma serie (también entre las que se están cambiando), y se
     * guardan en dos pasos para que intercambiar series entre dos piezas no
     * choque con el índice único a medio camino.
     *
     * Quién puede tocar cada pieza lo revisa quien llama (EdicionPropia).
     *
     * @param  array<int|string, string|null>  $series
     * @return int cuántas piezas cambiaron
     *
     * @throws ValidationException si alguna pieza no es del producto o hay repetidas
     */
    public function actualizarSeriesEnLote(Producto $producto, array $series): int
    {
        $nuevas = collect($series)
            ->mapWithKeys(fn ($serie, $id) => [(int) $id => trim((string) ($serie ?? '')) ?: null]);

        $piezas = $producto->seriales()->get()->keyBy('id');

        if ($nuevas->keys()->reject(fn (int $id) => $piezas->has($id))->isNotEmpty()) {
            throw ValidationException::withMessages(['series' => 'Alguna de esas piezas no es de este producto.']);
        }

        // Solo lo que de verdad cambió.
        $cambios = $nuevas->filter(fn ($serie, int $id) => $serie !== $piezas[$id]->no_serie);

        if ($cambios->isEmpty()) {
            return 0;
        }

        $finales = $piezas
            ->map(fn (ProductoSerial $p) => $cambios->has($p->id) ? $cambios[$p->id] : $p->no_serie)
            ->filter();

        $repetidas = $finales->countBy()->filter(fn (int $n) => $n > 1)->keys();

        if ($repetidas->isNotEmpty()) {
            throw ValidationException::withMessages([
                'series' => 'Quedarían piezas con la misma serie: '.$repetidas->implode(', ').'. Cada pieza debe tener la suya.',
            ]);
        }

        DB::transaction(function () use ($producto, $cambios) {
            $usuario = auth()->id();

            ProductoSerial::whereIn('id', $cambios->keys())->update(['no_serie' => null]);

            foreach ($cambios as $id => $serie) {
                ProductoSerial::whereKey($id)->update(['no_serie' => $serie, 'editado_por' => $usuario]);
            }

            $producto->recalcularStock();
        });

        return $cambios->count();
    }

    /**
     * Quita del inventario una unidad que nunca se llegó a vender (por
     * ejemplo, si se dio de alta por error).
     *
     * @throws OperacionRechazada si ya se vendió
     */
    public function eliminarSerial(ProductoSerial $serial): void
    {
        if ($serial->vendido) {
            throw new OperacionRechazada('serial', 'Esa unidad ya se vendió, no se puede eliminar del inventario.');
        }

        $producto = $serial->producto;
        $serial->delete();
        $producto->recalcularStock();
    }

    /** El producto que ya existe para ese modelo, para autocompletar el alta. */
    public function porModelo(int $modeloId): ?Producto
    {
        return Producto::where('equipment_model_id', $modeloId)
            ->latest()
            ->first();
    }

    /** El serial que seguiría para este producto, si sus series terminan en dígitos. */
    public function siguienteSerial(Producto $producto): ?string
    {
        return $this->sugerirSiguienteSerial($producto->id);
    }

    /**
     * El kardex del producto: qué entró, cuándo entró y cuánto hay hoy. El
     * stock no se guarda como un número suelto, se cuenta a partir de las
     * piezas, así que aquí se enseña de dónde sale.
     *
     * @return array{piezas: Collection<int, ProductoSerial>, movimientos: Collection<int, InventoryMovement>, conteos: array<string, int>}
     */
    public function kardex(Producto $producto): array
    {
        $producto->load(['seriales.entrada', 'seriales.procesos', 'seriales.ventaItem.venta']);

        $piezas = $producto->seriales->sortByDesc('id')->values();

        // La bitácora del producto: entradas y salidas, más reciente arriba.
        $movimientos = InventoryMovement::where('item_type', InventoryMovement::ITEM_PRODUCT)
            ->where('item_id', $producto->id)
            ->with('creator')
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->get();

        return [
            'piezas' => $piezas,
            'movimientos' => $movimientos,
            'conteos' => [
                'total' => $piezas->count(),
                'disponibles' => $piezas->filter->vendible()->count(),
                'en_proceso' => $piezas->where('vendido', false)
                    ->filter(fn ($p) => in_array($p->estado, ProductoSerial::NO_VENDIBLES, true))
                    ->count(),
                'vendidas' => $piezas->where('vendido', true)->count(),
            ],
        ];
    }

    /**
     * El formulario no dibuja el campo para quien no puede definir precios;
     * si aun así llegó uno, se descarta en vez de confiar en él.
     */
    public static function filtrarPrecio(array $data, ?User $user): array
    {
        if (! PrecioVisible::editable($user)) {
            $data['precio'] = null;
        }

        return $data;
    }

    /** Valores ya usados de tipo, subtipo, marca y modelo (para sugerir al capturar). */
    public function opciones(): array
    {
        $options = collect(['tipo_equipo', 'subtipo', 'marca', 'modelo'])
            ->mapWithKeys(function (string $column): array {
                return [
                    $column => Producto::query()
                        ->whereNotNull($column)
                        ->where($column, '!=', '')
                        ->distinct()
                        ->orderBy($column)
                        ->pluck($column)
                        ->values()
                        ->all(),
                ];
            })
            ->all();

        $subtypesByType = Producto::query()
            ->whereNotNull('tipo_equipo')
            ->where('tipo_equipo', '!=', '')
            ->whereNotNull('subtipo')
            ->where('subtipo', '!=', '')
            ->orderBy('tipo_equipo')
            ->orderBy('subtipo')
            ->get(['tipo_equipo', 'subtipo'])
            ->groupBy('tipo_equipo')
            ->map(fn ($rows) => $rows->pluck('subtipo')->unique()->values()->all())
            ->all();

        return array_merge($options, [
            'subtypes_by_type' => $subtypesByType,
        ]);
    }

    /**
     * Busca (con bloqueo) si el modelo ya tiene fila en productos; si es
     * así, le suma las unidades nuevas, y si no, crea la fila. El lock
     * evita que dos altas simultáneas del mismo modelo creen dos filas.
     *
     * @return array{producto: Producto, mensaje: string}
     */
    private function crearOAcumular(array $data, int $cantidad, array $series): array
    {
        return DB::transaction(function () use ($data, $cantidad, $series) {
            // Por la API el campo puede no venir; sin modelo no hay con qué
            // acumular y se crea fila nueva, igual que con el campo vacío.
            $existente = ($data['equipment_model_id'] ?? null)
                ? Producto::where('equipment_model_id', $data['equipment_model_id'])->lockForUpdate()->first()
                : null;

            if ($existente) {
                return $this->sumarAExistente($existente, $data, $cantidad, $series);
            }

            $producto = Producto::create($data + ['stock' => 0]);
            $producto->agregarUnidades($cantidad, $series);

            return ['producto' => $producto, 'mensaje' => 'Producto guardado correctamente.'];
        });
    }

    /**
     * Acumula unidades nuevas en el registro que ya existe de ese modelo,
     * en vez de duplicar la fila.
     *
     * @return array{producto: Producto, mensaje: string}
     */
    private function sumarAExistente(Producto $existente, array $data, int $cantidad, array $series): array
    {
        if (! empty($data['imagen_path']) && $existente->imagen_path) {
            Storage::disk('public')->delete($existente->imagen_path);
        }

        // Sin precio nuevo se conserva el que ya tenía el modelo.
        if (($data['precio'] ?? null) !== null) {
            $existente->precio = $data['precio'];
        }

        $existente->descripcion = $data['descripcion'] ?? $existente->descripcion;
        $existente->referencia = ($data['referencia'] ?? null) ?: $existente->referencia;

        if (! empty($data['imagen_path'])) {
            $existente->imagen_path = $data['imagen_path'];
        }

        // Una vez marcado como serializado, se queda así: no se le quita
        // la marca solo porque una entrada posterior no trajo el checkbox
        // marcado.
        if (! empty($data['es_serializado'])) {
            $existente->es_serializado = true;
        }

        $existente->save();
        $existente->agregarUnidades($cantidad, $series);

        return [
            'producto' => $existente,
            'mensaje' => "Se agregaron {$cantidad} unidad(es) al stock existente de ".trim($existente->marca.' '.$existente->modelo).'.',
        ];
    }
}

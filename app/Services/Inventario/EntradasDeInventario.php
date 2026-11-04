<?php

namespace App\Services\Inventario;

use App\Http\Controllers\Concerns\ManejaSeriesDeProducto;
use App\Models\InventoryMovement;
use App\Models\OrdenSalida;
use App\Models\PiezaProceso;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Services\RutaDeProcesos;
use App\Support\ChecklistRecepcion;
use App\Support\PrecioVisible;
use App\Support\GeneradorDeSeries;
use App\Support\EdicionPropia;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Entrada/Salida es la bitácora real de inventario. Aquí vive lo que hace
 * una ENTRADA: dar de alta (o acumular) el producto, crear una fila por
 * cada pieza con su propia evidencia, dejar la firma de quien recibió y,
 * si es equipo usado, la ruta de procesos de cada pieza.
 *
 * Lo usan la pantalla web y la API: las dos validan con las mismas reglas
 * y registran exactamente igual. Cuando algo no cuadra después de validar
 * (piezas que no coinciden con la cantidad, firma ilegible, video que no
 * existe, serial repetido) se lanza ValidationException sobre el campo;
 * la web la convierte en su regreso al formulario de siempre y la API en
 * un 422.
 */
class EntradasDeInventario
{
    use ManejaSeriesDeProducto;

    private const ALMACEN = 'Almacen Central';

    public const MENSAJES = [
        'unidades.required' => 'Registra cada pieza que llegó, con su propia evidencia.',
        'unidades.*.no_serie.required' => 'Captura el número de serie de cada pieza para continuar.',
        'unidades.*.no_serie.distinct' => 'Cada pieza debe tener un número de serie diferente.',
        'unidades.*.evidencias.required' => 'Cada pieza necesita al menos una foto de cómo llegó ella.',
        'unidades.*.evidencias.min' => 'Cada pieza necesita al menos una foto de cómo llegó ella.',
        'unidades.*.evidencias.max' => 'Puedes subir máximo 3 fotos por pieza.',
        'unidades.*.fotos_paths.max' => 'Puedes subir máximo 3 fotos por pieza.',
        'fecha_caducidad.after_or_equal' => 'La fecha de caducidad no puede ser antes de la de fabricación.',
        'firma.required' => 'Se necesita la firma digital de quien registró esta entrada.',
        'estado_general.required' => 'Di en qué estado general llegó el equipo usado.',
        'checklist.required' => 'Responde el checklist de recepción del equipo usado.',
    ];

    public function __construct(private VideoPorPedazos $videos, private FotoPorPedazos $fotos) {}

    /**
     * La evidencia es de cada pieza, no del lote.
     *
     * Si llegaron 3 piezas son 3 juegos de fotos, uno por pieza: cada una
     * llegó en su propio estado, y juntar todo en un solo montón hace
     * imposible saber después cuál fue la que venía golpeada. El video de
     * cada pieza es opcional.
     */
    public static function reglas(bool $usado): array
    {
        $reglas = [
            // Viene del formulario; si no viene, se asume equipo nuevo.
            'condicion' => ['nullable', 'in:nuevo,usado'],
            'equipment_type_id' => ['required', 'exists:equipment_types,id'],
            'subtype_id' => ['nullable', 'exists:subtypes,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'equipment_model_id' => ['nullable', 'exists:equipment_models,id'],
            // El precio se valida pero no se exige: puede venir de la fila
            // que ya existe, o quedar pendiente de que lo ponga un admin.
            'precio' => ['nullable', 'numeric', 'min:0'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:5000'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            // Opcional: la REF o número de parte del fabricante.
            'referencia' => ['nullable', 'string', 'max:120'],
            'movement_date' => ['required', 'date'],
            'notas' => ['nullable', 'string', 'max:1000'],
            // La foto del catálogo: subida por pedazos (imagen_path) desde
            // la pantalla, o como archivo directo desde la API. Sin límite
            // de tamaño ni de formato de imagen.
            'imagen' => ['nullable', 'file', FotoPorPedazos::reglaArchivo()],
            'imagen_path' => ['nullable', 'string'],
            'foto_compartida' => ['sometimes', 'boolean'],
            'foto_compartida_paths' => ['required_if:foto_compartida,1', 'nullable', 'array', 'max:3'],
            'foto_compartida_paths.*' => ['string'],
            // Opcionales. La caducidad solo aplica a equipo nuevo.
            'fecha_fabricacion' => ['nullable', 'date'],
            'fecha_caducidad' => ['nullable', 'date', 'after_or_equal:fecha_fabricacion'],
            'firma' => ['required', 'string'],

            // Una fila por pieza, cada una con su propia evidencia.
            'unidades' => ['required', 'array', 'min:1'],
            'unidades.*.no_serie' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            // Las fotos llegan ya subidas por pedazos (fotos_paths, la
            // pantalla) o como archivos (evidencias, la API). Que cada pieza
            // traiga al menos una se revisa en registrar(), sumando las dos.
            'unidades.*.evidencias' => ['nullable', 'array', 'max:3'],
            'unidades.*.evidencias.*' => ['file', FotoPorPedazos::reglaArchivo()],
            'unidades.*.fotos_paths' => ['nullable', 'array', 'max:3'],
            'unidades.*.fotos_paths.*' => ['string'],
            'unidades.*.video_path' => ['nullable', 'string'],
        ];

        if ($usado) {
            $reglas['estado_general'] = ['required', Rule::in(array_keys(ChecklistRecepcion::ESTADOS))];
            $reglas['checklist'] = ['required', 'array'];
            // La ruta puede venir vacía: hay equipo usado que llega bien y
            // entra directo a stock.
            $reglas['procesos'] = ['nullable', 'array'];
            $reglas['procesos.*'] = [Rule::in(array_keys(PiezaProceso::PROCESOS))];
        }

        return $reglas;
    }

    /**
     * Registra la entrada ya validada con reglas().
     *
     * Las fotos de cada pieza se toman del request (unidades.N.evidencias)
     * y la firma llega como data URL base64 del canvas.
     *
     * @return array{movimiento: InventoryMovement, mensaje: string, advertencias: list<string>}
     *
     * @throws ValidationException
     */
    public function registrar(Request $request, array $data): array
    {
        $usado = ($data['condicion'] ?? null) === 'usado';
        $disco = config('filesystems.fotos_disk', 'public');

        foreach (array_values($data['unidades'] ?? []) as $i => $unidad) {
            if (trim((string) ($unidad['no_serie'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    "unidades.$i.no_serie" => 'Captura el número de serie de la pieza #'.($i + 1).' para continuar.',
                ]);
            }
        }

        $data['condicion'] ??= 'nuevo';
        $compartida = filter_var($data['foto_compartida'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $fotosCompartidas = [];
        if ($compartida) {
            if ($usado) {
                throw ValidationException::withMessages(['foto_compartida' => 'El equipo usado necesita fotos individuales por pieza.']);
            }
            $rutasCompartidas = (array) ($data['foto_compartida_paths'] ?? []);
            if (count($rutasCompartidas) < 1 || count($rutasCompartidas) > 3) {
                throw ValidationException::withMessages(['foto_compartida_paths' => 'Sube entre 1 y 3 fotos para todas las piezas nuevas.']);
            }
            foreach ($rutasCompartidas as $ruta) {
                $resuelta = $this->fotos->resolver((string) $ruta, $disco);
                if ($resuelta === null) {
                    throw ValidationException::withMessages(['foto_compartida_paths' => 'Una foto compartida no se subió correctamente. Vuelve a subirla.']);
                }
                $fotosCompartidas[] = $resuelta;
            }
        }

        $data['checklist_recepcion'] = $usado ? ChecklistRecepcion::limpiar($request->input('checklist')) : null;

        // El equipo usado no lleva caducidad: aunque llegue, no se guarda.
        if ($usado) {
            $data['fecha_caducidad'] = null;
        }

        /*
        | El precio puede no venir de tres maneras: el modelo ya lo tenía y
        | no se volvió a preguntar, el usuario no puede verlo, o de plano no
        | se llenó. En las tres la llave debe existir como null para que el
        | resto del método no tenga que andar preguntando si está.
        */
        $data['precio'] = PrecioVisible::editable($request->user())
            ? ($data['precio'] ?? null)
            : null;

        // Consistencia primero: si algo no cuadra, todavía no se ha subido
        // ningún archivo nuevo en este request y no queda nada huérfano.
        $cantidad = (int) $data['cantidad'];

        if (count($data['unidades']) !== $cantidad) {
            throw ValidationException::withMessages([
                'unidades' => 'Registraste '.count($data['unidades'])." pieza(s), pero la cantidad dice {$cantidad}. Debe haber una por cada unidad que llegó.",
            ]);
        }

        /*
        | Números de serie: cada pieza se queda con el que se le capturó a
        | ella, en su posición; todas deben tener un número de serie.
        |
        | Antes, si solo venía una serie se armaba una secuencia y se
        | repartía desde la pieza #1: capturar solo la de la pieza #3 se la
        | ponía a la #1 y le inventaba series a las demás. Para numerar en
        | secuencia está el botón de generar series de la pantalla.
        */
        $series = collect($data['unidades'])
            ->values()
            ->map(fn (array $u) => trim((string) ($u['no_serie'] ?? '')));

        // Los videos ya se subieron por pedazos antes de este submit: solo
        // se verifica que cada ruta sea de un video real y exista.
        $videos = [];

        foreach (array_values($data['unidades']) as $i => $u) {
            $ruta = trim((string) ($u['video_path'] ?? ''));

            if ($ruta === '') {
                $videos[$i] = null;

                continue;
            }

            $videos[$i] = $this->videos->resolver($ruta, $disco);

            if ($videos[$i] === null) {
                throw ValidationException::withMessages([
                    "unidades.$i.video_path" => 'El video de la pieza #'.($i + 1).' no se subió correctamente o expiró. Vuelve a subirlo.',
                ]);
            }
        }

        /*
        | Las fotos que ya se subieron por pedazos solo se verifican: que cada
        | ruta sea de una foto real armada por FotoPorPedazos. Igual que el
        | video, no se borran si algo falla: el usuario reintenta sin volver
        | a subirlas.
        */
        $fotosPrevias = [];

        foreach (array_values($data['unidades']) as $i => $u) {
            $fotosPrevias[$i] = [];
            if ($compartida) {
                continue;
            }

            foreach ((array) ($u['fotos_paths'] ?? []) as $ruta) {
                $resuelta = $this->fotos->resolver(trim((string) $ruta), $disco);

                if ($resuelta === null) {
                    throw ValidationException::withMessages([
                        "unidades.$i.fotos_paths" => 'Una foto de la pieza #'.($i + 1).' no se subió correctamente. Vuelve a subirla.',
                    ]);
                }

                $fotosPrevias[$i][] = $resuelta;
            }

            $total = count($fotosPrevias[$i]) + count((array) ($u['evidencias'] ?? []));

            if ($total === 0) {
                throw ValidationException::withMessages([
                    "unidades.$i.evidencias" => 'Cada pieza necesita al menos una foto de cómo llegó ella.',
                ]);
            }

            if ($total > 3) {
                throw ValidationException::withMessages([
                    "unidades.$i.evidencias" => 'Puedes subir máximo 3 fotos por pieza.',
                ]);
            }
        }

        /*
        | Ya validado todo lo que no cuesta archivos, se guardan las fotos
        | de cada pieza. Se suben antes de saber si su serial choca con uno
        | existente: eso se depura después, dentro de la transacción, sin
        | perder la evidencia ya subida.
        */
        $unidades = [];
        $copiasCompartidas = [];

        foreach (array_keys($data['unidades']) as $posicion => $llave) {
            if ($compartida) {
                foreach ($fotosCompartidas as $fotoCompartida) {
                    $copia = dirname($fotoCompartida).'/'.\Illuminate\Support\Str::uuid().'.'.pathinfo($fotoCompartida, PATHINFO_EXTENSION);
                    if (! Storage::disk($disco)->copy($fotoCompartida, $copia)) {
                        $this->borrarEvidencias($copiasCompartidas, $disco);
                        throw ValidationException::withMessages(['foto_compartida_paths' => 'No se pudieron aplicar las fotos a todas las piezas. Intenta de nuevo.']);
                    }
                    $copiasCompartidas[] = $copia;
                    $fotosPrevias[$posicion][] = $copia;
                }
            }
            $unidades[] = [
                'no_serie' => $series->get($posicion),
                'evidence_paths' => array_merge($fotosPrevias[$posicion], collect($compartida ? [] : ($request->file("unidades.$llave.evidencias") ?? []))
                    ->map(fn ($archivo) => $archivo->store('productos/seriales', $disco))
                    ->all()),
                'video_path' => $videos[$posicion],
            ];
        }

        // El producto queda marcado como serializado si alguna pieza traía
        // número de serie del fabricante.
        $serializado = $series->isNotEmpty();

        // La foto del catálogo ya subida por pedazos se conserva si algo
        // falla (igual que las de las piezas); la que llegó como archivo en
        // este mismo request sí se limpia.
        $imagenPrevia = filled($data['imagen_path'] ?? null) ? $this->fotos->resolver($data['imagen_path'], $disco) : null;

        $imagen = $request->hasFile('imagen')
            ? $request->file('imagen')->store('productos', $disco)
            : null;
        $imagenSubidaAhora = $imagen !== null;
        $imagen ??= $imagenPrevia;

        // La firma se decodifica al final, ya que se sabe que todo lo demás
        // es válido: si falla, se limpia lo que ya se subió en este mismo
        // request (el video no, porque quedó de un request anterior y el
        // usuario puede reintentar sin volver a subirlo).
        $firma = $this->guardarFirma($data['firma'], $disco);

        // Toda la evidencia subida en este request, para poder limpiarla de
        // un tirón si algo falla más adelante. Los videos no van aquí:
        // quedaron de un request anterior y el usuario puede reintentar sin
        // volver a subirlos.
        $previas = collect($fotosPrevias)->flatten()->all();
        $subidoAhora = collect($unidades)->flatMap(fn (array $u) => $u['evidence_paths'])->filter()
            ->reject(fn ($ruta) => in_array($ruta, $previas, true))->values()->all();

        if ($firma === null) {
            $this->borrarEvidencias($subidoAhora, $disco);

            if ($imagen && $imagenSubidaAhora) {
                Storage::disk($disco)->delete($imagen);
            }

            throw ValidationException::withMessages([
                'firma' => 'La firma digital no es válida. Vuelve a firmar e intenta de nuevo.',
            ]);
        }

        try {
            try {
                return $this->registrarEntrada($data, $unidades, $imagen, $firma, $serializado);
            } catch (QueryException $e) {
                if (! $this->esErrorDeDuplicado($e)) {
                    throw $e;
                }

                // Otra entrada del mismo modelo ganó la carrera: se
                // reintenta una vez contra la fila que ya quedó creada.
                return $this->registrarEntrada($data, $unidades, $imagen, $firma, $serializado);
            }
        } catch (QueryException|ValidationException $e) {
            $this->borrarEvidencias(array_merge($subidoAhora, $copiasCompartidas), $disco);
            $this->borrarEvidencias([$firma], $disco);

            if ($imagen && $imagenSubidaAhora) {
                Storage::disk($disco)->delete($imagen);
            }

            if ($e instanceof ValidationException || ! $this->esErrorDeDuplicado($e)) {
                throw $e;
            }

            throw ValidationException::withMessages([
                'unidades' => 'Uno de esos números de serie ya existe para este producto. Revísalos y vuelve a intentar.',
            ]);
        }
    }

    /**
     * Lo que se puede corregir de una entrada ya registrada: sus datos, no
     * sus piezas (las series y fotos de cada pieza se corrigen en el
     * producto, y la ruta de procesos en el detalle de la entrada).
     */
    public static function reglasEdicion(InventoryMovement $movimiento): array
    {
        $reglas = [
            'movement_date' => ['required', 'date'],
            'fecha_fabricacion' => ['nullable', 'date'],
            'fecha_caducidad' => ['nullable', 'date', 'after_or_equal:fecha_fabricacion'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];

        if ($movimiento->condicion === 'usado') {
            $reglas['estado_general'] = ['required', Rule::in(array_keys(ChecklistRecepcion::ESTADOS))];
            $reglas['checklist'] = ['nullable', 'array'];
        }

        return $reglas;
    }

    /**
     * Guarda la corrección. El checklist conserva los puntos que se
     * preguntaron al recibir (con su texto y su proceso): aquí solo cambian
     * las respuestas y las notas.
     */
    public function actualizar(InventoryMovement $movimiento, array $data): void
    {
        $usado = $movimiento->condicion === 'usado';
        $cambios = [
            'movement_date' => $data['movement_date'],
            'fecha_fabricacion' => $data['fecha_fabricacion'] ?? null,
            // El equipo usado no lleva caducidad.
            'fecha_caducidad' => $usado ? null : ($data['fecha_caducidad'] ?? null),
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notas'] ?? null,
        ];

        if ($usado) {
            $cambios['estado_general'] = $data['estado_general'];

            $recibido = $data['checklist'] ?? [];
            $checklist = [];

            foreach ($movimiento->checklist_recepcion ?? [] as $llave => $punto) {
                $nuevo = $recibido[$llave] ?? [];
                $r = $nuevo['r'] ?? null;
                $punto['r'] = in_array($r, ChecklistRecepcion::RESPUESTAS, true) ? $r : $punto['r'];

                $nota = trim((string) ($nuevo['nota'] ?? ''));
                if ($nota !== '') {
                    $punto['nota'] = mb_substr($nota, 0, 300);
                } else {
                    unset($punto['nota']);
                }

                $checklist[$llave] = $punto;
            }

            $cambios['checklist_recepcion'] = $checklist;
        }

        $movimiento->update($cambios);
    }

    public function generarSeriesFaltantes(InventoryMovement $movimiento, User $usuario): int
    {
        abort_unless(EdicionPropia::puedeEditarMovimiento($usuario, $movimiento), 403);

        return DB::transaction(function () use ($movimiento, $usuario) {
            $producto = Producto::whereKey($movimiento->item_id)->lockForUpdate()->firstOrFail();
            abort_unless($movimiento->item_type === InventoryMovement::ITEM_PRODUCT
                && EdicionPropia::puedeEditarProducto($usuario, $producto), 403);

            $piezas = $movimiento->seriales()->where('vendido', false)
                ->where(fn ($q) => $q->whereNull('no_serie')->orWhereRaw("TRIM(no_serie) = ''"))
                ->orderBy('id')->lockForUpdate()->get();
            if ($piezas->isEmpty()) {
                return 0;
            }
            abort_unless($piezas->every(fn ($pieza) => EdicionPropia::puedeEditarPieza($usuario, $pieza)), 403);

            $prefijo = GeneradorDeSeries::prefijo($producto->equipment_type_id, $producto->subtype_id,
                $producto->brand_id, $producto->equipment_model_id);
            $series = GeneradorDeSeries::generar($prefijo, $piezas->count());
            foreach ($piezas as $i => $pieza) {
                $pieza->update(['no_serie' => $series[$i]]);
            }
            $producto->update(['es_serializado' => true]);

            return $piezas->count();
        });
    }

    /**
     * Por qué no se puede borrar este movimiento, o null si sí se puede.
     *
     * Si alguna de sus unidades ya se vendió, borrarlo dejaría esa venta
     * sin origen.
     */
    public function motivoParaNoEliminar(InventoryMovement $movimiento): ?string
    {
        return $movimiento->seriales()->where('vendido', true)->exists()
            ? 'No se puede eliminar: alguna unidad de esta entrada ya se vendió.'
            : null;
    }

    /**
     * Borra el movimiento, sus unidades y toda su evidencia, y recalcula
     * el stock de los productos que tocaba.
     *
     * @throws OperacionRechazada si alguna unidad ya se vendió
     */
    public function eliminar(InventoryMovement $movimiento): void
    {
        if ($motivo = $this->motivoParaNoEliminar($movimiento)) {
            throw new OperacionRechazada('movimiento', $motivo);
        }

        DB::transaction(function () use ($movimiento) {
            $unidades = $movimiento->seriales()->get();
            $productoIds = $unidades->pluck('producto_id')->unique();

            // La evidencia de cada unidad (foto individual, o hasta 3 fotos
            // + video en registros con evidencia por unidad) también es
            // evidencia de esta entrada: se borra junto con la del lote.
            $this->borrarEvidencias($unidades->flatMap(fn ($u) => $u->evidence_paths ?: array_filter([$u->foto_path]))->filter()->all());
            $this->borrarEvidencias($unidades->pluck('video_path')->filter()->all());

            $movimiento->seriales()->delete();

            Producto::whereIn('id', $productoIds)->get()->each->recalcularStock();

            $this->borrarEvidencias($movimiento->evidence_paths ?? []);
            $this->borrarEvidencias(array_filter([$movimiento->signature_path, $movimiento->video_path]));
            $movimiento->delete();
        });
    }

    /**
     * Filtros del listado: tipo de movimiento y rango de fechas.
     */
    public function filtrar(Builder $query, ?string $tipo, ?string $desde, ?string $hasta): Builder
    {
        return $query
            ->when($tipo, fn ($q) => $q->where('movement_type', $tipo))
            ->when($desde, fn ($q) => $q->whereDate('movement_date', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('movement_date', '<=', $hasta));
    }

    /**
     * Las métricas son de todo el inventario, no de la página que se está
     * viendo: si se calcularan sobre el paginador dirían otra cosa según en
     * qué página estés.
     *
     * @return array{movimientos: int, entradas_mes: int, piezas: int, en_proceso: int}
     */
    public function resumen(): array
    {
        return [
            'movimientos' => InventoryMovement::count(),
            'entradas_mes' => InventoryMovement::where('movement_type', InventoryMovement::TYPE_ENTRY)
                ->whereDate('movement_date', '>=', now()->startOfMonth())
                ->count(),
            'piezas' => ProductoSerial::where('vendido', false)->count(),
            'en_proceso' => ProductoSerial::where('vendido', false)
                ->whereIn('estado', ProductoSerial::NO_VENDIBLES)
                ->count(),
        ];
    }

    /**
     * Una salida viene de una venta, y su entrega la controla la orden de
     * salida: así se llega a esa hoja (checklist y firmas) sin tener que
     * buscarla por folio.
     */
    public function ordenDeSalida(InventoryMovement $movimiento): ?OrdenSalida
    {
        return $movimiento->movement_type === InventoryMovement::TYPE_EXIT && $movimiento->reference
            ? OrdenSalida::whereHas('venta', fn ($q) => $q->where('folio', $movimiento->reference))
                ->with('venta')
                ->first()
            : null;
    }

    /**
     * Decodifica la firma capturada en el canvas (data URL base64) y la
     * guarda como PNG. Regresa null si el valor no es una imagen válida.
     */
    private function guardarFirma(string $firmaDataUrl, string $disco): ?string
    {
        if (! preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $firmaDataUrl, $match)) {
            return null;
        }

        $contenido = base64_decode($match[2], true);

        if ($contenido === false || $contenido === '') {
            return null;
        }

        $path = 'inventario/firmas/'.uniqid('firma_').'.png';
        Storage::disk($disco)->put($path, $contenido);

        return $path;
    }

    /**
     * Busca (con bloqueo) si el modelo ya tiene fila en productos, crea el
     * movimiento de entrada, y le agrega las unidades nuevas ya ligadas a
     * ese movimiento.
     *
     * @return array{movimiento: InventoryMovement, mensaje: string, advertencias: list<string>}
     */
    private function registrarEntrada(array $data, array $unidades, ?string $imagen, string $firma, bool $serializado): array
    {
        $disco = config('filesystems.fotos_disk', 'public');

        return DB::transaction(function () use ($data, $unidades, $imagen, $firma, $serializado, $disco) {
            $cantidad = (int) $data['cantidad'];

            $productoData = [
                'equipment_type_id' => $data['equipment_type_id'],
                'subtype_id' => $data['subtype_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'equipment_model_id' => $data['equipment_model_id'] ?? null,
                'precio' => $data['precio'],
                'descripcion' => $data['descripcion'] ?? null,
                'referencia' => trim((string) ($data['referencia'] ?? '')) ?: null,
            ];

            /*
            | ¿Es el mismo producto que ya está dado de alta?
            |
            | Se compara la combinación completa del catálogo, no solo el
            | modelo. Antes solo miraba equipment_model_id, así que un foco
            | (que no tiene modelo) creaba una fila nueva en cada entrada y
            | el stock quedaba repartido entre filas duplicadas.
            |
            | Con los cuatro campos, dos entradas del mismo equipo caen en
            | la misma fila y dos modelos distintos siguen separados.
            */
            $existente = Producto::query()
                ->where('equipment_type_id', $productoData['equipment_type_id'])
                ->where(fn ($q) => $this->igualA($q, 'subtype_id', $productoData['subtype_id']))
                ->where(fn ($q) => $this->igualA($q, 'brand_id', $productoData['brand_id']))
                ->where(fn ($q) => $this->igualA($q, 'equipment_model_id', $productoData['equipment_model_id']))
                ->lockForUpdate()
                ->first();

            $series = collect($unidades)->pluck('no_serie');
            if ($series->map(fn ($serie) => mb_strtolower($serie))->duplicates()->isNotEmpty()
                || ($existente && $existente->seriales()->whereIn('no_serie', $series->all())->exists())) {
                throw ValidationException::withMessages([
                    'unidades' => 'Uno de esos números de serie ya existe para este producto o está repetido en la entrada. Corrígelo para continuar.',
                ]);
            }

            $stockAntes = $existente->stock ?? 0;

            if ($existente) {
                // El precio del modelo solo se toca si vino uno nuevo: la
                // segunda entrada del mismo equipo no vuelve a preguntarlo,
                // y quien no puede verlo tampoco puede borrarlo sin querer.
                if ($data['precio'] !== null) {
                    $existente->precio = $data['precio'];
                }

                $existente->descripcion = $productoData['descripcion'] ?? $existente->descripcion;
                $existente->referencia = $productoData['referencia'] ?? $existente->referencia;

                // Una vez marcado como serializado, se queda así.
                if ($serializado) {
                    $existente->es_serializado = true;
                }

                // La foto de catálogo es del modelo, no de la entrada: solo
                // se reemplaza si se subió una nueva, y se borra la vieja.
                if ($imagen) {
                    if ($existente->imagen_path) {
                        Storage::disk($disco)->delete($existente->imagen_path);
                    }
                    $existente->imagen_path = $imagen;
                }

                $existente->save();
                $producto = $existente;
            } else {
                $producto = Producto::create($productoData + [
                    'stock' => 0,
                    'imagen_path' => $imagen,
                    'es_serializado' => $serializado,
                ]);
            }

            // Ninguna pieza ni su evidencia se guarda sin su número de
            // serie: los repetidos se rechazan antes de modificar el producto.
            // Se conserva la lista de advertencias en la respuesta para
            // mantener el mismo formato que esperan la pantalla y la API.
            $advertencias = [];

            $movimiento = InventoryMovement::create([
                'folio' => InventoryMovement::siguienteFolio(InventoryMovement::TYPE_ENTRY),
                'movement_type' => InventoryMovement::TYPE_ENTRY,
                'item_type' => InventoryMovement::ITEM_PRODUCT,
                'item_id' => $producto->id,
                'item_code' => (string) $producto->id,
                'item_name' => trim($producto->marca.' '.$producto->modelo) ?: $producto->tipo_equipo,
                'warehouse' => self::ALMACEN,
                'quantity' => $cantidad,
                'unit' => 'Pza',
                'stock_before' => $stockAntes,
                'stock_after' => $stockAntes + $cantidad,
                'movement_date' => $data['movement_date'],
                'fecha_fabricacion' => $data['fecha_fabricacion'] ?? null,
                'fecha_caducidad' => $data['fecha_caducidad'] ?? null,
                'notes' => $data['notas'] ?? null,
                'condicion' => $data['condicion'],
                'checklist_recepcion' => $data['checklist_recepcion'],
                'estado_general' => $data['estado_general'] ?? null,
                // La evidencia vive en cada pieza (producto_seriales), no
                // aquí: el movimiento solo guarda la firma de quien recibió.
                'evidence_paths' => null,
                'signature_path' => $firma,
                'video_path' => null,
                'created_by' => auth()->id(),
            ]);

            /*
            | El estado inicial lo manda la ruta, no la condición: hay
            | equipo usado que llega bien y entra directo a stock, y hay
            | equipo que solo necesita mantenimiento sin pasar por
            | hojalatería. Si no lleva procesos, queda disponible.
            */
            $ruta = collect($data['procesos'] ?? [])->unique()
                ->sortBy(fn ($p) => PiezaProceso::ordenDe($p))
                ->values();

            $estadoInicial = $ruta->first() ?? 'disponible';

            $producto->agregarUnidades($cantidad, $unidades, $movimiento->id, $data['condicion'], $estadoInicial);

            // La ruta se copia a cada pieza que entró, con el motivo que la
            // justifica sacado del checklist.
            if ($ruta->isNotEmpty()) {
                $motivos = ChecklistRecepcion::procesosSugeridos($data['checklist_recepcion'] ?? []);
                $servicio = app(RutaDeProcesos::class);

                foreach ($producto->seriales()->where('inventory_movement_id', $movimiento->id)->get() as $pieza) {
                    $servicio->definir($pieza, $ruta->all(), $motivos);
                }
            }

            if ($data['condicion'] === 'usado') {
                app(ChecklistConIa::class)->recordarAdicionales($data['equipment_model_id'] ?? null, $data['checklist_recepcion'] ?? []);
            }

            return [
                'movimiento' => $movimiento,
                'mensaje' => "Entrada {$movimiento->folio} registrada correctamente.",
                'advertencias' => $advertencias,
            ];
        });
    }

    /**
     * Compara una columna del catálogo respetando los nulos.
     *
     * En SQL `columna = NULL` nunca es cierto, así que un producto sin
     * subtipo jamás se habría reconocido a sí mismo.
     */
    private function igualA($query, string $columna, $valor)
    {
        return $valor === null || $valor === ''
            ? $query->whereNull($columna)
            : $query->where($columna, $valor);
    }

    private function borrarEvidencias(array $paths, ?string $disco = null): void
    {
        $disco ??= config('filesystems.fotos_disk', 'public');

        foreach ($paths as $path) {
            if ($path) {
                Storage::disk($disco)->delete($path);
            }
        }
    }
}

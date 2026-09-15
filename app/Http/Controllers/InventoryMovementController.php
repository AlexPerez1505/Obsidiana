<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ConstruyeCatalogoEquipo;
use App\Http\Controllers\Concerns\ManejaSeriesDeProducto;
use App\Models\InventoryMovement;
use App\Models\PiezaProceso;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Services\RutaDeProcesos;
use App\Support\ChecklistRecepcion;
use App\Support\PrecioVisible;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Entrada/Salida es la bitácora real de inventario:
 *
 * - Una ENTRADA es cómo se da de alta stock nuevo: pide evidencia (fotos
 *   del lote/envío) y, al guardarse, crea o acumula las unidades del
 *   producto en producto_seriales (igual que hacía ProductoController, pero
 *   ahora queda documentado con quién, cuándo y con qué evidencia llegó).
 * - Una SALIDA se genera sola cuando se registra una venta (ver
 *   VentaController); aquí solo se listan y se consultan.
 */
class InventoryMovementController extends Controller
{
    use ManejaSeriesDeProducto, ConstruyeCatalogoEquipo;

    private const ALMACEN = 'Almacen Central';

    private const VIDEO_EXTENSIONES = ['mp4', 'mov', 'm4v', 'webm'];
    private const VIDEO_CHUNK_MAX_KB = 5120; // 5MB por pedazo
    private const VIDEO_MAX_BYTES = 150 * 1024 * 1024; // 150MB ya ensamblado

    public function index(Request $request): View
    {
        $query = InventoryMovement::query()->with('creator')->latest('movement_date')->latest('id');

        if ($tipo = $request->get('tipo')) {
            $query->where('movement_type', $tipo);
        }

        if ($desde = $request->get('desde')) {
            $query->whereDate('movement_date', '>=', $desde);
        }

        if ($hasta = $request->get('hasta')) {
            $query->whereDate('movement_date', '<=', $hasta);
        }

        $movements = $query->paginate(20)->withQueryString();

        /*
        | Las métricas son de todo el inventario, no de la página que se
        | está viendo: si se calcularan sobre el paginador dirían otra cosa
        | según en qué página estés.
        */
        return view('structure.gestion_Inventario.entrada_salida.index', [
            'movements' => $movements,
            'filters' => $request->only('tipo', 'desde', 'hasta'),
            'resumen' => [
                'movimientos' => InventoryMovement::count(),
                'entradas_mes' => InventoryMovement::where('movement_type', InventoryMovement::TYPE_ENTRY)
                    ->whereDate('movement_date', '>=', now()->startOfMonth())
                    ->count(),
                'piezas' => ProductoSerial::where('vendido', false)->count(),
                'en_proceso' => ProductoSerial::where('vendido', false)
                    ->whereIn('estado', ProductoSerial::NO_VENDIBLES)
                    ->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        /*
        | Los errores de las piezas llegan con su índice
        | (unidades.0.evidencias, unidades.2.video_path...). Los bloques de
        | cada pieza los dibuja el script en el navegador, así que no pueden
        | traer su propio error: se juntan aquí para mostrarlos como lista.
        */
        $erroresPiezas = collect(optional($request->session()->get('errors'))->getBag('default')?->messages() ?? [])
            ->filter(fn ($mensajes, $llave) => str_starts_with((string) $llave, 'unidades'))
            ->flatten()
            ->unique()
            ->values();

        return view('structure.gestion_Inventario.entrada_salida.create', [
            'catalogo' => $this->catalogoEquipo(),
            'checklist' => ChecklistRecepcion::grupos(),
            'estadosGenerales' => ChecklistRecepcion::ESTADOS,
            'erroresPiezas' => $erroresPiezas,
        ]);
    }

    /**
     * Recibe un pedazo (chunk) del video de verificación y lo va guardando.
     * El video se sube en pedazos chicos para no mandar un archivo pesado
     * de golpe (evita timeouts y fallos por conexiones lentas). Cuando llega
     * el último pedazo, se ensamblan todos en un solo archivo y se regresa
     * su ruta; el formulario principal solo manda esa ruta como texto, no
     * el video completo otra vez.
     */
    public function subirVideoChunk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chunk' => ['required', 'file', 'max:'.self::VIDEO_CHUNK_MAX_KB],
            'upload_id' => ['required', 'string', 'regex:/^[a-zA-Z0-9\-]{8,64}$/'],
            'index' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:1'],
            'extension' => ['required', 'string', Rule::in(self::VIDEO_EXTENSIONES)],
        ]);

        $disco = config('filesystems.fotos_disk', 'public');
        $uploadId = $data['upload_id'];
        $index = (int) $data['index'];
        $total = (int) $data['total'];
        $carpetaTemporal = "inventario/tmp_videos/{$uploadId}";

        Storage::disk($disco)->put("{$carpetaTemporal}/{$index}.part", $request->file('chunk')->get());

        if ($index < $total - 1) {
            return response()->json(['status' => 'chunk_recibido']);
        }

        // Llegó el último pedazo: revisa que no falte ninguno y los ensambla
        // en orden, en streaming (sin cargar el video completo a memoria).
        for ($i = 0; $i < $total; $i++) {
            if (! Storage::disk($disco)->exists("{$carpetaTemporal}/{$i}.part")) {
                return response()->json(['message' => 'Faltan pedazos del video, vuelve a subirlo.'], 422);
            }
        }

        $pathFinal = 'inventario/entradas/'.uniqid('video_').'.'.$data['extension'];
        $rutaAbsoluta = Storage::disk($disco)->path($pathFinal);

        if (! is_dir(dirname($rutaAbsoluta))) {
            mkdir(dirname($rutaAbsoluta), 0755, true);
        }

        $destino = fopen($rutaAbsoluta, 'wb');
        for ($i = 0; $i < $total; $i++) {
            $origen = fopen(Storage::disk($disco)->path("{$carpetaTemporal}/{$i}.part"), 'rb');
            stream_copy_to_stream($origen, $destino);
            fclose($origen);
        }
        fclose($destino);

        Storage::disk($disco)->deleteDirectory($carpetaTemporal);

        if (filesize($rutaAbsoluta) > self::VIDEO_MAX_BYTES) {
            Storage::disk($disco)->delete($pathFinal);

            return response()->json(['message' => 'El video no debe pesar más de 150MB en total.'], 422);
        }

        return response()->json(['status' => 'listo', 'video_path' => $pathFinal]);
    }

    /**
     * Registra una entrada de inventario: da de alta (o acumula) el
     * producto, crea sus unidades/seriales, y deja evidencia fotográfica.
     *
     * Si el producto es_serializado, cada unidad trae su propio renglón
     * (serie + foto individual) en vez del textarea + evidencia de lote de
     * siempre; la evidencia general se vuelve opcional en ese caso, porque
     * ya quedó una foto por cada unidad.
     */
    public function store(Request $request): RedirectResponse
    {
        /*
        | La evidencia es de cada pieza, no del lote.
        |
        | Si llegaron 3 piezas son 3 juegos de fotos, uno por pieza: cada
        | una llegó en su propio estado, y juntar todo en un solo montón
        | hace imposible saber después cuál fue la que venía golpeada. El
        | video de cada pieza es opcional.
        |
        | Cada pieza queda como su propia fila con su etiqueta interna, que
        | es lo que después lleva el QR.
        */
        $usado = $request->input('condicion') === 'usado';
        $disco = config('filesystems.fotos_disk', 'public');

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
            'movement_date' => ['required', 'date'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'imagen' => ['nullable', 'image', 'max:5120'],
            'firma' => ['required', 'string'],

            // Una fila por pieza, cada una con su propia evidencia.
            'unidades' => ['required', 'array', 'min:1'],
            'unidades.*.no_serie' => ['nullable', 'string', 'max:255'],
            'unidades.*.evidencias' => ['required', 'array', 'min:1', 'max:3'],
            'unidades.*.evidencias.*' => ['image', 'max:5120'],
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

        $data = $request->validate($reglas, [
            'unidades.required' => 'Registra cada pieza que llegó, con su propia evidencia.',
            'unidades.*.evidencias.required' => 'Cada pieza necesita al menos una foto de cómo llegó ella.',
            'unidades.*.evidencias.min' => 'Cada pieza necesita al menos una foto de cómo llegó ella.',
            'unidades.*.evidencias.max' => 'Puedes subir máximo 3 fotos por pieza.',
            'firma.required' => 'Se necesita la firma digital de quien registró esta entrada.',
            'estado_general.required' => 'Di en qué estado general llegó el equipo usado.',
            'checklist.required' => 'Responde el checklist de recepción del equipo usado.',
        ]);

        $data['condicion'] ??= 'nuevo';

        $data['checklist_recepcion'] = $usado ? ChecklistRecepcion::limpiar($request->input('checklist')) : null;

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
            return back()->withInput()->withErrors([
                'unidades' => 'Registraste '.count($data['unidades'])." pieza(s), pero la cantidad dice {$cantidad}. Debe haber una por cada unidad que llegó.",
            ]);
        }

        /*
        | Números de serie: si solo se capturó el de la primera pieza, el
        | resto se completa como secuencia (23A00001 → 23A00002...). Si se
        | capturaron sueltos (unas sí y otras no), se respeta cada posición
        | tal como se capturó.
        */
        $capturadas = collect($data['unidades'])
            ->values()
            ->map(fn (array $u) => trim((string) ($u['no_serie'] ?? '')) ?: null);

        $secuencia = $this->autocompletarSecuencia($capturadas->filter()->values(), $cantidad);
        $series = $secuencia->count() === $cantidad ? $secuencia->values() : $capturadas;

        // Los videos ya se subieron por chunks antes de este submit: solo
        // se verifica que cada ruta sea de un video real y exista.
        $videos = [];

        foreach (array_values($data['unidades']) as $i => $u) {
            $ruta = trim((string) ($u['video_path'] ?? ''));

            if ($ruta === '') {
                $videos[$i] = null;
                continue;
            }

            $videos[$i] = $this->resolverVideoPreSubido($ruta, $disco);

            if ($videos[$i] === null) {
                return back()->withInput()->withErrors([
                    "unidades.$i.video_path" => 'El video de la pieza #'.($i + 1).' no se subió correctamente o expiró. Vuelve a subirlo.',
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

        foreach (array_keys($data['unidades']) as $posicion => $llave) {
            $unidades[] = [
                'no_serie' => $series->get($posicion),
                'evidence_paths' => collect($request->file("unidades.$llave.evidencias") ?? [])
                    ->map(fn ($archivo) => $archivo->store('productos/seriales', $disco))
                    ->all(),
                'video_path' => $videos[$posicion],
            ];
        }

        // El producto queda marcado como serializado si alguna pieza traía
        // número de serie del fabricante.
        $serializado = $series->filter()->isNotEmpty();

        $imagen = $request->hasFile('imagen')
            ? $request->file('imagen')->store('productos', $disco)
            : null;

        // La firma se decodifica al final, ya que se sabe que todo lo demás
        // es válido: si falla, se limpia lo que ya se subió en este mismo
        // request (el video no, porque quedó de un request anterior y el
        // usuario puede reintentar sin volver a subirlo).
        $firma = $this->guardarFirma($data['firma'], $disco);

        // Toda la evidencia subida en este request, para poder limpiarla de
        // un tirón si algo falla más adelante. Los videos no van aquí:
        // quedaron de un request anterior y el usuario puede reintentar sin
        // volver a subirlos.
        $subidoAhora = collect($unidades)->flatMap(fn (array $u) => $u['evidence_paths'])->filter()->all();

        if ($firma === null) {
            $this->borrarEvidencias($subidoAhora, $disco);

            if ($imagen) {
                Storage::disk($disco)->delete($imagen);
            }

            return back()->withInput()->withErrors([
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
        } catch (QueryException $e) {
            $this->borrarEvidencias($subidoAhora, $disco);
            $this->borrarEvidencias([$firma], $disco);

            if ($imagen) {
                Storage::disk($disco)->delete($imagen);
            }

            if (! $this->esErrorDeDuplicado($e)) {
                throw $e;
            }

            return back()->withInput()->withErrors([
                'unidades' => 'Uno de esos números de serie ya existe para este producto. Revísalos y vuelve a intentar.',
            ]);
        }
    }

    /**
     * Confirma que la ruta del video que mandó el formulario sea
     * efectivamente un video ya ensamblado por subirVideoChunk() (evita
     * que manden cualquier ruta arbitraria del disco).
     */
    private function resolverVideoPreSubido(string $path, string $disco): ?string
    {
        $extensiones = implode('|', self::VIDEO_EXTENSIONES);

        if (! preg_match('#^inventario/entradas/video_[a-zA-Z0-9.]+\.('.$extensiones.')$#', $path)) {
            return null;
        }

        return Storage::disk($disco)->exists($path) ? $path : null;
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
     */
    private function registrarEntrada(array $data, array $unidades, ?string $imagen, string $firma, bool $serializado): RedirectResponse
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

            $stockAntes = $existente->stock ?? 0;

            if ($existente) {
                // El precio del modelo solo se toca si vino uno nuevo: la
                // segunda entrada del mismo equipo no vuelve a preguntarlo,
                // y quien no puede verlo tampoco puede borrarlo sin querer.
                if ($data['precio'] !== null) {
                    $existente->precio = $data['precio'];
                }

                $existente->descripcion = $productoData['descripcion'] ?? $existente->descripcion;

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

            $advertencias = [];

            if ($serializado) {
                // Ninguna pieza ni su evidencia se descarta por un serial
                // repetido: solo se limpia el serial de esa pieza (queda
                // "sin serie capturada") y se avisa, para no perder la
                // captura de las demás.
                $series = collect($unidades)->map(fn ($u) => $u['no_serie']);
                $depurado = $this->depurarSeriesDuplicadas($producto->id, $series);

                $unidades = collect($unidades)
                    ->values()
                    ->map(fn (array $u, int $i) => ['no_serie' => $depurado['series'][$i]] + $u)
                    ->all();

                if ($depurado['rechazadas']->isNotEmpty()) {
                    $advertencias[] = 'Estos números de serie ya existían para este producto y se guardaron sin serie (la evidencia y la unidad sí se conservaron): '.$depurado['rechazadas']->implode(', ').'.';
                }
            }

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

            $mensaje = "Entrada {$movimiento->folio} registrada correctamente.";

            $redirect = redirect()->route('inventory.movimientos.index')->with('status', $mensaje);

            return $advertencias ? $redirect->with('warning', implode(' ', $advertencias)) : $redirect;
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

    /**
     * Hoja de etiquetas con el QR de cada pieza de esta entrada.
     *
     * Se imprime, se corta y se pega una en cada equipo. El QR lleva a la
     * ficha pública de esa pieza, no a nada interno.
     */
    public function etiquetas(InventoryMovement $movimiento): View
    {
        $piezas = $movimiento->seriales()->with('producto')->orderBy('id')->get();

        return view('structure.gestion_Inventario.entrada_salida.etiquetas', [
            'movimiento' => $movimiento,
            'piezas' => $piezas,
        ]);
    }

    public function show(InventoryMovement $movimiento): View
    {
        $movimiento->load(['creator', 'seriales']);

        /*
        | Una salida viene de una venta, y su entrega la controla la orden de
        | salida: desde aquí se llega a esa hoja (checklist y firmas) sin
        | tener que buscarla por folio.
        */
        $orden = $movimiento->movement_type === InventoryMovement::TYPE_EXIT && $movimiento->reference
            ? \App\Models\OrdenSalida::whereHas('venta', fn ($q) => $q->where('folio', $movimiento->reference))
                ->with('venta')
                ->first()
            : null;

        return view('structure.gestion_Inventario.entrada_salida.show', [
            'movimiento' => $movimiento,
            'producto' => $movimiento->producto(),
            'ordenSalida' => $orden,
        ]);
    }

    /**
     * Elimina un movimiento. Solo tiene sentido para entradas que todavía
     * no se hayan vendido (si alguna de sus unidades ya se vendió, borrar
     * el movimiento dejaría esa venta sin origen).
     */
    public function destroy(Request $request, InventoryMovement $movimiento): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $user = auth()->user();

        // Si el usuario ya configuró un PIN de aprobación, se usa ese; si
        // no, se acepta su contraseña normal para no dejarlo sin forma de
        // confirmar la eliminación.
        $valido = $user->approval_pin_hash
            ? $user->checkApprovalPin($request->password)
            : \Illuminate\Support\Facades\Hash::check($request->password, $user->password);

        if (! $valido) {
            return back()->withErrors(['password' => 'PIN o contraseña incorrecta.']);
        }

        if ($movimiento->seriales()->where('vendido', true)->exists()) {
            return back()->withErrors(['password' => 'No se puede eliminar: alguna unidad de esta entrada ya se vendió.']);
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

        return redirect()->route('inventory.movimientos.index')->with('status', "Movimiento {$movimiento->folio} eliminado.");
    }
}

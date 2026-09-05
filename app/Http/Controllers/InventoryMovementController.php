<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ConstruyeCatalogoEquipo;
use App\Http\Controllers\Concerns\ManejaSeriesDeProducto;
use App\Models\InventoryMovement;
use App\Models\Producto;
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

        return view('structure.gestion_Inventario.entrada_salida.index', [
            'movements' => $movements,
            'filters' => $request->only('tipo', 'desde', 'hasta'),
        ]);
    }

    public function create(): View
    {
        return view('structure.gestion_Inventario.entrada_salida.create', [
            'catalogo' => $this->catalogoEquipo(),
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
     * producto y crea sus unidades/seriales.
     *
     * La evidencia siempre es por unidad, sin importar si el producto
     * maneja serie o no: cada unidad que llega (según "cantidad") tiene
     * su propio renglón con hasta 3 fotos y 1 video opcional, para poder
     * revisar cómo llegó cada pieza por separado. El número de serie es
     * opcional por renglón; si solo se captura el de la primera unidad, el
     * resto de la secuencia se genera sola.
     */
    public function store(Request $request): RedirectResponse
    {
        $disco = config('filesystems.fotos_disk', 'public');

        $data = $request->validate([
            'equipment_type_id' => ['required', 'exists:equipment_types,id'],
            'subtype_id' => ['nullable', 'exists:subtypes,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'equipment_model_id' => ['nullable', 'exists:equipment_models,id'],
            'precio' => ['required', 'numeric', 'min:0'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'proveedor' => ['nullable', 'string', 'max:255'],
            'movement_date' => ['required', 'date'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'imagen' => ['nullable', 'image', 'max:5120'],
            'firma' => ['required', 'string'],
            'unidades' => ['required', 'array', 'min:1'],
            'unidades.*.no_serie' => ['nullable', 'string', 'max:255'],
            'unidades.*.evidencias' => ['required', 'array', 'min:1', 'max:3'],
            'unidades.*.evidencias.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'unidades.*.video_path' => ['nullable', 'string'],
        ], [
            'unidades.required' => 'Captura al menos una unidad con su evidencia.',
            'unidades.*.evidencias.required' => 'Cada unidad necesita al menos 1 foto de evidencia de cómo llegó.',
            'unidades.*.evidencias.max' => 'Cada unidad admite máximo 3 fotos de evidencia.',
            'firma.required' => 'Se necesita la firma digital de quien registró esta entrada.',
        ]);

        // Validación de consistencia primero: si algo no cuadra, todavía no
        // se ha subido ningún archivo nuevo en este request y no queda
        // nada huérfano.
        if (count($data['unidades']) !== (int) $data['cantidad']) {
            return back()->withInput()->withErrors([
                'unidades' => 'Capturaste '.count($data['unidades'])." renglón(es), pero la cantidad dice {$data['cantidad']}. Debe haber un renglón por cada unidad.",
            ]);
        }

        // Si solo se capturó el serial de una unidad y hay más de una, el
        // resto de la secuencia se genera sola (mismo comportamiento de
        // siempre, ahora aplicado sobre los renglones por unidad).
        $unidadesInput = $this->autocompletarSecuenciaDeUnidades($data['unidades']);

        // Cada unidad puede traer su propio video, ya subido por chunks
        // antes de este submit: solo se verifica que la ruta sea la de un
        // video real ya ensamblado y que exista. Es opcional.
        foreach ($unidadesInput as $i => $unidad) {
            if (empty($unidad['video_path'])) {
                continue;
            }

            $video = $this->resolverVideoPreSubido($unidad['video_path'], $disco);

            if ($video === null) {
                return back()->withInput()->withErrors([
                    "unidades.$i.video_path" => 'El video de la unidad #'.($i + 1).' no se subió correctamente o expiró. Vuelve a subirlo.',
                ]);
            }

            $unidadesInput[$i]['video_path'] = $video;
        }

        // Las fotos de cada unidad se suben ya, antes de saber si el
        // serial choca con uno existente (eso se depura después, dentro de
        // la transacción, sin perder la evidencia ya subida).
        $unidades = collect($unidadesInput)
            ->map(fn (array $u, int $i) => [
                'no_serie' => trim((string) ($u['no_serie'] ?? '')) ?: null,
                'evidence_paths' => collect($request->file("unidades.$i.evidencias") ?? [])
                    ->map(fn ($archivo) => $archivo->store('productos/seriales', $disco))
                    ->all(),
                'video_path' => $u['video_path'] ?? null,
            ])
            ->all();

        $imagen = $request->hasFile('imagen')
            ? $request->file('imagen')->store('productos', $disco)
            : null;

        // La firma se decodifica al final, ya que se sabe que todo lo demás
        // es válido: si falla, se limpia lo que ya se subió en este mismo
        // request (los videos no, porque quedaron de requests anteriores y
        // el usuario puede reintentar sin volver a subirlos).
        $firma = $this->guardarFirma($data['firma'], $disco);

        if ($firma === null) {
            $this->borrarEvidencias(collect($unidades)->flatMap(fn ($u) => $u['evidence_paths'])->all(), $disco);

            if ($imagen) {
                Storage::disk($disco)->delete($imagen);
            }

            return back()->withInput()->withErrors([
                'firma' => 'La firma digital no es válida. Vuelve a firmar e intenta de nuevo.',
            ]);
        }

        try {
            try {
                return $this->registrarEntrada($data, $unidades, $imagen, $firma);
            } catch (QueryException $e) {
                if (! $this->esErrorDeDuplicado($e)) {
                    throw $e;
                }

                // Otra entrada del mismo modelo ganó la carrera: se
                // reintenta una vez contra la fila que ya quedó creada.
                return $this->registrarEntrada($data, $unidades, $imagen, $firma);
            }
        } catch (QueryException $e) {
            $this->borrarEvidencias(collect($unidades)->flatMap(fn ($u) => $u['evidence_paths'])->all(), $disco);
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
     * Si de todos los renglones de unidades solo uno trae número de serie
     * capturado y hay más de un renglón, se toma como base y se genera la
     * secuencia consecutiva para llenar los demás (23A12345 → 23A12346,
     * 23A12347...). Si no se cumple esa condición, se deja tal cual.
     */
    private function autocompletarSecuenciaDeUnidades(array $unidades): array
    {
        if (count($unidades) <= 1) {
            return $unidades;
        }

        $conSerie = collect($unidades)
            ->map(fn ($u) => trim((string) ($u['no_serie'] ?? '')))
            ->filter();

        if ($conSerie->count() !== 1) {
            return $unidades;
        }

        $generadas = $this->generarSecuencia($conSerie->first(), count($unidades));

        if (! $generadas) {
            return $unidades;
        }

        foreach ($unidades as $i => $unidad) {
            $unidades[$i]['no_serie'] = $generadas[$i];
        }

        return $unidades;
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
    private function registrarEntrada(array $data, array $unidades, ?string $imagen, string $firma): RedirectResponse
    {
        $disco = config('filesystems.fotos_disk', 'public');

        return DB::transaction(function () use ($data, $unidades, $imagen, $firma, $disco) {
            $cantidad = (int) $data['cantidad'];

            $productoData = [
                'equipment_type_id' => $data['equipment_type_id'],
                'subtype_id' => $data['subtype_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'equipment_model_id' => $data['equipment_model_id'] ?? null,
                'precio' => $data['precio'],
                'descripcion' => $data['descripcion'] ?? null,
            ];

            $existente = ! empty($productoData['equipment_model_id'])
                ? Producto::where('equipment_model_id', $productoData['equipment_model_id'])->lockForUpdate()->first()
                : null;

            $stockAntes = $existente->stock ?? 0;

            if ($existente) {
                $existente->precio = $productoData['precio'];
                $existente->descripcion = $productoData['descripcion'] ?? $existente->descripcion;
                $existente->proveedor = $data['proveedor'] ?? $existente->proveedor;

                // La foto de catálogo es del modelo, no de la entrada: solo
                // se reemplaza si se subió una nueva, y se borra la vieja.
                // Si no tiene, y esta entrada trae evidencia, se usa la
                // primera foto de la primera unidad como imagen representativa.
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
                    'proveedor' => $data['proveedor'] ?? null,
                    'imagen_path' => $imagen ?: ($unidades[0]['evidence_paths'][0] ?? null),
                ]);
            }

            // Ninguna unidad ni su evidencia se descarta por un serial
            // repetido: solo se limpia el serial de ese renglón (queda
            // "sin serie capturada") y se avisa, para no perder la
            // captura de las demás.
            $series = collect($unidades)->map(fn ($u) => $u['no_serie']);
            $depurado = $this->depurarSeriesDuplicadas($producto->id, $series);

            $unidades = collect($unidades)
                ->values()
                ->map(fn ($u, $i) => [
                    'no_serie' => $depurado['series'][$i],
                    'evidence_paths' => $u['evidence_paths'],
                    'video_path' => $u['video_path'],
                ])
                ->all();

            $advertencias = [];
            if ($depurado['rechazadas']->isNotEmpty()) {
                $advertencias[] = 'Estos números de serie ya existían para este producto y se guardaron sin serie (la evidencia sí se conservó): '.$depurado['rechazadas']->implode(', ').'.';
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
                'supplier' => $data['proveedor'] ?? null,
                'movement_date' => $data['movement_date'],
                'notes' => $data['notas'] ?? null,
                'evidence_paths' => collect($unidades)->flatMap(fn ($u) => $u['evidence_paths'])->all(),
                'signature_path' => $firma,
                'created_by' => auth()->id(),
            ]);

            $producto->agregarUnidades($cantidad, $unidades, $movimiento->id);

            $mensaje = "Entrada {$movimiento->folio} registrada correctamente.";

            $redirect = redirect()->route('inventory.movimientos.index')->with('status', $mensaje);

            return $advertencias ? $redirect->with('warning', implode(' ', $advertencias)) : $redirect;
        });
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

    public function show(InventoryMovement $movimiento): View
    {
        $movimiento->load(['creator', 'seriales']);

        return view('structure.gestion_Inventario.entrada_salida.show', [
            'movimiento' => $movimiento,
            'producto' => $movimiento->producto(),
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

            // La evidencia de cada unidad (hasta 3 fotos + video) se borra
            // junto con la unidad; borrar por foto_path solo no bastaría
            // porque una unidad puede tener más de una foto.
            $this->borrarEvidencias($unidades->flatMap(fn ($u) => $u->evidence_paths ?? array_filter([$u->foto_path]))->filter()->all());
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

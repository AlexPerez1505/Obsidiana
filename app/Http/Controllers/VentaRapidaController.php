<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Congress;
use App\Models\Customer;
use App\Models\OrdenSalida;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\Venta;
use App\Models\VentaBitacora;
use App\Services\CalculadoraCotizacion;
use App\Services\InventarioDeVentas;
use App\Services\OrdenesDeSalida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Venta rápida: mostrador y congresos.
 *
 * En un stand se vende un capuchón, unas pinzas, un accesorio, y nadie va
 * a abrir el cotizador ni a dar de alta al cliente. Aquí el flujo es:
 * escanear la etiqueta de cada pieza con el celular, ver el precio, elegir
 * cómo pagó y cobrar. Sale una venta normal (con su folio, su cobro y su
 * salida de inventario) a nombre de "Público en general", ya entregada,
 * porque el cliente se la llevó en la mano.
 */
class VentaRapidaController extends Controller
{
    /**
     * Patrón de una etiqueta interna: MB-000147 (pelón o dentro de la liga
     * del QR). Va anclado para que no pesque un pedazo de un número de
     * serie de fabricante como "END-GAS-OLY-GIT-0003".
     */
    private const PATRON = '/(?<![A-Za-z0-9-])([A-Za-z]{2,6}-\d{4,10})(?![A-Za-z0-9-])/';

    /** Formas de pago que se ven en un stand. El resto se registra desde cobranza. */
    private const METODOS = ['efectivo', 'tarjeta', 'transferencia'];

    public function __construct(
        private readonly CalculadoraCotizacion $calc,
        private readonly InventarioDeVentas $inventario,
        private readonly OrdenesDeSalida $ordenes,
    ) {
    }

    public function index(Request $request): View
    {
        // Solo congresos que no han terminado: los pasados no venden.
        $congresos = Congress::orderBy('fecha_inicio')->get()
            ->filter(fn (Congress $c) => $c->estado() !== 'finished')
            ->values();

        return view('structure.commercial_management.ventas.rapida', [
            'congresos' => $congresos,
            'congresoSugerido' => $this->congresoSugerido($congresos, (int) $request->user()->id)?->id,
            // En el orden en que se usan en un stand: efectivo primero.
            'metodos' => array_combine(self::METODOS, array_map(fn ($m) => Cobro::METODOS[$m], self::METODOS)),
            'garantias' => Venta::GARANTIAS,
            'ivaTasa' => CalculadoraCotizacion::IVA_TASA,
        ]);
    }

    /**
     * Qué pieza es la etiqueta escaneada, con su precio.
     *
     * A diferencia de la pantalla de escaneo de inventario, aquí sí va el
     * precio: quien está cobrando lo necesita.
     */
    public function pieza(Request $request): JsonResponse
    {
        $data = $request->validate(['codigo' => ['required', 'string', 'max:255']]);
        $leido = trim($data['codigo']);

        /*
        | Lo normal es la etiqueta MB-000147 (pelona o dentro de la liga que
        | trae el QR). Si la pieza no tiene etiqueta pegada, también vale
        | teclear el número de serie del fabricante tal cual se capturó en
        | la entrada.
        */
        $pieza = null;
        $codigo = $leido;

        if (preg_match(self::PATRON, $leido, $m)) {
            $codigo = strtoupper($m[1]);
            $pieza = ProductoSerial::where('codigo', $codigo)->with(['producto', 'congress'])->first();
        }

        if (! $pieza) {
            $pieza = ProductoSerial::whereRaw('UPPER(no_serie) = ?', [mb_strtoupper($leido)])
                ->with(['producto', 'congress'])
                ->orderByDesc('id')
                ->get()
                // Si el mismo serial se capturó dos veces, la que sigue disponible.
                ->sortBy(fn (ProductoSerial $p) => $p->vendible() ? 0 : 1)
                ->first();
        }

        if (! $pieza || ! $pieza->producto) {
            return response()->json(['encontrado' => false, 'codigo' => $codigo, 'mensaje' => "No hay ninguna pieza con la etiqueta o número de serie \"{$codigo}\"."]);
        }

        $producto = $pieza->producto;

        $motivo = match (true) {
            (bool) $pieza->vendido => 'Esta pieza ya se vendió.',
            ! $pieza->vendible() => 'Esta pieza está en '.mb_strtolower($pieza->estadoLabel()).': todavía no se puede vender.',
            default => null,
        };

        return response()->json([
            'encontrado' => true,
            'vendible' => $motivo === null,
            'motivo' => $motivo,
            'serial_id' => $pieza->id,
            'codigo' => $pieza->codigo,
            'no_serie' => $pieza->no_serie,
            'producto_id' => $producto->id,
            'nombre' => $this->nombreDe($producto),
            'marca_modelo' => trim(collect([$producto->marca, $producto->modelo])->filter()->implode(' ')),
            'precio' => $producto->precio !== null ? (float) $producto->precio : null,
            'foto' => $pieza->fotoUrl() ?: ($producto->imagen_path ? asset('storage/'.$producto->imagen_path) : null),
            'congreso_id' => $pieza->congress_id,
            'congreso' => $pieza->congress?->nombre,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'items.*.seriales' => ['nullable', 'array'],
            'items.*.seriales.*' => ['integer', 'exists:producto_seriales,id'],
            'metodo' => ['required', Rule::in(self::METODOS)],
            // Si el cliente ya está en el directorio, la venta va a su nombre;
            // si no, queda como público en general.
            'customer_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'congreso_id' => ['nullable', 'integer', 'exists:congresos_eventos,id'],
            'aplica_iva' => ['nullable', 'boolean'],
            'garantia_meses' => ['nullable', 'integer', Rule::in(Venta::GARANTIAS)],
            'nota' => ['nullable', 'string', 'max:255'],
            'referencia' => ['nullable', 'string', 'max:255'],
        ], [
            'items.required' => 'Escanea al menos una pieza.',
            'items.*.precio_unitario.required' => 'Falta el precio de una pieza.',
        ]);

        try {
            [$venta, $cobro] = DB::transaction(fn () => $this->registrar($data));
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'mensaje' => $e->getMessage()], 422);
        }

        $cliente = $venta->customer;

        return response()->json([
            'ok' => true,
            'folio' => $venta->folio,
            'cliente' => $cliente->esPublicoEnGeneral()
                ? ($venta->nota_cliente ?: Customer::PUBLICO_EN_GENERAL)
                : trim($cliente->nombre.' '.$cliente->apellido),
            'total' => (float) $venta->total,
            'metodo' => $cobro->metodoLabel(),
            'ver' => route('commercial.ventas.show', $venta),
            'recibo' => route('commercial.ventas.cobros.recibo', [$venta, $cobro]),
        ]);
    }

    /** @return array{0: Venta, 1: Cobro} */
    private function registrar(array $data): array
    {
        $aplicaIva = (bool) ($data['aplica_iva'] ?? false);
        $congresoId = $data['congreso_id'] ?? null;

        $cliente = ! empty($data['customer_id']) ? Customer::find($data['customer_id']) : null;

        // Cada asesor solo puede vender a los clientes que le aparecen a él.
        if ($cliente && ! $cliente->visiblePara(auth()->user())) {
            throw new \RuntimeException('Ese cliente no está disponible para tu usuario. Búscalo de nuevo o déjalo como público en general.');
        }

        $cliente ??= Customer::publicoEnGeneral();
        $nombreCliente = $cliente->esPublicoEnGeneral()
            ? (($data['nota'] ?? null) ?: Customer::PUBLICO_EN_GENERAL)
            : trim($cliente->nombre.' '.$cliente->apellido);

        $productos = Producto::whereIn('id', collect($data['items'])->pluck('producto_id'))->get()->keyBy('id');

        /*
        | Primero se resuelve qué piezas salen de cada renglón. Si algo no
        | alcanza se avisa antes de crear nada: la transacción se revierte
        | entera y no queda una venta a medias.
        */
        $renglones = [];

        foreach ($data['items'] as $i) {
            $producto = $productos[$i['producto_id']];
            $renglones[] = [
                'producto' => $producto,
                'cantidad' => (int) $i['cantidad'],
                'precio' => round((float) $i['precio_unitario'], 2),
                'seriales' => $this->piezasParaVender($producto, (int) $i['cantidad'], $i['seriales'] ?? [], $congresoId),
            ];
        }

        $d = $this->calc->desglose(
            array_map(fn ($r) => ['precio_unitario' => $r['precio'], 'sobreprecio' => 0, 'cantidad' => $r['cantidad'], 'es_regalo' => false], $renglones),
            null, 0, 0, $aplicaIva, 0
        );

        $venta = new Venta();
        $venta->folio = Venta::siguienteFolio();
        $venta->seller_id = auth()->id();
        $venta->customer_id = $cliente->id;
        $venta->congreso_id = $congresoId;
        $venta->nota_cliente = $data['nota'] ?? null;
        $venta->modalidad = 'contado';
        $venta->aplica_iva = $aplicaIva;
        $venta->subtotal = $d['subtotal'];
        $venta->descuento_tipo = null;
        $venta->descuento_valor = 0;
        $venta->descuento_monto = 0;
        $venta->envio = 0;
        $venta->iva_monto = $d['iva'];
        $venta->valor_a_cuenta = 0;
        $venta->total = $d['total'];
        $venta->total_contrato = $d['total_contrato'];
        $venta->plan_nombre = null;
        $venta->num_meses = 0;
        $venta->garantia_meses = (int) ($data['garantia_meses'] ?? 0);
        // Nace confirmada: ya está cobrada y entregada.
        $venta->estado = 'confirmada';
        $venta->save();

        foreach ($renglones as $orden => $r) {
            $producto = $r['producto'];

            $item = $venta->items()->create([
                'producto_id' => $producto->id,
                'tipo_item' => 'producto',
                'nombre' => $this->nombreDe($producto),
                'modelo' => $producto->modelo,
                'marca' => $producto->marca,
                // Mismo formato que la venta normal: URL completa, no la ruta en disco.
                'imagen' => $producto->imagen_path ? asset('storage/'.$producto->imagen_path) : null,
                'cantidad' => $r['cantidad'],
                'precio_unitario' => $r['precio'],
                'sobreprecio' => 0,
                'es_regalo' => false,
                'orden' => $orden,
            ]);

            // Mismo descuento de stock que la venta completa, con las piezas exactas.
            $this->inventario->asignarSerialesVendidos($item, $r['seriales']);
        }

        $this->cerrarSalida($venta, $nombreCliente);

        $cobro = Cobro::create([
            'venta_id' => $venta->id,
            'folio' => Cobro::siguienteFolio(),
            'fecha' => now()->toDateString(),
            'monto' => $venta->total,
            'metodo' => $data['metodo'],
            'referencia' => $data['referencia'] ?? null,
            'nota' => 'Venta rápida'.($venta->congreso ? ' en '.$venta->congreso->nombre : ''),
            'registrado_por' => auth()->id(),
        ]);

        VentaBitacora::registrar(
            $venta,
            'venta_rapida',
            "Venta rápida a {$nombreCliente}"
                .($venta->congreso ? " en {$venta->congreso->nombre}" : '')
                .': cobrada en '.mb_strtolower($cobro->metodoLabel()).' ($'.number_format((float) $venta->total, 2).') y entregada en el momento.',
            ['cobro_id' => $cobro->id, 'metodo' => $data['metodo'], 'congreso_id' => $congresoId]
        );

        return [$venta, $cobro];
    }

    /**
     * Qué piezas concretas salen en un renglón.
     *
     * Las escaneadas van seguras (si alguna dejó de estar disponible entre
     * escanear y cobrar, se avisa). Si se vende más cantidad de la que se
     * escaneó, el resto se completa con piezas del mismo producto: primero
     * las que están en este congreso, que son las que se tienen a la mano.
     *
     * @return array<int, int>
     *
     * @throws \RuntimeException
     */
    private function piezasParaVender(Producto $producto, int $cantidad, array $escaneadas, ?int $congresoId): array
    {
        $disponibles = ProductoSerial::where('producto_id', $producto->id)
            ->where('vendido', false)
            ->whereNotIn('estado', ProductoSerial::NO_VENDIBLES);

        $escaneadas = array_values(array_unique(array_map('intval', $escaneadas)));
        $elegidas = $escaneadas
            ? (clone $disponibles)->whereIn('id', $escaneadas)->pluck('id')->all()
            : [];

        if (count($elegidas) < count($escaneadas)) {
            throw new \RuntimeException("Alguna pieza escaneada de {$this->nombreDe($producto)} ya no está disponible. Quítala y vuelve a escanear.");
        }

        $faltan = $cantidad - count($elegidas);

        if ($faltan > 0) {
            $extra = (clone $disponibles)
                ->when($elegidas, fn ($q) => $q->whereNotIn('id', $elegidas))
                ->when(
                    $congresoId,
                    fn ($q) => $q->orderByRaw('CASE WHEN congress_id = ? THEN 0 ELSE 1 END', [$congresoId]),
                    fn ($q) => $q->orderByRaw('CASE WHEN congress_id IS NULL THEN 0 ELSE 1 END')
                )
                ->orderBy('id')
                ->take($faltan)
                ->pluck('id')
                ->all();

            if (count($extra) < $faltan) {
                $hay = count($elegidas) + count($extra);
                throw new \RuntimeException("Solo hay {$hay} disponible(s) de {$this->nombreDe($producto)} y se quieren vender {$cantidad}.");
            }

            $elegidas = array_merge($elegidas, $extra);
        }

        return array_slice($elegidas, 0, $cantidad);
    }

    /**
     * La orden de salida nace y se cierra en el mismo acto: el cliente se
     * llevó la pieza en la mano. Así almacén no la ve como pendiente y la
     * venta queda protegida igual que cualquiera ya entregada.
     */
    private function cerrarSalida(Venta $venta, string $recibe): void
    {
        $ahora = now();
        $orden = $this->ordenes->generarPara($venta);

        if ($orden) {
            $orden->items()->update([
                'preparado' => true,
                'preparado_en' => $ahora,
                'emplayado' => DB::raw('requiere_emplayado'),
                'emplayado_en' => $ahora,
            ]);

            $orden->update([
                'estado' => OrdenSalida::ENTREGADA,
                'preparada_por' => auth()->id(),
                'preparada_en' => $ahora,
                'entregada_por' => auth()->id(),
                'entregada_en' => $ahora,
                'recibe_nombre' => $recibe,
                'notas' => 'Venta rápida: el cliente se llevó el equipo en el momento, sin pasar por almacén.',
            ]);
        }

        $this->inventario->marcarEntregada($venta, $ahora);
    }

    /**
     * El congreso que se preselecciona: uno activo hoy en el que esté
     * anotado quien vende; si no, cualquiera activo; si no hay, ninguno.
     */
    private function congresoSugerido($congresos, int $userId): ?Congress
    {
        $activos = $congresos->filter(fn (Congress $c) => $c->estado() === 'active');

        return $activos->first(fn (Congress $c) => $c->notifiedUsers()->where('users.id', $userId)->exists())
            ?? $activos->first();
    }

    private function nombreDe(Producto $producto): string
    {
        return trim(collect([$producto->tipo_equipo, $producto->subtipo])->filter()->implode(' · ')) ?: 'Producto';
    }
}

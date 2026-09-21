<?php

namespace App\Http\Controllers;

use App\Models\Congress;
use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Paquete;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\Venta;
use App\Models\VentaBitacora;
use App\Models\VentaItem;
use App\Services\CalculadoraCotizacion;
use App\Services\CalendarioPagos;
use App\Services\InventarioDeVentas;
use App\Services\OrdenesDeSalida;
use App\Support\AnexosVenta;
use App\Support\DocumentoInitial;
use App\Support\FusionadorPdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function __construct(
        private readonly CalculadoraCotizacion $calc,
        private readonly InventarioDeVentas $inventario,
    ) {
    }

    public function index(): View
    {
        // items: para mostrar qué se vendió en cada fila sin abrirla.
        // Cada asesor ve las suyas, salvo que tenga permiso de ver todas.
        $ventas = Venta::visiblesPara(auth()->user())
            ->with(['customer', 'seller', 'items'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('structure.commercial_management.ventas.index', [
            'ventas' => $ventas,
            'total' => Venta::count(),
            'confirmadas' => Venta::where('estado', 'confirmada')->count(),
            'facturadas' => Venta::where('estado', 'facturada')->count(),
            'montoTotal' => (float) Venta::sum('total'),
        ]);
    }

    /**
     * Formulario de nueva venta. Con ?cotizacion=ID precarga desde la cotización.
     */
    public function create(Request $request): View
    {
        $origen = null;
        $clientePre = null;

        if ($request->filled('cotizacion')) {
            // Solo se puede convertir una cotización que el usuario pueda ver.
            $origen = Cotizacion::visiblesPara($request->user())
                ->with(['customer', 'items.producto', 'pagos', 'fichas'])
                ->find($request->integer('cotizacion'));
            $clientePre = $origen?->customer;
        } elseif ($request->filled('cliente')) {
            $clientePre = Customer::visiblesPara($request->user())->find($request->integer('cliente'));
        }

        $initial = DocumentoInitial::build($origen, $clientePre);

        /*
        | Al convertir una cotización, el calendario suele venir desfasado:
        | se cotizó hace semanas y la venta se cierra hoy. Se arrastran las
        | fechas para que el primer pago caiga hoy, conservando los días
        | entre una parcialidad y la siguiente.
        */
        if ($origen && ! empty($initial['pagos'])) {
            $initial['pagos'] = app(CalendarioPagos::class)
                ->reanclarDesdeCotizacion(collect($initial['pagos'])->all());
        }

        return view('structure.commercial_management.ventas.form', [
            'venta' => null,
            'initial' => $initial,
            'congresos' => Congress::orderBy('nombre')->get(),
            'origenId' => $origen?->id,
            'avisosStock' => $origen ? $this->avisosStock($origen->items) : [],
        ]);
    }

    /**
     * Al convertir una cotización en venta es cuando de verdad importa el
     * stock: mientras se cotiza puede no haber inventario todavía. Por eso
     * la advertencia solo aparece aquí, no al cotizar.
     *
     * Un paquete no tiene stock propio: el aviso se calcula sobre cada
     * producto que lo compone, multiplicando por la cantidad del paquete
     * en el pivote.
     */
    private function avisosStock(iterable $items): array
    {
        $avisos = [];

        foreach ($items as $item) {
            if ($item->tipo_item === 'producto' && $item->producto) {
                $this->avisoSiFalta($avisos, $item->producto, (int) $item->cantidad, $item->nombre);

                continue;
            }

            if ($item->tipo_item === 'paquete') {
                $paquete = Paquete::with('productos')->find($item->paquete_id);

                foreach ($paquete?->productos ?? [] as $producto) {
                    $necesaria = (int) $item->cantidad * (int) $producto->pivot->cantidad;
                    $nombreProducto = trim($producto->marca.' '.$producto->modelo) ?: $producto->tipo_equipo;
                    $this->avisoSiFalta($avisos, $producto, $necesaria, "{$item->nombre} → {$nombreProducto}");
                }
            }
        }

        return $avisos;
    }

    private function avisoSiFalta(array &$avisos, Producto $producto, int $necesaria, string $nombre): void
    {
        $disponible = (int) $producto->stock;

        if ($necesaria > $disponible) {
            $avisos[] = "{$nombre}: se necesitan {$necesaria}, pero solo hay {$disponible} en stock.";
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);
        $cotizacionId = $request->integer('cotizacion') ?: null;

        $venta = DB::transaction(function () use ($data, $cotizacionId) {
            $venta = new Venta();
            $venta->folio = Venta::siguienteFolio();
            $venta->seller_id = auth()->id();
            $venta->cotizacion_id = $cotizacionId;
            $this->llenarDesde($venta, $data);
            $venta->save();

            $this->guardarItems($venta, $data['items']);
            $this->guardarPagos($venta, $data);
            if (! empty($data['fichas'])) {
                $venta->fichas()->sync($data['fichas']);
            }

            if ($cotizacionId) {
                Cotizacion::whereKey($cotizacionId)->update(['estado' => 'convertida']);
            }

            // Con la venta nace la orden de salida para almacén (solo si
            // hay equipo físico que preparar).
            app(OrdenesDeSalida::class)->generarPara($venta);

            return $venta;
        });

        return redirect()->route('commercial.ventas.show', $venta)
            ->with('status', "Venta {$venta->folio} registrada correctamente.");
    }

    public function show(Venta $venta): View
    {
        $venta->load(['customer', 'seller', 'items', 'pagos', 'fichas', 'cotizacion', 'bitacora.user', 'ordenSalida']);

        return view('structure.commercial_management.ventas.show', [
            'venta' => $venta,
        ]);
    }

    public function edit(Venta $venta): View
    {
        $venta->load(['customer', 'items', 'pagos', 'fichas']);

        if ($venta->cancelada()) {
            return redirect()->route('commercial.ventas.show', $venta)
                ->withErrors(['venta' => 'Una venta cancelada no se edita. Si hace falta, regístrala de nuevo.']);
        }

        return view('structure.commercial_management.ventas.form', [
            'venta' => $venta,
            'initial' => DocumentoInitial::build($venta, $venta->customer),
            'congresos' => Congress::orderBy('nombre')->get(),
            'origenId' => null,
            // Con dinero cobrado sobre alguna parcialidad, el plan se congela
            // en el formulario: se ajusta desde Cobranza, no desde aquí.
            'planBloqueado' => $venta->planBloqueado(),
        ]);
    }

    public function update(Request $request, Venta $venta): RedirectResponse
    {
        if ($venta->cancelada()) {
            return redirect()->route('commercial.ventas.show', $venta)
                ->withErrors(['venta' => 'Una venta cancelada no se edita.']);
        }

        if ($this->inventario->equipoYaSalio($venta)) {
            return redirect()->route('commercial.ventas.show', $venta)
                ->withErrors(['venta' => 'El equipo de esta venta ya salió del almacén con firma de entrega: cambiar los renglones regresaría al inventario piezas que el cliente ya tiene. Registra la devolución del equipo antes de editarla.']);
        }

        $data = $this->validar($request);

        /*
        | Reglas para no pelearse con el dinero que ya entró:
        |
        | 1. Si alguna parcialidad ya tiene cobros, el calendario NO se
        |    rehace: borrarlo y recrearlo dejaría los cobros sin parcialidad
        |    y contradiría los recibos que el cliente ya tiene. Se conservan
        |    modalidad y meses tal como estaban, y la diferencia de montos
        |    se reparte después entre las parcialidades sin cobrar.
        |
        | 2. Si solo hay abonos sueltos (sin parcialidad), el plan sí se
        |    puede rehacer: los abonos se vuelven a aplicar sobre el plan
        |    nuevo, sin crear cobros de más.
        |
        | 3. Nunca se deja el total por debajo de lo ya cobrado.
        */
        $planBloqueado = $venta->planBloqueado();
        $cobrado = $venta->totalCobrado();

        if ($planBloqueado) {
            $data['modalidad'] = $venta->modalidad;
            $data['num_meses'] = $venta->num_meses;
            unset($data['pagos']);
        }

        $nuevoExigible = $this->exigibleDe($data);

        if ($cobrado > 0.009 && $nuevoExigible + 0.009 < $cobrado) {
            return back()->withInput()->withErrors([
                'items' => 'El nuevo total ($'.number_format($nuevoExigible, 2).') queda por debajo de lo que el cliente ya pagó ($'
                    .number_format($cobrado, 2).'). Cancela primero algún cobro en Cobranza o ajusta los montos.',
            ]);
        }

        DB::transaction(function () use ($venta, $data, $planBloqueado) {
            $this->llenarDesde($venta, $data);
            $venta->save();

            $this->inventario->liberarSerialesDe($venta);
            $venta->items()->delete();
            $this->guardarItems($venta, $data['items']);

            if (! $planBloqueado) {
                // Los cobros sueltos quedan con venta_pago_id en null al
                // borrar (nullOnDelete) y se reasignan abajo.
                $venta->pagos()->delete();
                $this->guardarPagos($venta, $data);
            }

            $venta->fichas()->sync($data['fichas'] ?? []);

            // La orden de salida se rehace con las partidas nuevas, salvo
            // que el equipo ya haya salido.
            app(OrdenesDeSalida::class)->sincronizar($venta);
        });

        $venta->refresh()->load(['pagos.cobros', 'cobros']);
        $calendario = app(CalendarioPagos::class);
        $aviso = '';

        if ($planBloqueado) {
            VentaBitacora::registrar(
                $venta,
                'items_editados',
                'Se editó la venta; el calendario se conservó porque ya tiene cobros aplicados'
            );

            $r = $calendario->rebalancear($venta);

            if (! empty($r['sin_donde']) && $r['diferencia'] > 0.009) {
                // Todas las parcialidades ya tienen cobros: la diferencia
                // no cabe en ninguna, así que se abre una nueva al final en
                // vez de dejar el plan descuadrado.
                $this->agregarParcialidadDeAjuste($venta, (float) $r['diferencia']);
                $aviso = ' Como todas las parcialidades ya tenían cobros, la diferencia de $'
                    .number_format($r['diferencia'], 2).' quedó en una parcialidad nueva al final del plan.';
            } elseif ($r['ajustadas'] > 0) {
                $aviso = ' La diferencia se repartió entre las parcialidades sin cobrar.';
            }
        } elseif ($cobrado > 0.009) {
            // Plan nuevo con abonos previos: se vuelven a aplicar, en orden,
            // sobre las parcialidades nuevas.
            $r = $calendario->absorberExcedente($venta);

            if ($r['excedente'] > 0.009) {
                $aviso = ' Los $'.number_format($r['excedente'], 2).' ya cobrados se aplicaron al plan nuevo.';
            }
        }

        return redirect()->route('commercial.ventas.show', $venta)
            ->with('status', "Venta {$venta->folio} actualizada.{$aviso}");
    }

    /**
     * Cancelar una venta. No se borra: queda como cancelada con su motivo,
     * el equipo regresa al inventario, la orden de salida se cancela y los
     * cobros se conservan como historial (si hubo dinero, se avisa cuánto
     * hay que devolver). Pide el PIN de aprobación o la contraseña.
     */
    public function cancelar(Request $request, Venta $venta): RedirectResponse
    {
        if ($venta->cancelada()) {
            return back()->withErrors(['venta' => 'Esta venta ya estaba cancelada.']);
        }

        if ($this->inventario->equipoYaSalio($venta)) {
            return back()->withErrors([
                'venta' => 'El equipo ya salió del almacén con firma de entrega. Cancelar aquí regresaría al stock piezas que el cliente tiene en su poder y borraría la salida firmada del historial: primero hay que registrar la devolución física del equipo.',
            ]);
        }

        $data = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
            'password' => ['required', 'string'],
        ], ['motivo.required' => 'Escribe el motivo de la cancelación.']);

        $user = $request->user();
        $valido = $user->approval_pin_hash
            ? $user->checkApprovalPin($data['password'])
            : \Illuminate\Support\Facades\Hash::check($data['password'], $user->password);

        if (! $valido) {
            return back()->withInput()->withErrors(['password' => 'PIN o contraseña incorrecta.']);
        }

        $cobrado = $venta->totalCobrado();

        DB::transaction(function () use ($venta, $data, $cobrado) {
            $this->inventario->liberarSerialesDe($venta);

            $venta->estado = 'cancelada';
            $venta->save();

            // La orden de almacén se marca cancelada (no se borra: queda el
            // rastro de qué se alcanzó a preparar).
            $venta->ordenSalida?->update(['estado' => \App\Models\OrdenSalida::CANCELADA]);

            VentaBitacora::registrar(
                $venta,
                'venta_cancelada',
                'Venta cancelada: '.$data['motivo']
                    .($cobrado > 0.009 ? ' · Cobrado hasta ese momento: $'.number_format($cobrado, 2).' (pendiente de devolver)' : ''),
                ['motivo' => $data['motivo'], 'cobrado' => $cobrado]
            );
        });

        $aviso = $cobrado > 0.009
            ? " El cliente tenía pagados \$".number_format($cobrado, 2).'; los cobros se conservan como historial y queda pendiente su devolución.'
            : '';

        return redirect()->route('commercial.ventas.show', $venta)
            ->with('status', "Venta {$venta->folio} cancelada. El equipo volvió al inventario.{$aviso}");
    }

    /**
     * Eliminar del todo solo se permite cuando la venta no tiene ningún
     * cobro: si ya entró dinero, se cancela (y el historial se queda).
     */
    public function destroy(Venta $venta): RedirectResponse
    {
        if ($venta->cobros()->exists()) {
            return redirect()->route('commercial.ventas.show', $venta)
                ->withErrors(['venta' => 'Esta venta ya tiene cobros registrados: no se puede eliminar, solo cancelar.']);
        }

        if ($this->inventario->equipoYaSalio($venta)) {
            return redirect()->route('commercial.ventas.show', $venta)
                ->withErrors(['venta' => 'El equipo de esta venta ya salió del almacén con firma de entrega: no se puede borrar. Registra la devolución física del equipo.']);
        }

        $folio = $venta->folio;
        $cotizacionId = $venta->cotizacion_id;

        // Si la venta viene de una cotización, se borra junto con ella: ya no
        // tiene sentido dejar la cotización suelta cuando su venta se cancela.
        DB::transaction(function () use ($venta, $cotizacionId) {
            $this->inventario->liberarSerialesDe($venta);
            app(OrdenesDeSalida::class)->cancelarDe($venta);
            $venta->delete();

            if ($cotizacionId) {
                Cotizacion::whereKey($cotizacionId)->delete();
            }
        });

        return redirect()->route('commercial.ventas.index')
            ->with('status', "Venta {$folio} eliminada.");
    }

    /** Lo que se le cobrará al cliente con los datos nuevos, antes de guardar. */
    private function exigibleDe(array $data): float
    {
        $items = array_map(fn ($i) => [
            'precio_unitario' => (float) $i['precio_unitario'],
            'sobreprecio' => (float) ($i['sobreprecio'] ?? 0),
            'cantidad' => (int) $i['cantidad'],
            'es_regalo' => (bool) ($i['es_regalo'] ?? false),
        ], $data['items']);

        $valorACuenta = (float) ($data['valor_a_cuenta'] ?? 0);

        $d = $this->calc->desglose(
            $items,
            $data['descuento_tipo'] ?? null,
            (float) ($data['descuento_valor'] ?? 0),
            (float) ($data['envio'] ?? 0),
            (bool) ($data['aplica_iva'] ?? false),
            $valorACuenta
        );

        return (float) ($valorACuenta > 0 ? $d['contrato'] : $d['total']);
    }

    /** Una parcialidad extra, un mes después de la última, con lo que no cupo. */
    private function agregarParcialidadDeAjuste(Venta $venta, float $monto): void
    {
        // pagos() ya ordena ascendente: se quita ese orden para tomar la última.
        $ultima = $venta->pagos()->reorder('orden', 'desc')->orderByDesc('id')->first();

        $venta->pagos()->create([
            'nombre' => 'Ajuste por cambio en la venta',
            'fecha' => $ultima?->fecha ? $ultima->fecha->copy()->addMonth() : now()->addMonth(),
            'porcentaje' => 0,
            'monto' => round($monto, 2),
            'bloqueado' => true,
            'orden' => ((int) $venta->pagos()->max('orden')) + 1,
        ]);

        VentaBitacora::registrar(
            $venta,
            'parcialidad_agregada',
            'Se agregó una parcialidad de ajuste por $'.number_format($monto, 2).' porque todas las demás ya tenían cobros'
        );
    }

    public function pdf(Venta $venta)
    {
        $venta->load(['customer', 'seller', 'items', 'pagos', 'fichas']);

        $pdf = Pdf::loadView('structure.commercial_management.ventas.pdf', [
            'venta' => $venta,
        ])->setPaper('letter');

        // Todo en un solo archivo: la venta, el contrato y la carta garantía,
        // y hasta el final las fichas técnicas del equipo.
        $unido = FusionadorPdf::unir($pdf->output(), $venta->fichas, AnexosVenta::para($venta));

        return response($unido['contenido'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$venta->folio}.pdf\"",
        ]);
    }

    /**
     * Contrato de compraventa a plazos.
     *
     * Solo tiene sentido cuando se paga en parcialidades: en una venta de
     * contado no hay nada que garantizar en el tiempo.
     */
    public function contrato(Venta $venta)
    {
        abort_unless($venta->requiereContrato(), 404, 'El contrato solo aplica a ventas a plazos.');

        $venta->load(['customer', 'seller', 'items', 'pagos']);

        return $this->entregarPdf(
            'structure.commercial_management.ventas.contrato',
            ['venta' => $venta],
            "Contrato-{$venta->folio}"
        );
    }

    /** Carta garantía del equipo. Aplica a toda venta. */
    public function garantia(Venta $venta)
    {
        if (! $venta->tieneGarantia()) {
            return redirect()->route('commercial.ventas.show', $venta)
                ->withErrors(['venta' => 'Esta venta se registró sin garantía: no hay carta garantía que generar.']);
        }

        $venta->load(['customer', 'seller', 'items']);

        return $this->entregarPdf(
            'structure.commercial_management.ventas.garantia',
            ['venta' => $venta],
            "Garantia-{$venta->folio}"
        );
    }

    private function entregarPdf(string $vista, array $datos, string $nombre)
    {
        $pdf = Pdf::loadView($vista, $datos)->setPaper('letter');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$nombre}.pdf\"",
        ]);
    }

    /* ===================== Helpers (espejo de CotizacionController) ===================== */

    private function validar(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', Customer::reglaVisiblePara($request->user())],
            'congreso_id' => ['nullable', 'exists:congresos_eventos,id'],
            'nota_cliente' => ['nullable', 'string'],
            'modalidad' => ['required', 'in:contado,financiamiento'],
            'aplica_iva' => ['nullable', 'boolean'],
            'descuento_tipo' => ['nullable', 'in:porcentaje,monto'],
            'descuento_valor' => ['nullable', 'numeric', 'min:0'],
            'envio' => ['nullable', 'numeric', 'min:0'],
            'valor_a_cuenta' => ['nullable', 'numeric', 'min:0'],
            'plan_nombre' => ['nullable', 'string', 'max:255'],
            'num_meses' => ['nullable', 'integer', 'min:0', 'max:60'],
            'garantia_meses' => ['nullable', 'integer', Rule::in(Venta::GARANTIAS)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.tipo_item' => ['required', 'in:equipo,paquete,producto'],
            'items.*.equipo_id' => ['nullable', 'integer'],
            'items.*.paquete_id' => ['nullable', 'integer'],
            'items.*.producto_id' => ['nullable', 'integer'],
            'items.*.nombre' => ['required', 'string', 'max:255'],
            'items.*.modelo' => ['nullable', 'string', 'max:255'],
            'items.*.marca' => ['nullable', 'string', 'max:255'],
            'items.*.imagen' => ['nullable', 'string', 'max:1024'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'items.*.sobreprecio' => ['nullable', 'numeric', 'min:0'],
            'items.*.es_regalo' => ['nullable', 'boolean'],
            // Las piezas concretas que el asesor eligió entregar.
            'items.*.seriales' => ['nullable', 'array'],
            'items.*.seriales.*' => ['integer', 'exists:producto_seriales,id'],
            'pagos' => ['nullable', 'array'],
            'pagos.*.nombre' => ['required_with:pagos', 'string', 'max:255'],
            'pagos.*.fecha' => ['nullable', 'date'],
            'pagos.*.porcentaje' => ['nullable', 'numeric', 'min:0'],
            'pagos.*.monto' => ['nullable', 'numeric', 'min:0'],
            'pagos.*.bloqueado' => ['nullable', 'boolean'],
            'fichas' => ['nullable', 'array'],
            'fichas.*' => ['integer', 'exists:fichas_tecnicas,id'],
        ]);
    }

    private function llenarDesde(Venta $venta, array $data): void
    {
        $items = array_map(fn ($i) => [
            'precio_unitario' => (float) $i['precio_unitario'],
            'sobreprecio' => (float) ($i['sobreprecio'] ?? 0),
            'cantidad' => (int) $i['cantidad'],
            'es_regalo' => (bool) ($i['es_regalo'] ?? false),
        ], $data['items']);

        $descTipo = $data['descuento_tipo'] ?? null;
        $descValor = (float) ($data['descuento_valor'] ?? 0);
        $envio = (float) ($data['envio'] ?? 0);
        $aplicaIva = (bool) ($data['aplica_iva'] ?? false);
        $valorACuenta = (float) ($data['valor_a_cuenta'] ?? 0);

        $d = $this->calc->desglose($items, $descTipo, $descValor, $envio, $aplicaIva, $valorACuenta);

        $venta->customer_id = $data['customer_id'];
        $venta->congreso_id = $data['congreso_id'] ?? null;
        $venta->nota_cliente = $data['nota_cliente'] ?? null;
        $venta->modalidad = $data['modalidad'];
        $venta->aplica_iva = $aplicaIva;
        $venta->subtotal = $d['subtotal'];
        $venta->descuento_tipo = $descValor > 0 ? $descTipo : null;
        $venta->descuento_valor = $descValor;
        $venta->descuento_monto = $d['descuento'];
        $venta->envio = $envio;
        $venta->iva_monto = $d['iva'];
        $venta->valor_a_cuenta = $valorACuenta;
        $venta->total = $d['total'];
        $venta->total_contrato = $d['total_contrato'];
        $venta->plan_nombre = $data['modalidad'] === 'financiamiento' ? ($data['plan_nombre'] ?? 'Plan Personalizado') : null;
        $venta->num_meses = $data['modalidad'] === 'financiamiento' ? (int) ($data['num_meses'] ?? 0) : 0;
        $venta->garantia_meses = (int) ($data['garantia_meses'] ?? 6);
    }

    private function guardarItems(Venta $venta, array $items): void
    {
        foreach (array_values($items) as $orden => $i) {
            $item = $venta->items()->create([
                'equipo_id' => $i['tipo_item'] === 'equipo' ? ($i['equipo_id'] ?? null) : null,
                'paquete_id' => $i['tipo_item'] === 'paquete' ? ($i['paquete_id'] ?? null) : null,
                'producto_id' => $i['tipo_item'] === 'producto' ? ($i['producto_id'] ?? null) : null,
                'tipo_item' => $i['tipo_item'],
                'nombre' => $i['nombre'],
                'modelo' => $i['modelo'] ?? null,
                'marca' => $i['marca'] ?? null,
                'imagen' => $i['imagen'] ?? null,
                'cantidad' => (int) $i['cantidad'],
                'precio_unitario' => (float) $i['precio_unitario'],
                'sobreprecio' => (float) ($i['sobreprecio'] ?? 0),
                'es_regalo' => (bool) ($i['es_regalo'] ?? false),
                'orden' => $orden,
            ]);

            if ($i['tipo_item'] === 'producto' && ! empty($i['producto_id'])) {
                $this->inventario->asignarSerialesVendidos($item, $i['seriales'] ?? []);
            }

            if ($i['tipo_item'] === 'paquete' && ! empty($i['paquete_id'])) {
                $this->inventario->descontarStockDePaquete($item);
            }
        }
    }

    private function guardarPagos(Venta $venta, array $data): void
    {
        if ($data['modalidad'] !== 'financiamiento') {
            return;
        }

        $previos = array_map(fn ($p) => [
            'nombre' => $p['nombre'] ?? null,
            'fecha' => $p['fecha'] ?? null,
            'monto' => (float) ($p['monto'] ?? 0),
            'porcentaje' => (float) ($p['porcentaje'] ?? 0),
            'bloqueado' => (bool) ($p['bloqueado'] ?? false),
        ], $data['pagos'] ?? []);

        $plan = $this->calc->planPagos((float) $venta->total_contrato, (int) $venta->num_meses, $previos);

        foreach ($plan as $p) {
            $venta->pagos()->create($p);
        }
    }
}

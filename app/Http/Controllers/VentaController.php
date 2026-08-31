<?php

namespace App\Http\Controllers;

use App\Models\Congress;
use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\Venta;
use App\Models\VentaBitacora;
use App\Services\CalculadoraCotizacion;
use App\Services\CalendarioPagos;
use App\Support\AnexosVenta;
use App\Support\DocumentoInitial;
use App\Support\FusionadorPdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function __construct(private readonly CalculadoraCotizacion $calc)
    {
    }

    public function index(): View
    {
        $ventas = Venta::with(['customer', 'seller'])->latest()->get();

        return view('structure.commercial_management.ventas.index', [
            'ventas' => $ventas,
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
            $origen = Cotizacion::with(['customer', 'items.producto', 'pagos', 'fichas'])->find($request->integer('cotizacion'));
            $clientePre = $origen?->customer;
        } elseif ($request->filled('cliente')) {
            $clientePre = Customer::find($request->integer('cliente'));
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
     */
    private function avisosStock(iterable $items): array
    {
        $avisos = [];

        foreach ($items as $item) {
            if ($item->tipo_item !== 'producto' || ! $item->producto) {
                continue;
            }

            $disponible = (int) $item->producto->stock;

            if ((int) $item->cantidad > $disponible) {
                $nombre = trim($item->producto->marca.' '.$item->producto->modelo) ?: $item->nombre;
                $avisos[] = "{$nombre}: se necesitan {$item->cantidad}, pero solo hay {$disponible} en stock.";
            }
        }

        return $avisos;
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

            return $venta;
        });

        return redirect()->route('commercial.ventas.show', $venta)
            ->with('status', "Venta {$venta->folio} registrada correctamente.");
    }

    public function show(Venta $venta): View
    {
        $venta->load(['customer', 'seller', 'items', 'pagos', 'fichas', 'cotizacion']);

        return view('structure.commercial_management.ventas.show', [
            'venta' => $venta,
        ]);
    }

    public function edit(Venta $venta): View
    {
        $venta->load(['customer', 'items', 'pagos', 'fichas']);

        return view('structure.commercial_management.ventas.form', [
            'venta' => $venta,
            'initial' => DocumentoInitial::build($venta, $venta->customer),
            'congresos' => Congress::orderBy('nombre')->get(),
            'origenId' => null,
        ]);
    }

    public function update(Request $request, Venta $venta): RedirectResponse
    {
        $data = $this->validar($request);

        /*
        | Si la venta ya tiene cobros, el calendario NO se rehace: borrarlo y
        | recrearlo dejaría los cobros sin parcialidad y contradiría los
        | recibos que el cliente ya tiene. En ese caso se respetan las
        | parcialidades y la diferencia de montos se reparte después entre
        | las que todavía no se han cobrado.
        */
        $tieneCobros = $venta->cobros()->exists();

        DB::transaction(function () use ($venta, $data, $tieneCobros) {
            $this->llenarDesde($venta, $data);
            $venta->save();

            $venta->items()->delete();
            $this->guardarItems($venta, $data['items']);

            if (! $tieneCobros) {
                $venta->pagos()->delete();
                $this->guardarPagos($venta, $data);
            }

            $venta->fichas()->sync($data['fichas'] ?? []);
        });

        if ($tieneCobros) {
            $venta->refresh()->load('pagos.cobros');

            VentaBitacora::registrar(
                $venta,
                'items_editados',
                'Se editó el equipo de la venta; el calendario se conservó por tener cobros'
            );

            $r = app(CalendarioPagos::class)->rebalancear($venta);

            $aviso = ! empty($r['sin_donde'])
                ? ' El plan quedó descuadrado y todas las parcialidades tienen cobros: agrega una parcialidad en Cobranza.'
                : ($r['ajustadas'] > 0 ? ' La diferencia se repartió entre las parcialidades sin cobrar.' : '');

            return redirect()->route('commercial.ventas.show', $venta)
                ->with('status', "Venta {$venta->folio} actualizada.{$aviso}");
        }

        return redirect()->route('commercial.ventas.show', $venta)
            ->with('status', "Venta {$venta->folio} actualizada.");
    }

    public function destroy(Venta $venta): RedirectResponse
    {
        $folio = $venta->folio;
        $cotizacionId = $venta->cotizacion_id;

        // Si la venta viene de una cotización, se borra junto con ella: ya no
        // tiene sentido dejar la cotización suelta cuando su venta se cancela.
        DB::transaction(function () use ($venta, $cotizacionId) {
            $venta->delete();

            if ($cotizacionId) {
                Cotizacion::whereKey($cotizacionId)->delete();
            }
        });

        return redirect()->route('commercial.ventas.index')
            ->with('status', "Venta {$folio} eliminada.");
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
            'customer_id' => ['required', 'exists:clientes,id'],
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
            $venta->items()->create([
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

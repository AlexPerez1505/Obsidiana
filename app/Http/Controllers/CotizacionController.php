<?php

namespace App\Http\Controllers;

use App\Models\Congress;
use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\Equipo;
use App\Models\FichaTecnica;
use App\Models\Paquete;
use App\Models\Venta;
use App\Services\CalculadoraCotizacion;
use App\Support\FusionadorPdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CotizacionController extends Controller
{
    public function __construct(private readonly CalculadoraCotizacion $calc)
    {
    }

    public function index(): View
    {
        $cotizaciones = Cotizacion::with(['customer', 'seller'])->latest()->get();

        return view('structure.commercial_management.cotizaciones.index', [
            'cotizaciones' => $cotizaciones,
        ]);
    }

    public function create(Request $request): View
    {
        return view('structure.commercial_management.cotizaciones.form', [
            'cotizacion' => null,
            'congresos' => Congress::orderBy('nombre')->get(),
            'clientePre' => $request->filled('cliente')
                ? Customer::find($request->integer('cliente'))
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        $cotizacion = DB::transaction(function () use ($data) {
            $cot = new Cotizacion();
            $cot->folio = Cotizacion::siguienteFolio();
            $cot->seller_id = auth()->id();
            $this->llenarDesde($cot, $data);
            $cot->save();

            $this->guardarItems($cot, $data['items']);
            $this->guardarPagos($cot, $data);
            $this->guardarFichas($cot, $data['fichas'] ?? []);

            return $cot;
        });

        return redirect()->route('commercial.cotizaciones.show', $cotizacion)
            ->with('status', "Cotización {$cotizacion->folio} guardada correctamente.");
    }

    public function show(Cotizacion $cotizacion): View
    {
        $cotizacion->load(['customer', 'seller', 'items', 'pagos', 'fichas']);

        return view('structure.commercial_management.cotizaciones.show', [
            'cotizacion' => $cotizacion,
        ]);
    }

    public function edit(Cotizacion $cotizacion): View
    {
        $cotizacion->load(['customer', 'items', 'pagos', 'fichas']);

        return view('structure.commercial_management.cotizaciones.form', [
            'cotizacion' => $cotizacion,
            'congresos' => Congress::orderBy('nombre')->get(),
            'clientePre' => $cotizacion->customer,
        ]);
    }

    public function update(Request $request, Cotizacion $cotizacion): RedirectResponse
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($cotizacion, $data) {
            $this->llenarDesde($cotizacion, $data);
            $cotizacion->save();

            $cotizacion->items()->delete();
            $cotizacion->pagos()->delete();
            $this->guardarItems($cotizacion, $data['items']);
            $this->guardarPagos($cotizacion, $data);
            $cotizacion->fichas()->sync($data['fichas'] ?? []);
        });

        return redirect()->route('commercial.cotizaciones.show', $cotizacion)
            ->with('status', "Cotización {$cotizacion->folio} actualizada.");
    }

    public function destroy(Cotizacion $cotizacion): RedirectResponse
    {
        $folio = $cotizacion->folio;
        $cotizacion->delete();

        return redirect()->route('commercial.cotizaciones.index')
            ->with('status', "Cotización {$folio} eliminada.");
    }

    public function pdf(Cotizacion $cotizacion)
    {
        $cotizacion->load(['customer', 'seller', 'items', 'pagos', 'fichas']);

        $pdf = Pdf::loadView('structure.commercial_management.cotizaciones.pdf', [
            'cotizacion' => $cotizacion,
        ])->setPaper('letter');

        // Las fichas técnicas se pegan al final, en el mismo archivo.
        $unido = FusionadorPdf::unir($pdf->output(), $cotizacion->fichas);

        return response($unido['contenido'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$cotizacion->folio}.pdf\"",
        ]);
    }

    /* ===================== Endpoints JSON ===================== */

    public function buscarClientes(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        $clientes = Customer::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido', 'like', "%{$q}%")
                        ->orWhere('correo', 'like', "%{$q}%")
                        ->orWhere('rfc', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->limit(15)
            ->get()
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'nombre' => trim($c->nombre.' '.$c->apellido),
                'correo' => $c->correo,
                'rfc' => $c->rfc,
            ]);

        return response()->json($clientes);
    }

    public function buscarProductos(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        $equipos = Equipo::where('activo', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('tipo', 'like', "%{$q}%")
                        ->orWhere('modelo', 'like', "%{$q}%")
                        ->orWhere('marca', 'like', "%{$q}%");
                });
            })
            ->orderBy('tipo')
            ->limit(20)
            ->get()
            ->map(fn (Equipo $e) => [
                'id' => $e->id,
                'tipo_item' => 'equipo',
                'nombre' => $e->tipo,
                'modelo' => $e->modelo,
                'marca' => $e->marca,
                'precio' => (float) $e->precio,
                'imagen' => $e->imagen ? asset('storage/'.$e->imagen) : null,
            ]);

        $paquetes = Paquete::where('activo', true)
            ->with('equipos')
            ->when($q !== '', fn ($query) => $query->where('nombre', 'like', "%{$q}%"))
            ->orderBy('nombre')
            ->limit(10)
            ->get()
            ->map(fn (Paquete $p) => [
                'id' => $p->id,
                'tipo_item' => 'paquete',
                'nombre' => $p->nombre,
                'modelo' => 'PAQUETE',
                'marca' => $p->equipos->pluck('marca')->filter()->unique()->implode(', '),
                'precio' => $p->precioCalculado(),
                'imagen' => $p->imagen ? asset('storage/'.$p->imagen) : null,
            ]);

        return response()->json($paquetes->concat($equipos)->values());
    }

    public function buscarFichas(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        $fichas = FichaTecnica::where('activo', true)
            ->when($q !== '', fn ($query) => $query->where('titulo', 'like', "%{$q}%"))
            ->orderBy('titulo')
            ->limit(20)
            ->get()
            ->map(fn (FichaTecnica $f) => [
                'id' => $f->id,
                'titulo' => $f->titulo,
            ]);

        return response()->json($fichas);
    }

    /* ===================== Helpers ===================== */

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
            'items.*.tipo_item' => ['required', 'in:equipo,paquete'],
            'items.*.equipo_id' => ['nullable', 'integer'],
            'items.*.paquete_id' => ['nullable', 'integer'],
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

    /**
     * Rellena la cabecera recalculando SIEMPRE los montos en el servidor.
     */
    private function llenarDesde(Cotizacion $cot, array $data): void
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

        $cot->customer_id = $data['customer_id'];
        // El nombre del congreso lo escribe el modelo en lugar_propuesta.
        $cot->congreso_id = $data['congreso_id'] ?? null;
        $cot->nota_cliente = $data['nota_cliente'] ?? null;
        $cot->modalidad = $data['modalidad'];
        $cot->aplica_iva = $aplicaIva;
        $cot->subtotal = $d['subtotal'];
        $cot->descuento_tipo = $descValor > 0 ? $descTipo : null;
        $cot->descuento_valor = $descValor;
        $cot->descuento_monto = $d['descuento'];
        $cot->envio = $envio;
        $cot->iva_monto = $d['iva'];
        $cot->valor_a_cuenta = $valorACuenta;
        $cot->total = $d['total'];
        $cot->total_contrato = $d['total_contrato'];
        $cot->plan_nombre = $data['modalidad'] === 'financiamiento' ? ($data['plan_nombre'] ?? 'Plan Personalizado') : null;
        $cot->num_meses = $data['modalidad'] === 'financiamiento' ? (int) ($data['num_meses'] ?? 0) : 0;
        $cot->garantia_meses = (int) ($data['garantia_meses'] ?? 6);
    }

    private function guardarItems(Cotizacion $cot, array $items): void
    {
        foreach (array_values($items) as $orden => $i) {
            $cot->items()->create([
                'equipo_id' => $i['tipo_item'] === 'equipo' ? ($i['equipo_id'] ?? null) : null,
                'paquete_id' => $i['tipo_item'] === 'paquete' ? ($i['paquete_id'] ?? null) : null,
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

    /**
     * Recalcula y guarda el plan de pagos con la calculadora (no confía en el cliente).
     */
    private function guardarPagos(Cotizacion $cot, array $data): void
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

        $plan = $this->calc->planPagos(
            (float) $cot->total_contrato,
            (int) $cot->num_meses,
            $previos,
        );

        foreach ($plan as $p) {
            $cot->pagos()->create($p);
        }
    }

    private function guardarFichas(Cotizacion $cot, array $fichas): void
    {
        if (! empty($fichas)) {
            $cot->fichas()->sync($fichas);
        }
    }
}

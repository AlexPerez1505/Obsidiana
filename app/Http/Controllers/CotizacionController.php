<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Category;
use App\Models\Congress;
use App\Models\Customer;
use App\Models\Pago;
use App\Models\PlanPago;
use App\Models\Paquete;
use App\Models\Producto;
use App\Support\SimplePdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CotizacionController extends Controller
{
    /**
     * Lista las cotizaciones registradas.
     */
    public function index(Request $request): View
    {
        $query = Cotizacion::with(['cliente', 'items', 'planPagos'])->latest();

        if ($search = $request->get('search')) {
            $query->whereHas('cliente', function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido', 'like', "%{$search}%");
            });
        }

        return view('structure.commercial_management.cotizaciones.index', [
            'cotizaciones' => $query->get(),
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Lista las remisiones (cotizaciones ya convertidas en venta definitiva).
     */
    public function remisiones(Request $request): View
    {
        $query = Cotizacion::with(['cliente', 'items', 'planPagos.pagos'])
            ->where('estado', 'remision')
            ->latest();

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('cliente', function ($cliente) use ($search) {
                    $cliente->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%")
                        ->orWhere('telefono', 'like', "%{$search}%")
                        ->orWhere('gmail', 'like', "%{$search}%");
                })
                ->orWhereHas('items', function ($items) use ($search) {
                    $items->where('nombre', 'like', "%{$search}%");
                })
                ->orWhere('id', 'like', "%{$search}%")
                ->orWhere('total', 'like', "%{$search}%");
            });
        }

        return view('structure.commercial_management.cotizaciones.remisiones', [
            'remisiones' => $query->get(),
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Muestra el formulario de creación de cotización.
     */
    public function create(Request $request): View
    {
        $clienteSeleccionado = null;
        if ($request->filled('cliente_id')) {
            $clienteSeleccionado = Customer::find($request->get('cliente_id'));
        }

        return view('structure.commercial_management.cotizaciones.create', [
            'clientes' => Customer::query()->orderBy('nombre')->get(),
            'productos' => Producto::query()->orderBy('tipo_equipo')->get(),
            'refacciones' => Refaccion::query()->orderBy('name')->get(),
            'paquetes' => Paquete::with('productos')->orderBy('nombre')->get(),
            'planesPago' => $this->planesPagoPredeterminados(),
            'categories' => Category::query()->orderBy('nombre')->get(),
            'congresses' => Congress::query()->latest()->get(),
            'clienteSeleccionado' => $clienteSeleccionado,
        ]);
    }

    /**
     * Busca clientes por nombre/teléfono (AJAX) para el buscador de la cotización.
     */
    public function buscarCliente(Request $request): JsonResponse
    {
        $search = trim($request->get('q', ''));

        $query = Customer::query();

        if ($search === '') {
            // Sin término de búsqueda: mostrar los clientes más recientes.
            $query->latest();
        } else {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        $clientes = $query->limit(10)->get(['id', 'nombre', 'apellido', 'telefono']);

        return response()->json($clientes);
    }

    /**
     * Guarda la cotización y genera automáticamente las filas de plan_pagos.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validarDatosCotizacion($request);

        $lineas = $this->construirLineas($data['items']);
        if ($lineas instanceof RedirectResponse) {
            return $lineas;
        }

        $totales = $this->calcularTotales($lineas, $data);
        $estado = $data['accion'] ?? 'cotizacion';

        $cotizacion = Cotizacion::create([
            'cliente_id' => $data['cliente_id'],
            'user_id' => auth()->id(),
            'subtotal' => $totales['subtotal'],
            'descuentos' => $totales['descuentos'],
            'iva' => $totales['iva'],
            'aplica_iva' => $totales['aplica_iva'],
            'lugar' => $data['lugar'] ?? null,
            'costo_envio' => $totales['costo_envio'],
            'anticipo' => $totales['anticipo'],
            'total' => $totales['total'],
            'estado' => $estado,
        ]);

        foreach ($lineas as $linea) {
            $cotizacion->items()->create($linea);
        }

        $this->generarPlanDePagos($cotizacion, $data, $totales);

        $mensaje = $estado === 'remision'
            ? 'Remisión creada correctamente. Ya es una venta definitiva con su plan de pagos.'
            : 'Cotización guardada con su plan de pagos.';

        return redirect()->route('commercial.cotizaciones.show', $cotizacion)
            ->with('status', $mensaje);
    }

    /**
     * Muestra el detalle de la cotización y sus planes de pago.
     */
    public function show(Cotizacion $cotizacion): View
    {
        return view('structure.commercial_management.cotizaciones.show', [
            'cotizacion' => $cotizacion->load(['cliente', 'items.producto', 'items.paquete.productos', 'planPagos.pagos']),
        ]);
    }

    public function descargarRemisionPdf(Cotizacion $cotizacion)
    {
        abort_unless($cotizacion->esRemision(), 404);

        $cotizacion->load(['cliente', 'items', 'planPagos.pagos']);
        $pdf = $this->crearPdfRemision($cotizacion);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="remision-' . $cotizacion->id . '.pdf"',
        ]);
    }

    private function crearPdfRemision(Cotizacion $cotizacion): string
    {
        $pdf = new SimplePdf();
        $cliente = trim(($cotizacion->cliente?->nombre ?? '') . ' ' . ($cotizacion->cliente?->apellido ?? '')) ?: 'Sin cliente';
        $totalPagado = $cotizacion->planPagos
            ->flatMap(fn ($plan) => $plan->pagos->where('pagado', true))
            ->sum('monto_pagado');

        $pdf->title('Remision #' . $cotizacion->id);
        $pdf->subtitle('Venta definitiva - ' . now()->format('d/m/Y H:i'));

        $pdf->section('Cliente');
        $pdf->keyValue('Cliente', $cliente);
        $pdf->keyValue('Telefono', $cotizacion->cliente?->telefono ?: 'Sin telefono');
        $pdf->keyValue('Correo', $cotizacion->cliente?->gmail ?: 'Sin correo');
        $pdf->keyValue('Lugar', $cotizacion->lugar ?: 'Sin lugar');

        $pdf->section('Productos / Paquetes');
        $pdf->table(
            ['Concepto', 'Cant.', 'Precio', 'Sobreprecio', 'Subtotal'],
            $cotizacion->items->map(function ($item) {
                return [
                    $item->nombre,
                    (string) $item->cantidad,
                    '$' . number_format((float) $item->precio_original, 2),
                    '$' . number_format((float) $item->sobreprecio, 2),
                    $item->es_regalo ? 'Obsequio' : '$' . number_format((float) $item->subtotal_linea, 2),
                ];
            })->values()->all(),
            [210, 48, 82, 82, 88]
        );

        $pdf->section('Resumen');
        $pdf->keyValue('Subtotal', '$' . number_format((float) $cotizacion->subtotal, 2));
        $pdf->keyValue('Descuentos', '$' . number_format((float) $cotizacion->descuentos, 2));
        $pdf->keyValue('IVA', '$' . number_format((float) $cotizacion->iva, 2));
        $pdf->keyValue('Costo de envio', '$' . number_format((float) $cotizacion->costo_envio, 2));
        $pdf->keyValue('Anticipo', '$' . number_format((float) $cotizacion->anticipo, 2));
        $pdf->keyValue('Total', '$' . number_format((float) $cotizacion->total, 2));
        $pdf->keyValue('Pagado', '$' . number_format((float) $totalPagado, 2));
        $pdf->keyValue('Saldo', '$' . number_format(max((float) $cotizacion->total - (float) $totalPagado, 0), 2));

        $pdf->section('Plan de pagos');
        $pdf->table(
            ['No.', 'Fecha limite', 'Metodo', 'Monto', 'Estado'],
            $cotizacion->planPagos->sortBy('no_pago')->map(function ($plan) {
                $pagado = $plan->pagos->where('pagado', true)->isNotEmpty();

                return [
                    $plan->no_pago === 0 ? 'Anticipo' : (string) $plan->no_pago,
                    $plan->plazo_pagar?->format('d/m/Y') ?? '-',
                    $plan->metodo_pago ?: '-',
                    '$' . number_format((float) $plan->monto, 2),
                    $pagado ? 'Pagado' : 'Pendiente',
                ];
            })->values()->all(),
            [70, 100, 130, 100, 110]
        );

        return $pdf->output();
    }

    /**
     * Muestra el formulario de edición de una cotización existente.
     */
    public function edit(Cotizacion $cotizacion): View
    {
        $cotizacion->load(['cliente', 'items.producto', 'items.paquete', 'planPagos.pagos']);

        return view('structure.commercial_management.cotizaciones.edit', [
            'cotizacion' => $cotizacion,
            'productos' => Producto::query()->orderBy('tipo_equipo')->get(),
            'refacciones' => Refaccion::query()->orderBy('name')->get(),
            'paquetes' => Paquete::with('productos')->orderBy('nombre')->get(),
            'planesPago' => $this->planesPagoPredeterminados(),
            'categories' => Category::query()->orderBy('nombre')->get(),
            'congresses' => Congress::query()->latest()->get(),
            'tienePagosRegistrados' => $this->tienePagosRegistrados($cotizacion),
        ]);
    }

    /**
     * Actualiza una cotización existente: productos/paquetes, montos y,
     * si aún no se han registrado pagos, el plan de pagos completo.
     */
    public function update(Request $request, Cotizacion $cotizacion): RedirectResponse
    {
        $data = $this->validarDatosCotizacion($request, esEdicion: true);

        $lineas = $this->construirLineas($data['items']);
        if ($lineas instanceof RedirectResponse) {
            return $lineas;
        }

        $totales = $this->calcularTotales($lineas, $data);

        $cotizacion->update([
            'cliente_id' => $data['cliente_id'],
            'subtotal' => $totales['subtotal'],
            'descuentos' => $totales['descuentos'],
            'iva' => $totales['iva'],
            'aplica_iva' => $totales['aplica_iva'],
            'lugar' => $data['lugar'] ?? null,
            'costo_envio' => $totales['costo_envio'],
            'anticipo' => $totales['anticipo'],
            'total' => $totales['total'],
        ]);

        $cotizacion->items()->delete();
        foreach ($lineas as $linea) {
            $cotizacion->items()->create($linea);
        }

        if (!$this->tienePagosRegistrados($cotizacion) && $request->filled('numero_pagos')) {
            $cotizacion->planPagos()->delete();
            $this->generarPlanDePagos($cotizacion, $data, $totales);
        }

        return redirect()->route('commercial.cotizaciones.show', $cotizacion)
            ->with('status', 'Cotización actualizada correctamente.');
    }

    /**
     * true si la cotización ya tiene al menos un pago registrado (no se puede
     * regenerar su plan de pagos sin perder ese historial).
     */
    private function tienePagosRegistrados(Cotizacion $cotizacion): bool
    {
        return $cotizacion->planPagos()
            ->whereHas('pagos', fn ($q) => $q->where('pagado', true))
            ->exists();
    }

    private function planesPagoPredeterminados(): Collection
    {
        return collect([
            [
                'id' => 'contado',
                'nombre' => 'Contado',
                'descripcion' => 'Pago completo en una sola exhibicion',
                'numero_pagos' => 1,
                'dias_entre_pagos' => 1,
                'metodo_pago' => 'Efectivo',
            ],
            [
                'id' => 'dos_quincenas',
                'nombre' => '2 pagos quincenales',
                'descripcion' => '2 pagos cada 15 dias',
                'numero_pagos' => 2,
                'dias_entre_pagos' => 15,
                'metodo_pago' => 'Transferencia',
            ],
            [
                'id' => 'tres_quincenas',
                'nombre' => '3 pagos quincenales',
                'descripcion' => '3 pagos cada 15 dias',
                'numero_pagos' => 3,
                'dias_entre_pagos' => 15,
                'metodo_pago' => 'Transferencia',
            ],
            [
                'id' => 'cuatro_quincenas',
                'nombre' => '4 pagos quincenales',
                'descripcion' => '4 pagos cada 15 dias',
                'numero_pagos' => 4,
                'dias_entre_pagos' => 15,
                'metodo_pago' => 'Transferencia',
            ],
            [
                'id' => 'tres_meses',
                'nombre' => '3 pagos mensuales',
                'descripcion' => '3 pagos cada 30 dias',
                'numero_pagos' => 3,
                'dias_entre_pagos' => 30,
                'metodo_pago' => 'Transferencia',
            ],
            [
                'id' => 'seis_meses',
                'nombre' => '6 pagos mensuales',
                'descripcion' => '6 pagos cada 30 dias',
                'numero_pagos' => 6,
                'dias_entre_pagos' => 30,
                'metodo_pago' => 'Transferencia',
            ],
        ])->map(fn (array $plan) => (object) $plan);
    }

    /**
     * Valida los datos comunes para crear/editar una cotización.
     * En edición, los campos del plan de pagos son opcionales (se puede
     * editar solo productos/montos sin tocar el plan de pagos).
     */
    private function validarDatosCotizacion(Request $request, bool $esEdicion = false): array
    {
        $reglaPlan = $esEdicion ? 'nullable' : 'required';

        return $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.tipo' => ['required', 'in:producto,paquete,refaccion'],
            'items.*.id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.sobreprecio' => ['nullable', 'numeric', 'min:0'],
            'items.*.es_regalo' => ['nullable', 'boolean'],
            'items.*.paquete_origen_id' => ['nullable', 'integer', 'exists:paquetes,id'],
            'descuentos' => ['nullable', 'numeric', 'min:0'],
            'aplica_iva' => ['nullable', 'boolean'],
            'costo_envio' => ['nullable', 'numeric', 'min:0'],
            'anticipo' => ['nullable', 'numeric', 'min:0'],
            'lugar' => ['nullable', 'string', 'max:255'],
            'numero_pagos' => [$reglaPlan, 'integer', 'min:1', 'max:36'],
            'metodo_pago' => [$reglaPlan, 'string', 'max:255'],
            'fecha_inicio' => [$reglaPlan, 'date'],
            'dias_entre_pagos' => [$reglaPlan, 'integer', 'min:1'],
            'montos' => ['nullable', 'array'],
            'montos.*' => ['nullable', 'numeric', 'min:0'],
            'accion' => ['nullable', 'in:cotizacion,remision'],
        ]);
    }

    /**
     * Valida existencia/stock de cada item y construye las líneas de la cotización.
     * Devuelve el arreglo de líneas, o un RedirectResponse si hubo un error de negocio
     * (producto/paquete inexistente o sin stock suficiente).
     */
    private function construirLineas(array $items): array|RedirectResponse
    {
        $lineas = [];

        foreach ($items as $item) {
            $esRegalo = (bool) ($item['es_regalo'] ?? false);
            $sobreprecio = $item['sobreprecio'] ?? 0;
            $cantidad = $item['cantidad'];

            if ($item['tipo'] === 'producto') {
                $producto = Producto::find($item['id']);
                if (!$producto) {
                    return back()->withInput()->withErrors(['items' => 'Uno de los productos seleccionados no existe.']);
                }
                if ($cantidad > $producto->stock) {
                    return back()->withInput()->withErrors([
                        'items' => "El producto {$producto->tipo_equipo} {$producto->marca} {$producto->modelo} solo tiene {$producto->stock} unidades disponibles."
                    ]);
                }

                $precioOriginal = $producto->precio;
                $precioFinal = $esRegalo ? 0 : ($precioOriginal + $sobreprecio);

                $lineas[] = [
                    'producto_id' => $producto->id,
                    'paquete_id' => $item['paquete_origen_id'] ?? null,
                    'nombre' => trim("{$producto->tipo_equipo} {$producto->marca} {$producto->modelo}"),
                    'cantidad' => $cantidad,
                    'precio_original' => $precioOriginal,
                    'sobreprecio' => $sobreprecio,
                    'precio_final' => $precioFinal,
                    'es_regalo' => $esRegalo,
                    'subtotal_linea' => $precioFinal * $cantidad,
                ];
            } elseif ($item['tipo'] === 'paquete') {
                $paquete = Paquete::with('productos')->find($item['id']);
                if (!$paquete) {
                    return back()->withInput()->withErrors(['items' => 'Uno de los paquetes seleccionados no existe.']);
                }

                foreach ($paquete->productos as $producto) {
                    $requerido = $producto->pivot->cantidad * $cantidad;
                    if ($requerido > $producto->stock) {
                        return back()->withInput()->withErrors([
                            'items' => "El paquete {$paquete->nombre} requiere {$requerido} unidades de {$producto->tipo_equipo} {$producto->marca} {$producto->modelo}, pero solo hay {$producto->stock} disponibles."
                        ]);
                    }
                }

                $precioOriginal = $paquete->productos->sum(function ($p) {
                    return $p->precio * $p->pivot->cantidad;
                });
                $precioFinal = $esRegalo ? 0 : ($precioOriginal + $sobreprecio);

                $lineas[] = [
                    'producto_id' => null,
                    'paquete_id' => $paquete->id,
                    'nombre' => $paquete->nombre,
                    'cantidad' => $cantidad,
                    'precio_original' => $precioOriginal,
                    'sobreprecio' => $sobreprecio,
                    'precio_final' => $precioFinal,
                    'es_regalo' => $esRegalo,
                    'subtotal_linea' => $precioFinal * $cantidad,
                ];
            } else {
                $refaccion = Refaccion::find($item['id']);
                if (!$refaccion) {
                    return back()->withInput()->withErrors(['items' => 'Una de las refacciones seleccionadas no existe.']);
                }
                if ($cantidad > $refaccion->stock) {
                    return back()->withInput()->withErrors([
                        'items' => "La refacción {$refaccion->name} solo tiene {$refaccion->stock} unidades disponibles."
                    ]);
                }

                $precioOriginal = $refaccion->price ?? 0;
                $precioFinal = $esRegalo ? 0 : ($precioOriginal + $sobreprecio);

                $lineas[] = [
                    'producto_id' => null,
                    'paquete_id' => null,
                    'nombre' => $refaccion->name,
                    'cantidad' => $cantidad,
                    'precio_original' => $precioOriginal,
                    'sobreprecio' => $sobreprecio,
                    'precio_final' => $precioFinal,
                    'es_regalo' => $esRegalo,
                    'subtotal_linea' => $precioFinal * $cantidad,
                ];
            }
        }

        return $lineas;
    }

    /**
     * Calcula subtotal, IVA y total a partir de las líneas y los datos del formulario.
     * El anticipo se resta del total para saber cuánto falta por distribuir en el plan de pagos.
     */
    private function calcularTotales(array $lineas, array $data): array
    {
        $subtotal = array_sum(array_column($lineas, 'subtotal_linea'));
        $descuentos = $data['descuentos'] ?? 0;
        $costoEnvio = $data['costo_envio'] ?? 0;
        $anticipo = $data['anticipo'] ?? 0;
        $aplicaIva = (bool) ($data['aplica_iva'] ?? false);
        $baseIva = max($subtotal - $descuentos, 0);
        $iva = $aplicaIva ? round($baseIva * 0.16, 2) : 0;
        $total = $subtotal - $descuentos + $iva + $costoEnvio;
        $restante = max(round($total - $anticipo, 2), 0);

        return [
            'subtotal' => $subtotal,
            'descuentos' => $descuentos,
            'costo_envio' => $costoEnvio,
            'anticipo' => $anticipo,
            'aplica_iva' => $aplicaIva,
            'iva' => $iva,
            'total' => $total,
            'restante' => $restante,
        ];
    }

    /**
     * Genera las cuotas del plan de pagos a partir del restante (total - anticipo).
     * Si hay anticipo, se registra como una cuota #0 ya marcada como pagada.
     */
    private function generarPlanDePagos(Cotizacion $cotizacion, array $data, array $totales): void
    {
        if ($totales['anticipo'] > 0) {
            $planAnticipo = PlanPago::create([
                'nombre' => 'Anticipo',
                'no_pago' => 0,
                'cliente_id' => $cotizacion->cliente_id,
                'cotizacion_id' => $cotizacion->id,
                'plazo_pagar' => $data['fecha_inicio'] ?? now()->toDateString(),
                'metodo_pago' => $data['metodo_pago'] ?? 'Efectivo',
                'monto' => $totales['anticipo'],
            ]);

            $planAnticipo->pagos()->create([
                'cliente_id' => $cotizacion->cliente_id,
                'monto_pagado' => $totales['anticipo'],
                'pago_atrasado' => false,
                'pagado' => true,
                'nota' => 'Anticipo registrado al guardar la cotización.',
            ]);
        }

        if (empty($data['numero_pagos'])) {
            return;
        }

        $fecha = Carbon::parse($data['fecha_inicio']);
        $montos = $data['montos'] ?? [];
        $montoParejo = round($totales['restante'] / $data['numero_pagos'], 2);

        for ($i = 1; $i <= $data['numero_pagos']; $i++) {
            $monto = $montos[$i - 1] ?? null;

            PlanPago::create([
                'nombre' => "Pago {$i} de {$data['numero_pagos']}",
                'no_pago' => $i,
                'cliente_id' => $cotizacion->cliente_id,
                'cotizacion_id' => $cotizacion->id,
                'plazo_pagar' => $fecha->copy()->addDays($data['dias_entre_pagos'] * ($i - 1)),
                'metodo_pago' => $data['metodo_pago'],
                'monto' => is_numeric($monto) ? $monto : $montoParejo,
            ]);
        }
    }

    /**
     * Convierte una cotización (solo presupuesto) en remisión: venta definitiva
     * donde se debe dar seguimiento formal a los pagos.
     */
    public function convertirRemision(Cotizacion $cotizacion): RedirectResponse
    {
        if ($cotizacion->estado !== 'remision') {
            $cotizacion->update(['estado' => 'remision']);
        }

        return redirect()->route('commercial.cotizaciones.show', $cotizacion)
            ->with('status', 'La cotización se convirtió en remisión. Ahora es una venta definitiva.');
    }

    /**
     * Registra el pago efectivo de una cuota (plan_pagos -> pagos).
     */
    public function storePago(Request $request, PlanPago $planPago): RedirectResponse
    {
        if (!$planPago->cotizacion || !$planPago->cotizacion->esRemision()) {
            return back()->withErrors(['monto_pagado' => 'Solo se pueden registrar pagos en remisiones (ventas definitivas). Convierte la cotización en remisión primero.']);
        }

        $data = $request->validate([
            'monto_pagado' => ['required', 'numeric', 'min:0'],
            'pago_atrasado' => ['nullable', 'boolean'],
            'nota' => ['nullable', 'string', 'max:255'],
        ]);

        Pago::create([
            'plan_pago_id' => $planPago->id,
            'cliente_id' => $planPago->cliente_id,
            'monto_pagado' => $data['monto_pagado'],
            'pago_atrasado' => $request->boolean('pago_atrasado'),
            'pagado' => true,
            'nota' => $data['nota'] ?? null,
        ]);

        return redirect()->route('commercial.cotizaciones.show', $planPago->cotizacion_id)
            ->with('status', 'Pago registrado correctamente.');
    }
}

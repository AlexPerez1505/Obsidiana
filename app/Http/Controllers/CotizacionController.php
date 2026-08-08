<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\Pago;
use App\Models\PlanPago;
use App\Models\Paquete;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CotizacionController extends Controller
{
    /**
     * Lista las cotizaciones registradas.
     */
    public function index(Request $request): View
    {
        $query = Cotizacion::with(['cliente', 'producto', 'paquete', 'planPagos'])->latest();

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
            'paquetes' => Paquete::with('productos')->orderBy('nombre')->get(),
            'clienteSeleccionado' => $clienteSeleccionado,
        ]);
    }

    /**
     * Busca clientes por nombre/teléfono (AJAX) para el buscador de la cotización.
     */
    public function buscarCliente(Request $request): JsonResponse
    {
        $search = $request->get('q', '');

        $clientes = Customer::query()
            ->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'nombre', 'apellido', 'telefono']);

        return response()->json($clientes);
    }

    /**
     * Guarda la cotización y genera automáticamente las filas de plan_pagos.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'producto_id' => ['nullable', 'exists:productos,id'],
            'paquete_id' => ['nullable', 'exists:paquetes,id'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'descuentos' => ['nullable', 'numeric', 'min:0'],
            'iva' => ['nullable', 'numeric', 'min:0'],
            'costo_envio' => ['nullable', 'numeric', 'min:0'],
            'lugar' => ['nullable', 'string', 'max:255'],
            'regalo' => ['nullable', 'boolean'],
            'numero_pagos' => ['required', 'integer', 'min:1', 'max:36'],
            'metodo_pago' => ['required', 'string', 'max:255'],
            'fecha_inicio' => ['required', 'date'],
            'dias_entre_pagos' => ['required', 'integer', 'min:1'],
        ]);

        // Validar stock disponible
        if ($data['producto_id']) {
            $producto = Producto::find($data['producto_id']);
            if ($producto->stock < 1) {
                return back()->withInput()->withErrors([
                    'producto_id' => "El producto {$producto->tipo_equipo} {$producto->marca} {$producto->modelo} no tiene stock disponible."
                ]);
            }
        }

        if ($data['paquete_id']) {
            $paquete = Paquete::with('productos')->find($data['paquete_id']);
            foreach ($paquete->productos as $producto) {
                if ($producto->pivot->cantidad > $producto->stock) {
                    return back()->withInput()->withErrors([
                        'paquete_id' => "El paquete {$paquete->nombre} requiere {$producto->pivot->cantidad} unidades de {$producto->tipo_equipo} {$producto->marca} {$producto->modelo}, pero solo hay {$producto->stock} disponibles."
                    ]);
                }
            }
        }

        $subtotal = $data['subtotal'];
        $descuentos = $data['descuentos'] ?? 0;
        $iva = $data['iva'] ?? 0;
        $costoEnvio = $data['costo_envio'] ?? 0;
        $total = $subtotal - $descuentos + $iva + $costoEnvio;

        $cotizacion = Cotizacion::create([
            'cliente_id' => $data['cliente_id'],
            'user_id' => auth()->id(),
            'producto_id' => $data['producto_id'] ?? null,
            'paquete_id' => $data['paquete_id'] ?? null,
            'subtotal' => $subtotal,
            'descuentos' => $descuentos,
            'iva' => $iva,
            'lugar' => $data['lugar'] ?? null,
            'costo_envio' => $costoEnvio,
            'total' => $total,
            'estado' => true,
            'regalo' => $request->boolean('regalo'),
        ]);

        $fecha = Carbon::parse($data['fecha_inicio']);

        for ($i = 1; $i <= $data['numero_pagos']; $i++) {
            PlanPago::create([
                'nombre' => "Pago {$i} de {$data['numero_pagos']}",
                'no_pago' => $i,
                'cliente_id' => $data['cliente_id'],
                'cotizacion_id' => $cotizacion->id,
                'plazo_pagar' => $fecha->copy()->addDays($data['dias_entre_pagos'] * ($i - 1)),
                'metodo_pago' => $data['metodo_pago'],
            ]);
        }

        return redirect()->route('commercial.cotizaciones.show', $cotizacion)
            ->with('status', 'Cotización guardada con su plan de pagos.');
    }

    /**
     * Muestra el detalle de la cotización y sus planes de pago.
     */
    public function show(Cotizacion $cotizacion): View
    {
        return view('structure.commercial_management.cotizaciones.show', [
            'cotizacion' => $cotizacion->load(['cliente', 'producto', 'paquete', 'planPagos.pagos']),
        ]);
    }

    /**
     * Agrega un plan de pago extra a una cotización existente.
     */
    public function storePlanPago(Request $request, Cotizacion $cotizacion): RedirectResponse
    {
        $data = $request->validate([
            'plazo_pagar' => ['required', 'date'],
            'metodo_pago' => ['required', 'string', 'max:255'],
        ]);

        $siguienteNumero = ($cotizacion->planPagos()->max('no_pago') ?? 0) + 1;

        PlanPago::create([
            'nombre' => "Pago {$siguienteNumero} (extra)",
            'no_pago' => $siguienteNumero,
            'cliente_id' => $cotizacion->cliente_id,
            'cotizacion_id' => $cotizacion->id,
            'plazo_pagar' => $data['plazo_pagar'],
            'metodo_pago' => $data['metodo_pago'],
        ]);

        return redirect()->route('commercial.cotizaciones.show', $cotizacion)
            ->with('status', 'Plan de pago agregado correctamente.');
    }

    /**
     * Registra el pago efectivo de una cuota (plan_pagos -> pagos).
     */
    public function storePago(Request $request, PlanPago $planPago): RedirectResponse
    {
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

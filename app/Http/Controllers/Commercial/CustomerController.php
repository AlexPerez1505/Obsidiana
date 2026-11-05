<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;

use App\Models\Category;
use App\Models\Congress;
use App\Models\Customer;
use App\Services\Clientes\RegistroClientes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    // Las reglas de alta y edición viven en el servicio: la API usa las mismas.
    public function __construct(private readonly RegistroClientes $registro)
    {
    }

    /**
     * Lista los clientes registrados.
     */
    public function index(Request $request): View
    {
        // Cada quien ve lo suyo, salvo que tenga permiso de ver todos.
        $customers = Customer::visiblesPara($request->user())
            ->with(['asesor', 'category', 'congress', 'seguimientos'])
            ->latest()
            ->get();

        return view('structure.commercial_management.customers.menu_customers', [
            'customers' => $customers,
            'veTodos' => $request->user()->can('clientes.ver_todos'),
            // Todos los congresos del sistema, no solo los que ya tienen
            // clientes: así el filtro se ve completo desde el primer día.
            'congresos' => Congress::query()->orderBy('nombre')->pluck('nombre'),
        ]);
    }

    /**
     * Muestra los datos de un cliente.
     */
    public function show(Customer $cliente): View
    {
        $this->asegurarVisible($cliente);

        return view('structure.commercial_management.customers.ver_cliente', [
            'customer' => $cliente->load(['asesor', 'category', 'congress', 'cotizaciones', 'seguimientos.responsable', 'seguimientos.hechoPor']),
            'tiposSeguimiento' => \App\Models\ClienteSeguimiento::TIPOS,
            // Solo quien ve a todos puede asignarle el seguimiento a otro.
            'usuarios' => auth()->user()->can('clientes.ver_todos')
                ? \App\Models\User::orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    /**
     * Muestra el formulario de registro de cliente.
     */
    public function create(Request $request): View
    {
        return view('structure.commercial_management.customers.registrar_cliente', [
            'categories' => Category::query()->orderBy('nombre')->get(),
            'congresses' => Congress::query()->latest()->get(),
        ]);
    }

    /**
     * Guarda un nuevo cliente. Responde JSON cuando se llama vía AJAX (ej. modal de cotizaciones).
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->registro->normalizar($request);

        if ($alto = $this->detenerSiYaExiste($request)) {
            return $alto;
        }

        $data = $request->validate($this->registro->reglasAlta(), $this->registro->mensajesAlta());

        $customer = $this->registro->registrar($data, $request->user());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'id' => $customer->id,
                'nombre' => $customer->nombre,
                'apellido' => $customer->apellido,
                'telefono' => $customer->telefono,
            ]);
        }

        return redirect()->route('commercial.clientes.index')->with('status', 'Cliente guardado correctamente.');
    }

    /**
     * Muestra el formulario de edición de cliente.
     */
    public function edit(Customer $cliente): View
    {
        $this->asegurarVisible($cliente);

        return view('structure.commercial_management.customers.actulizar_cliente', [
            'customer' => $cliente,
            'categories' => Category::query()->orderBy('nombre')->get(),
            'congresses' => Congress::query()->latest()->get(),
        ]);
    }

    /**
     * Actualiza los datos del cliente.
     */
    public function update(Request $request, Customer $cliente): RedirectResponse
    {
        $this->asegurarVisible($cliente);

        $this->registro->normalizar($request);

        if ($alto = $this->detenerSiYaExiste($request, $cliente)) {
            return $alto;
        }

        $data = $request->validate($this->registro->reglasEdicion($cliente), $this->registro->mensajesEdicion());

        $this->registro->actualizar($cliente, $data);

        return redirect()->route('commercial.clientes.index')->with('status', 'Cliente actualizado correctamente.');
    }

    /**
     * Desactiva/reactiva al cliente. No se borra: está ligado a ventas,
     * cotizaciones y servicios, así que solo sale de las listas mientras
     * está inactivo y conserva todo su historial.
     */
    public function toggleActivo(Customer $cliente): RedirectResponse
    {
        $this->asegurarVisible($cliente);

        $cliente->activo = ! $cliente->activo;
        $cliente->save();

        return back()->with('status', $cliente->activo
            ? "Se reactivó a {$cliente->nombre}."
            : "Se desactivó a {$cliente->nombre}. Ya no aparece en las listas pero conserva su historial.");
    }

    /**
     * Borra al cliente de verdad, pero solo cuando no deja nada suelto:
     * si tiene ventas, cotizaciones, servicios, facturas o pagos ligados,
     * eliminarlo rompería ese historial y ahí lo correcto es desactivarlo.
     *
     * Sirve para quitarse de encima los registros de prueba.
     */
    public function destroy(Customer $cliente): RedirectResponse
    {
        return DB::transaction(function () use ($cliente) {
            $cliente = Customer::whereKey($cliente->id)->lockForUpdate()->firstOrFail();
            $this->asegurarVisible($cliente);

            $ligado = collect([
                'ventas' => $cliente->ventas()->exists(),
                'cotizaciones' => $cliente->cotizaciones()->exists(),
                'servicios' => \App\Models\Service::where('customer_id', $cliente->id)->exists(),
                'facturas' => \App\Models\Factura::where('customer_id', $cliente->id)->exists(),
                'planes de pago' => $cliente->planPagos()->exists(),
                'pagos' => $cliente->pagos()->exists(),
            ])->filter()->keys();

            if ($ligado->isNotEmpty()) {
                return back()->withErrors(['eliminar' =>
                    "No se puede eliminar a {$cliente->nombre}: tiene {$ligado->implode(', ')} ligados. Mejor desactívalo."]);
            }

            // Lo que sí se va con él: sus seguimientos (recordatorios suyos, de nadie más).
            $cliente->seguimientos()->delete();
            $cliente->delete();

            return redirect()->route('commercial.clientes.index')
                ->with('status', "Se eliminó a {$cliente->nombre}.");
        });
    }

    /**
     * Elimina al cliente CON todo su historial (ventas, cotizaciones,
     * pagos, servicios y garantías). Está pensado para depurar registros
     * de prueba; por eso vive detrás de un permiso aparte.
     *
     * Las piezas vendidas en las ventas eliminadas vuelven a quedar
     * disponibles: la venta deja de existir y la pieza no puede quedar
     * marcada como vendida para siempre.
     */
    public function destroyDefinitivo(Customer $cliente): RedirectResponse
    {
        return DB::transaction(function () use ($cliente) {
            $cliente = Customer::whereKey($cliente->id)->lockForUpdate()->firstOrFail();
            $this->asegurarVisible($cliente);

            abort_if($cliente->esPublicoEnGeneral(), 403,
                '«Público en general» sostiene las ventas de mostrador y congresos: no se puede eliminar.');

            $nombre = $cliente->nombre.' '.$cliente->apellido;

            // Garantías y reclamaciones: sus archivos/eventos/costos caen
            // en cascada; costos y sustituciones ligadas a la garantía se
            // quitan antes porque la FK las deja colgadas (SET NULL).
            \App\Models\GarantiaReclamacion::where('customer_id', $cliente->id)->delete();
            $garantiaIds = \App\Models\Garantia::where('customer_id', $cliente->id)->pluck('id');
            \App\Models\GarantiaCosto::whereIn('garantia_id', $garantiaIds)->delete();
            \App\Models\GarantiaSustitucion::whereIn('garantia_id', $garantiaIds)->delete();
            \App\Models\Garantia::where('customer_id', $cliente->id)->delete();

            // Servicios: la FK a clientes es RESTRICT, hay que borrarlos
            // primero; sus equipos, refacciones y seguimientos caen solos.
            \App\Models\Service::where('customer_id', $cliente->id)->delete();

            // Las piezas vendidas en las ventas que se van a borrar
            // regresan a disponible antes de que desaparezca su venta.
            $ventaItemIds = DB::table('venta_items')
                ->whereIn('venta_id', $cliente->ventas()->select('id'))->pluck('id');
            \App\Models\ProductoSerial::whereIn('venta_item_id', $ventaItemIds)
                ->update(['vendido' => false, 'vendido_en' => null, 'venta_item_id' => null, 'estado' => 'disponible']);

            // Al borrar al cliente, la base elimina en cascada sus
            // seguimientos, cotizaciones, ventas, facturas, pagos, planes
            // de pago y suscripciones a campañas/promociones.
            $cliente->delete();

            return redirect()->route('commercial.clientes.index')
                ->with('status', "Se eliminó a {$nombre} con todo su historial.");
        });
    }

    /**
     * Consulta en vivo desde el formulario: ¿ya existe un cliente con este
     * teléfono o correo? Se llama al salir del campo, para avisar antes
     * de que el usuario llene todo lo demás.
     */
    public function similar(Request $request): JsonResponse
    {
        $similar = $this->registro->similar(
            $request->input('telefono'),
            $request->input('gmail'),
            $request->integer('ignorar') ?: null
        );

        return response()->json([
            'similar' => $similar ? $this->avisoDeSimilar($similar, $request) : null,
        ]);
    }

    /**
     * Si el teléfono o el correo ya son de otro cliente, no se guarda: se
     * regresa al formulario con los datos de ese cliente para mostrarlos
     * en el modal. En AJAX responde 422 con lo mismo.
     */
    private function detenerSiYaExiste(Request $request, ?Customer $ignorar = null): RedirectResponse|JsonResponse|null
    {
        $similar = $this->registro->similar($request->input('telefono'), $request->input('gmail'), $ignorar?->id);

        if (! $similar) {
            return null;
        }

        $aviso = $this->avisoDeSimilar($similar, $request);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => $aviso['mensaje'], 'cliente_similar' => $aviso], 422);
        }

        return back()
            ->withInput()
            ->withErrors([$this->registro->campoDelChoque($similar) => $aviso['mensaje']])
            ->with('cliente_similar', $aviso);
    }

    /** Arma lo que ve el usuario: el motivo en palabras y el cliente que ya existe. */
    private function avisoDeSimilar(array $similar, Request $request): array
    {
        return [
            'motivo' => $similar['motivo'],
            'mensaje' => $similar['mensaje'],
            'cliente' => $similar['cliente']->resumenParaAviso($request->user()),
        ];
    }

    /**
     * Un cliente de otro asesor no se abre ni se edita tecleando su id en
     * la URL: si no le aparece en la lista, tampoco existe para él.
     */
    private function asegurarVisible(Customer $cliente): void
    {
        abort_unless($cliente->visiblePara(auth()->user()), 403, 'Este cliente lo registró otro asesor.');
    }

    /**
     * Guarda una nueva categoría vía AJAX.
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate($this->registro->reglasCategoria());

        $category = $this->registro->registrarCategoria($data);

        return response()->json([
            'id' => $category->id,
            'nombre' => $category->nombre,
        ]);
    }
}

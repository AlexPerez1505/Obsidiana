<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;

use App\Models\Category;
use App\Models\Congress;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
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
        $this->normalizeCustomerInput($request);

        if ($alto = $this->detenerSiYaExiste($request)) {
            return $alto;
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:20', Rule::unique('clientes', 'telefono')],
            'rfc' => ['nullable', 'string', 'max:13', Rule::unique('clientes', 'rfc')],
            'gmail' => ['nullable', 'email', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'comentarios' => ['nullable', 'string'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'congreso_id' => ['nullable', 'exists:congresos_eventos,id'],
            'como_conocio' => ['nullable', 'string', 'max:255'],
            'recibe_promocion' => ['nullable', 'boolean'],
            'etapa' => ['nullable', Rule::in(array_keys(Customer::ETAPAS))],
            // Primer seguimiento, opcional: "cotizarle en un mes", etc.
            'seguimiento_tipo' => ['nullable', Rule::in(array_keys(\App\Models\ClienteSeguimiento::TIPOS))],
            'seguimiento_fecha' => ['nullable', 'date', 'after_or_equal:today', 'required_with:seguimiento_tipo'],
            'seguimiento_nota' => ['nullable', 'string', 'max:500'],
        ], [
            'telefono.unique' => 'Este teléfono ya está registrado en otro cliente.',
            'rfc.unique' => 'Este RFC ya está registrado en otro cliente.',
            'seguimiento_fecha.after_or_equal' => 'La fecha del seguimiento no puede ser anterior a hoy.',
            'seguimiento_fecha.required_with' => 'Indica la fecha del seguimiento.',
        ]);

        $data['recibe_promocion'] = $request->boolean('recibe_promocion');
        $data['activo'] = true;
        $data['asesor_id'] = auth()->id();
        $data['etapa'] = $data['etapa'] ?? 'cliente';

        $seguimiento = $request->filled('seguimiento_fecha') ? [
            'user_id' => auth()->id(),
            'tipo' => $data['seguimiento_tipo'] ?? 'llamada',
            'fecha' => $data['seguimiento_fecha'],
            'nota' => $data['seguimiento_nota'] ?? null,
            'created_by' => auth()->id(),
        ] : null;

        unset($data['seguimiento_tipo'], $data['seguimiento_fecha'], $data['seguimiento_nota']);

        $customer = Customer::create($data);

        if ($seguimiento) {
            $customer->seguimientos()->create($seguimiento);
        }

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

        $this->normalizeCustomerInput($request);

        if ($alto = $this->detenerSiYaExiste($request, $cliente)) {
            return $alto;
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:20', Rule::unique('clientes', 'telefono')->ignore($cliente->id)],
            'rfc' => ['nullable', 'string', 'max:13', Rule::unique('clientes', 'rfc')->ignore($cliente->id)],
            'gmail' => ['nullable', 'email', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'comentarios' => ['nullable', 'string'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'congreso_id' => ['nullable', 'exists:congresos_eventos,id'],
            'como_conocio' => ['nullable', 'string', 'max:255'],
            'recibe_promocion' => ['nullable', 'boolean'],
            'activo' => ['nullable', 'boolean'],
            'etapa' => ['nullable', Rule::in(array_keys(Customer::ETAPAS))],
        ], [
            'telefono.unique' => 'Este teléfono ya está registrado en otro cliente.',
            'rfc.unique' => 'Este RFC ya está registrado en otro cliente.',
        ]);

        $data['recibe_promocion'] = $request->boolean('recibe_promocion');
        $data['activo'] = $request->boolean('activo', true);
        $data['etapa'] = $data['etapa'] ?? $cliente->etapa;

        $cliente->update($data);

        return redirect()->route('commercial.clientes.index')->with('status', 'Cliente actualizado correctamente.');
    }

    /**
     * Consulta en vivo desde el formulario: ¿ya existe un cliente con este
     * teléfono o correo? Se llama al salir del campo, para avisar antes
     * de que el usuario llene todo lo demás.
     */
    public function similar(Request $request): JsonResponse
    {
        $similar = Customer::buscarSimilar(
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
        $similar = Customer::buscarSimilar($request->input('telefono'), $request->input('gmail'), $ignorar?->id);

        if (! $similar) {
            return null;
        }

        $aviso = $this->avisoDeSimilar($similar, $request);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => $aviso['mensaje'], 'cliente_similar' => $aviso], 422);
        }

        return back()
            ->withInput()
            ->withErrors([$similar['motivo'] === 'correo' ? 'gmail' : 'telefono' => $aviso['mensaje']])
            ->with('cliente_similar', $aviso);
    }

    /** Arma lo que ve el usuario: el motivo en palabras y el cliente que ya existe. */
    private function avisoDeSimilar(array $similar, Request $request): array
    {
        $mensaje = $similar['motivo'] === 'correo'
            ? 'Ya hay un cliente registrado con ese correo electrónico.'
            : 'Ya hay un cliente registrado con ese número de teléfono.';

        return [
            'motivo' => $similar['motivo'],
            'mensaje' => $mensaje,
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
     * Normaliza la entrada antes de validar.
     *
     * RFC, categoria y congreso son opcionales: cuando llegan vacios se guardan
     * como NULL para no chocar con el indice unico del RFC ni con las llaves foraneas.
     */
    private function normalizeCustomerInput(Request $request): void
    {
        $rfc = strtoupper(trim((string) $request->input('rfc')));

        $congresoId = $request->filled('congreso_id') ? $request->input('congreso_id') : null;

        $request->merge([
            'telefono' => trim((string) $request->input('telefono')),
            'rfc' => $rfc !== '' ? $rfc : null,
            'categoria_id' => $request->filled('categoria_id') ? $request->input('categoria_id') : null,
            'congreso_id' => $congresoId,
            // El congreso ya responde "cómo lo conocimos"; el texto libre
            // solo aplica cuando no se levantó en uno.
            'como_conocio' => $congresoId ? null : (trim((string) $request->input('como_conocio')) ?: null),
        ]);
    }

    /**
     * Guarda una nueva categoría vía AJAX.
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:categorias,nombre'],
        ]);

        $category = Category::create($data);

        return response()->json([
            'id' => $category->id,
            'nombre' => $category->nombre,
        ]);
    }
}

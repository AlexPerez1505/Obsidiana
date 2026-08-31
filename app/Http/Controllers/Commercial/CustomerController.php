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
        $customers = Customer::with(['asesor', 'category', 'congress'])->latest()->get();

        return view('structure.commercial_management.customers.menu_customers', [
            'customers' => $customers,
        ]);
    }

    /**
     * Muestra los datos de un cliente.
     */
    public function show(Customer $cliente): View
    {
        return view('structure.commercial_management.customers.ver_cliente', [
            'customer' => $cliente->load(['asesor', 'category', 'congress', 'cotizaciones']),
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
            'recibe_promocion' => ['nullable', 'boolean'],
        ], [
            'telefono.unique' => 'Este teléfono ya está registrado en otro cliente.',
            'rfc.unique' => 'Este RFC ya está registrado en otro cliente.',
        ]);

        $data['recibe_promocion'] = $request->boolean('recibe_promocion');
        $data['activo'] = true;
        $data['asesor_id'] = auth()->id();

        $customer = Customer::create($data);

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
        $this->normalizeCustomerInput($request);

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
            'recibe_promocion' => ['nullable', 'boolean'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'telefono.unique' => 'Este teléfono ya está registrado en otro cliente.',
            'rfc.unique' => 'Este RFC ya está registrado en otro cliente.',
        ]);

        $data['recibe_promocion'] = $request->boolean('recibe_promocion');
        $data['activo'] = $request->boolean('activo', true);

        $cliente->update($data);

        return redirect()->route('commercial.clientes.index')->with('status', 'Cliente actualizado correctamente.');
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

        $request->merge([
            'telefono' => trim((string) $request->input('telefono')),
            'rfc' => $rfc !== '' ? $rfc : null,
            'categoria_id' => $request->filled('categoria_id') ? $request->input('categoria_id') : null,
            'congreso_id' => $request->filled('congreso_id') ? $request->input('congreso_id') : null,
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

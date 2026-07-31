<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Congress;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Lista los clientes registrados.
     */
    public function index(Request $request): View
    {
        $customers = Customer::with(['seller', 'category'])->latest()->get();

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
            'customer' => $cliente->load(['seller', 'category', 'congress']),
        ]);
    }

    /**
     * Muestra el formulario de registro de cliente.
     */
    public function create(): View
    {
        return view('structure.commercial_management.customers.registrar_cliente', [
            'categories' => Category::query()->orderBy('name')->get(),
            'congresses' => Congress::all(),
        ]);
    }

    /**
     * Guarda un nuevo cliente.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'rfc' => strtoupper($request->input('rfc') ?? ''),
        ]);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'regex:/^\d{10,12}$/'],
            'correo' => ['nullable', 'email', 'unique:customers,correo'],
            'rfc' => ['required', 'string', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{2,3}$/', 'unique:customers,rfc'],
            'category_id' => ['required', 'exists:categories,id'],
            'congress_id' => ['required', 'exists:congresses,id'],
            'receives_promotion' => ['required', 'boolean'],
            'comentarios' => ['nullable', 'string'],
        ]);

        $data['apellido'] = trim($data['apellido_paterno'] . ' ' . $data['apellido_materno']);

        $data['seller_id'] = auth()->id();

        Customer::create($data);

        return redirect()->route('commercial.clientes.index')->with('status', 'Cliente guardado correctamente.');
    }

    /**
     * Muestra el formulario de edición de cliente.
     */
    public function edit(Customer $cliente): View
    {
        return view('structure.commercial_management.customers.actulizar_cliente', [
            'customer' => $cliente,
            'categories' => Category::query()->orderBy('name')->get(),
            'congresses' => Congress::all(),
        ]);
    }

    /**
     * Actualiza los datos del cliente.
     */
    public function update(Request $request, Customer $cliente): RedirectResponse
    {
        $request->merge([
            'rfc' => strtoupper($request->input('rfc') ?? ''),
        ]);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'regex:/^\d{10,12}$/'],
            'correo' => ['nullable', 'email', 'unique:customers,correo,' . $cliente->id],
            'rfc' => ['required', 'string', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{2,3}$/', 'unique:customers,rfc,' . $cliente->id],
            'category_id' => ['required', 'exists:categories,id'],
            'congress_id' => ['required', 'exists:congresses,id'],
            'receives_promotion' => ['required', 'boolean'],
            'comentarios' => ['nullable', 'string'],
        ]);

        $data['apellido'] = trim($data['apellido_paterno'] . ' ' . $data['apellido_materno']);

        $cliente->update($data);

        return redirect()->route('commercial.clientes.index')->with('status', 'Cliente actualizado correctamente.');
    }

    /**
     * Guarda una nueva categoría vía AJAX.
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        $category = Category::create($data);

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
        ]);
    }
}

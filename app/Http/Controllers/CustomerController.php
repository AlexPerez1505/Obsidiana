<?php

namespace App\Http\Controllers;

use App\Models\Congress;
use App\Models\Customer;
use App\Models\CustomerCategory;
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
     * Muestra el formulario de registro de cliente.
     */
    public function create(): View
    {
        return view('structure.commercial_management.customers.registrar_cliente', [
            'categories' => CustomerCategory::all(),
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
            'customer_category_id' => ['required', 'exists:customer_categories,id'],
            'congress_id' => ['required', 'exists:congresses,id'],
            'receives_promotion' => ['required', 'boolean'],
            'comentarios' => ['nullable', 'string'],
        ]);

        $data['apellido'] = trim($data['apellido_paterno'] . ' ' . $data['apellido_materno']);

        $data['seller_id'] = auth()->id();

        Customer::create($data);

        return redirect()->route('commercial.clientes.index')->with('status', 'Cliente guardado correctamente.');
    }
}

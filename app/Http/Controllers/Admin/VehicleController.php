<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\Administracion\Vehiculos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantalla de Vehículos. El alta (fotos y documentos) vive en Vehiculos,
 * que comparte con la API.
 */
class VehicleController extends Controller
{
    public function __construct(private Vehiculos $vehiculos)
    {
    }

    public function index(Request $request): View
    {
        $vehicles = $this->vehiculos
            ->consulta($request->get('search'), $request->get('status'))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.vehicles.index', [
            'vehicles' => $vehicles,
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(Vehiculos::reglasAlta());

        $this->vehiculos->registrar($data, $request);

        return redirect()->route('admin.vehicles.index')->with('status', 'Vehículo registrado correctamente.');
    }

    public function show(Vehicle $vehicle): View
    {
        return view('admin.vehicles.show', [
            'vehicle' => $vehicle,
        ]);
    }
}

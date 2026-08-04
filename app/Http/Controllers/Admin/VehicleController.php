<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $query = Vehicle::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $vehicles = $query->orderByDesc('created_at')->get();

        return view('admin.vehicles.index', [
            'vehicles' => $vehicles,
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plate_number'       => ['required', 'string', 'max:20', 'unique:vehicles,plate_number'],
            'vin'                => ['nullable', 'string', 'max:50'],
            'brand'              => ['nullable', 'string', 'max:50'],
            'model'              => ['nullable', 'string', 'max:50'],
            'year'               => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'color'              => ['nullable', 'string', 'max:50'],
            'engine_type'        => ['nullable', 'string', 'max:50'],
            'fuel_type'          => ['nullable', 'string', 'max:30'],
            'load_capacity'      => ['nullable', 'numeric'],
            'mileage'            => ['nullable', 'integer'],
            'fuel_efficiency'    => ['nullable', 'numeric'],
            'tank_cost'          => ['nullable', 'numeric'],
            'acquisition_date'   => ['nullable', 'date'],
            'last_maintenance'   => ['nullable', 'date'],
            'next_maintenance'   => ['nullable', 'date'],
            'last_verification'  => ['nullable', 'date'],
            'next_verification'  => ['nullable', 'date'],
            'status'             => ['nullable', 'string', 'in:active,maintenance,inactive'],
        ]);

        Vehicle::create($data);

        return redirect()->route('admin.vehicles.index')->with('status', 'Vehículo registrado correctamente.');
    }

    public function show(Vehicle $vehicle): View
    {
        return view('admin.vehicles.show', [
            'vehicle' => $vehicle,
        ]);
    }
}

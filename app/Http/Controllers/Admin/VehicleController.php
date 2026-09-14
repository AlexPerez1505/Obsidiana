<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    private const CARPETA_FOTOS = 'vehiculos/fotos';
    private const CARPETA_DOCS = 'vehiculos/documentos';

    private const DOC_FIELDS = [
        'circulation_card_doc',
        'verification_doc',
        'tenancy_doc',
        'insurance_doc',
    ];

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
            'plate_number'             => ['required', 'string', 'max:20', 'unique:vehicles,plate_number'],
            'vin'                      => ['nullable', 'string', 'max:50'],
            'brand'                    => ['nullable', 'string', 'max:50'],
            'model'                    => ['nullable', 'string', 'max:50'],
            'year'                     => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'color'                    => ['nullable', 'string', 'max:50'],
            'insurance_policy_number'  => ['nullable', 'string', 'max:100'],
            'status'                   => ['nullable', 'string', 'in:active,maintenance,inactive'],
            'photos'                   => ['nullable', 'array', 'max:10'],
            'photos.*'                 => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'circulation_card_doc'     => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'verification_doc'         => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'tenancy_doc'              => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'insurance_doc'            => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $data['photos'] = $this->subirFotos($request);

        foreach (self::DOC_FIELDS as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store(self::CARPETA_DOCS, 'public');
            } else {
                unset($data[$field]);
            }
        }

        Vehicle::create($data);

        return redirect()->route('admin.vehicles.index')->with('status', 'Vehículo registrado correctamente.');
    }

    public function show(Vehicle $vehicle): View
    {
        return view('admin.vehicles.show', [
            'vehicle' => $vehicle,
        ]);
    }

    /**
     * Guarda las fotos subidas y devuelve la lista de rutas.
     *
     * @return array<int, string>
     */
    private function subirFotos(Request $request): array
    {
        $rutas = [];

        foreach ((array) $request->file('photos', []) as $foto) {
            $rutas[] = $foto->store(self::CARPETA_FOTOS, 'public');
        }

        return $rutas;
    }
}

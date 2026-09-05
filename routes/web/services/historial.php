<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\Services\EquipmentReportController;
use App\Http\Controllers\Services\QrController;
use App\Http\Controllers\Services\ServiceController;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\EquipmentType;
use App\Models\ExternalTechnician;
use App\Models\Service;
use App\Models\Equipo;
use App\Models\GarantiaDocumento;
use App\Models\User;
use App\Models\Venta;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-servicios/historial-servicios', function () {
        $services = Service::with(['customer', 'currentStep'])->latest()->get();
        return view('structure.gestion_servicios.historial_servicios.menu_historial_servicios', compact('services'));
    })->name('gestion.servicios.historial');
    Route::get('/gestion-servicios/historial-servicios/nueva-orden', function () {
        $customers = Customer::with('asesor')->latest()->get();

        if ($clienteId = request('cliente_id')) {
            $selected = $customers->firstWhere('id', $clienteId);
            if ($selected) {
                $customers = $customers->filter(fn ($customer) => $customer->id != $clienteId)->prepend($selected)->values();
            }
        }

        $equipmentTypes = EquipmentType::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $equipos = Equipo::all();
        $externalTechnicians = ExternalTechnician::where('is_active', true)->orderBy('name')->get();
        $internalTechnicians = User::where(function ($q) {
            $q->where('status', User::STATUS_APPROVED)
              ->orWhereRaw('LOWER(name) LIKE ?', ['%joel%'])
              ->orWhereRaw('LOWER(name) LIKE ?', ['%icelda%']);
        })->orderBy('name')->get();

        return view('structure.gestion_servicios.historial_servicios.registro_servicio.c_registro_serv', compact('customers', 'equipmentTypes', 'brands', 'equipos', 'externalTechnicians', 'internalTechnicians'));
    })->name('gestion.servicios.historial.nueva_orden');

    Route::post('/gestion-servicios/historial-servicios/nueva-orden', [ServiceController::class, 'store'])
        ->name('gestion.servicios.historial.nueva_orden.store');
    Route::get('/gestion-servicios/historial-servicios/invitar', [ServiceController::class, 'invite'])
        ->name('gestion.servicios.historial.invite');
    Route::get('/gestion-servicios/historial-servicios/aprobaciones', function () {
        $services = Service::where('status', 'registrado')
            ->with(['customer', 'currentStep'])
            ->latest()
            ->get();

        return view('structure.gestion_servicios.historial_servicios.aprobaciones.index', compact('services'));
    })->name('gestion.servicios.historial.aprobaciones.index');
    Route::get('/gestion-servicios/historial-servicios/aprobaciones/{service}', function (Service $service) {
        $service->load(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician', 'spareParts', 'serviceTrackings.serviceStep', 'currentStep']);

        return view('structure.gestion_servicios.historial_servicios.aprobaciones.show', compact('service'));
    })->name('gestion.servicios.historial.aprobaciones.show');
    Route::get('/gestion-servicios/historial-servicios/{service}', [ServiceController::class, 'show'])
        ->name('gestion.servicios.historial.show');
    Route::post('/gestion-servicios/historial-servicios/{service}/aprobar', [ServiceController::class, 'approve'])
        ->name('gestion.servicios.historial.approve');
    Route::post('/gestion-servicios/historial-servicios/{service}/denegar', [ServiceController::class, 'deny'])
        ->name('gestion.servicios.historial.deny');
    Route::post('/gestion-servicios/historial-servicios/{service}/renovar-qr', [QrController::class, 'renew'])
        ->name('qr.renew');

    Route::post('/gestion-servicios/historial-servicios/nueva-orden/external-technicians', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|regex:/^[0-9\s+\-()]{7,30}$/|max:255',
            'email' => 'nullable|email:filter|max:255',
            'company' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'photo' => 'nullable|mimetypes:image/*|max:2048',
        ], [
            'name.required' => 'El nombre del técnico es obligatorio.',
            'phone.regex' => 'El teléfono solo puede contener números, espacios y los caracteres + - ( ).',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'photo.mimetypes' => 'La foto debe ser una imagen válida.',
            'photo.max' => 'La foto no debe pesar más de 2 MB.',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = Storage::disk('public')->putFile('external_technicians', $request->file('photo'));
        }

        $technician = ExternalTechnician::create($data);

        if ($request->expectsJson()) {
            return response()->json($technician);
        }

        return back();
    })->name('gestion.servicios.historial.external_technicians.store');

    Route::get('/gestion-servicios/garantia', function () {
        $documentos = GarantiaDocumento::latest()->get();
        return view('structure.gestion_servicios.garantia.index', compact('documentos'));
    })->name('gestion.servicios.garantia.index');

    Route::get('/gestion-servicios/garantia/agregar-carta', function () {
        $equipmentTypes = EquipmentType::orderBy('name')->get();
        return view('structure.gestion_servicios.garantia.create', compact('equipmentTypes'));
    })->name('gestion.servicios.garantia.agregar_carta');

    Route::post('/gestion-servicios/garantia/agregar-carta', function (Request $request) {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo_equipo' => 'required|string|max:255',
            'archivos' => 'nullable|array|max:20',
            'archivos.*' => 'file|max:20480',
        ], [
            'nombre.required' => 'El nombre de la carta es obligatorio.',
            'tipo_equipo.required' => 'El tipo de equipo es obligatorio.',
            'archivos.*.file' => 'Cada archivo debe ser un archivo válido.',
            'archivos.*.max' => 'Cada archivo no debe superar los 20 MB.',
        ]);

        $paths = [];
        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $archivo) {
                $paths[] = $archivo->store('garantias', 'public');
            }
        }

        GarantiaDocumento::create([
            'folio' => GarantiaDocumento::siguienteFolio(),
            'nombre' => $data['nombre'],
            'tipo_equipo' => $data['tipo_equipo'],
            'archivos' => $paths,
        ]);

        return redirect()->route('gestion.servicios.garantia.index')->with('success', 'Carta agregada correctamente.');
    })->name('gestion.servicios.garantia.guardar_carta');

    Route::get('/gestion-servicios/mantenimiento', function () {
        $internalTechnicians = User::where('status', User::STATUS_APPROVED)
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%joel%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%icelda%']);
            })
            ->orderBy('name')
            ->get()
            ->map(function (User $technician) {
                $activeCount = Service::where('internal_technician_id', $technician->id)
                    ->whereNotIn('status', ['entregado', 'cancelado'])
                    ->count();

                return (object) [
                    'id' => $technician->id,
                    'name' => $technician->name,
                    'email' => $technician->email,
                    'initials' => collect(explode(' ', $technician->name))->map(fn ($w) => strtoupper(substr($w, 0, 1)))->take(2)->join(''),
                    'count' => $activeCount,
                    'count_label' => 'activo',
                    'is_external' => false,
                ];
            });

        $externalTotal = Service::where('service_type', 'externo')->count();
        $externalActive = Service::where('service_type', 'externo')
            ->whereNotIn('status', ['entregado', 'cancelado'])
            ->count();

        $externalOption = (object) [
            'id' => 'externo',
            'name' => 'Mantenimientos externos',
            'email' => 'Todos los servicios externos',
            'initials' => 'EXT',
            'count' => $externalTotal,
            'count_label' => 'servicio',
            'is_external' => true,
        ];

        $technicians = $internalTechnicians->push($externalOption)->values();

        if (request('tipo') === 'externo') {
            $selected = $externalOption;
        } else {
            $selectedId = request('tecnico');
            $selected = $technicians->firstWhere('id', (int) $selectedId) ?? $technicians->first();
        }

        $services = collect();
        if ($selected) {
            if ($selected->is_external) {
                $services = Service::where('service_type', 'externo')
                    ->with(['customer', 'currentStep'])
                    ->latest()
                    ->get();
            } else {
                $services = Service::where('internal_technician_id', $selected->id)
                    ->whereNotIn('status', ['entregado', 'cancelado'])
                    ->with(['customer', 'currentStep'])
                    ->latest()
                    ->get();
            }
        }

        return view('structure.gestion_servicios.mantenimiento.index', compact('technicians', 'selected', 'services'));
    })->name('gestion.servicios.mantenimiento.index');
});

Route::get('/nueva-orden/{invitation}', [ServiceController::class, 'createFromInvitation'])
    ->name('public.nueva_orden');
Route::post('/nueva-orden/{invitation}', [ServiceController::class, 'publicStore'])
    ->name('public.nueva_orden.store');

Route::get('/qr/{token}', [QrController::class, 'show'])
    ->name('qr.show');
Route::post('/qr/{token}', [QrController::class, 'update'])
    ->name('qr.update');
Route::get('/qr/{token}/imprimir', [QrController::class, 'print'])
    ->name('qr.print');
Route::get('/qr-completado', function () {
    return view('structure.gestion_servicios.historial_servicios.qr.completed');
})->name('qr.completed');

Route::get('/reporte-equipo', [EquipmentReportController::class, 'create'])
    ->name('reporte.equipo.create');
Route::post('/reporte-equipo', [EquipmentReportController::class, 'store'])
    ->name('reporte.equipo.store');
Route::post('/api/qr/generar', [EquipmentReportController::class, 'generateQr'])
    ->name('qr.generar');

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\Services\QrController;
use App\Http\Controllers\Services\ServiceController;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\EquipmentType;
use App\Models\ExternalTechnician;
use App\Models\User;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-servicios/historial-servicios', function () {
        $cotizaciones = \Illuminate\Support\Facades\DB::table('cotizacion_servicios')
            ->join('registro_servicios', 'registro_servicios.id_servicio', '=', 'cotizacion_servicios.id_servicio')
            ->join('clientes', 'clientes.id', '=', 'registro_servicios.id_cliente')
            ->select(
                'cotizacion_servicios.id',
                'cotizacion_servicios.id_servicio',
                'cotizacion_servicios.tipo_cotizacion',
                'cotizacion_servicios.estado',
                'cotizacion_servicios.total',
                'cotizacion_servicios.created_at',
                'clientes.nombre as cliente_nombre',
                'clientes.apellido as cliente_apellido'
            )
            ->orderByDesc('cotizacion_servicios.created_at')
            ->get();

        return view('structure.gestion_servicios.historial_servicios.menu_historial_servicios', compact('cotizaciones'));
    })->name('gestion.servicios.historial');
    Route::get('/gestion-servicios/historial-servicios/nueva-orden', function () {
        $customers = Customer::with('asesor')->latest()->get();

        if ($clienteId = request('cliente_id')) {
            $selected = $customers->firstWhere('id', $clienteId);
            if ($selected) {
                $customers = $customers->filter(fn ($customer) => $customer->id != $clienteId)->prepend($selected)->values();
            }
        }

        $equipmentTypes = EquipmentType::orderBy('name')->pluck('name')
            ->map(fn ($name) => (object) ['name' => $name]);
        $brands = Brand::orderBy('name')->pluck('name')
            ->map(fn ($name) => (object) ['name' => $name]);
        $externalTechnicians = \Illuminate\Support\Facades\DB::table('tecnico_externo')
            ->selectRaw("id, CONCAT(nombre, ' ', apellidos) as name, telefono as phone, correo as email, especialidad as specialty, empresa as company, domicilio as location, NULL as photo")
            ->whereNull('deleted_at')
            ->orderBy('nombre')
            ->get();
        $internalTechnicians = User::where('status', User::STATUS_APPROVED)->orderBy('name')->get();

        return view('structure.gestion_servicios.historial_servicios.registro_servicio.c_registro_serv', compact('customers', 'equipmentTypes', 'brands', 'externalTechnicians', 'internalTechnicians'));
    })->name('gestion.servicios.historial.nueva_orden');

    Route::get('/gestion-servicios/historial-servicios/nueva-orden/subtipos', function (Request $request) {
        $category = trim((string) $request->input('equipment_type_name'));

        $subtypes = \App\Models\Subtype::whereHas('equipmentType', fn ($q) => $q->where('name', $category))
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($name, $i) => ['id' => $i + 1, 'name' => $name])
            ->values();

        return response()->json($subtypes);
    })->name('gestion.servicios.historial.nueva_orden.subtipos');

    Route::get('/gestion-servicios/historial-servicios/nueva-orden/modelos', function (Request $request) {
        $brand = trim((string) $request->input('brand_name'));

        $models = \App\Models\EquipmentModel::whereHas('brand', fn ($q) => $q->where('name', $brand))
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($name, $i) => ['id' => $i + 1, 'name' => $name])
            ->values();

        return response()->json($models);
    })->name('gestion.servicios.historial.nueva_orden.modelos');

    Route::post('/gestion-servicios/historial-servicios/nueva-orden', [ServiceController::class, 'store'])
        ->name('gestion.servicios.historial.nueva_orden.store');
    Route::get('/gestion-servicios/historial-servicios/{service}', [ServiceController::class, 'show'])
        ->name('gestion.servicios.historial.show');
    Route::get('/gestion-servicios/historial-servicios/invitar', [ServiceController::class, 'invite'])
        ->name('gestion.servicios.historial.invite');

    Route::get('/qr/{token}', [QrController::class, 'show'])
        ->name('qr.show');
    Route::post('/qr/{token}', [QrController::class, 'update'])
        ->name('qr.update');

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
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $parts = preg_split('/\s+/', trim($data['name']), 2);

        $id = \Illuminate\Support\Facades\DB::table('tecnico_externo')->insertGetId([
            'nombre' => $parts[0],
            'apellidos' => $parts[1] ?? '',
            'telefono' => $data['phone'] ?? '',
            'domicilio' => $data['address'] ?? ($data['location'] ?? ''),
            'correo' => $data['email'] ?? '',
            'especialidad' => $data['specialty'] ?? '',
            'empresa' => $data['company'] ?? '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $technician = \Illuminate\Support\Facades\DB::table('tecnico_externo')
            ->selectRaw("id, CONCAT(nombre, ' ', apellidos) as name, telefono as phone, correo as email, especialidad as specialty, empresa as company, domicilio as location, NULL as photo")
            ->find($id);

        if ($request->expectsJson()) {
            return response()->json($technician);
        }

        return back();
    })->name('gestion.servicios.historial.external_technicians.store');
});

Route::get('/nueva-orden/{invitation}', [ServiceController::class, 'createFromInvitation'])
    ->name('public.nueva_orden');
Route::post('/nueva-orden/{invitation}', [ServiceController::class, 'publicStore'])
    ->name('public.nueva_orden.store');

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
use App\Models\Service;
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
        $externalTechnicians = ExternalTechnician::where('is_active', true)->orderBy('name')->get();
        $internalTechnicians = User::where(function ($q) {
            $q->where('status', User::STATUS_APPROVED)
              ->orWhereRaw('LOWER(name) LIKE ?', ['%joel%'])
              ->orWhereRaw('LOWER(name) LIKE ?', ['%icelda%']);
        })->orderBy('name')->get();

        return view('structure.gestion_servicios.historial_servicios.registro_servicio.c_registro_serv', compact('customers', 'equipmentTypes', 'brands', 'externalTechnicians', 'internalTechnicians'));
    })->name('gestion.servicios.historial.nueva_orden');

    Route::post('/gestion-servicios/historial-servicios/nueva-orden', [ServiceController::class, 'store'])
        ->name('gestion.servicios.historial.nueva_orden.store');
    Route::get('/gestion-servicios/historial-servicios/{service}', [ServiceController::class, 'show'])
        ->name('gestion.servicios.historial.show');
    Route::post('/gestion-servicios/historial-servicios/{service}/aprobar', [ServiceController::class, 'approve'])
        ->name('gestion.servicios.historial.approve');
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
        $ventas = Venta::with('customer')->where('garantia_meses', '>', 0)->latest()->get();
        return view('structure.gestion_servicios.garantia.index', compact('ventas'));
    })->name('gestion.servicios.garantia.index');
    Route::get('/gestion-servicios/mantenimiento', function () {
        $services = Service::with(['customer', 'currentStep'])->latest()->get();
        return view('structure.gestion_servicios.mantenimiento.index', compact('services'));
    })->name('gestion.servicios.mantenimiento.index');
});

Route::get('/nueva-orden/{invitation}', [ServiceController::class, 'createFromInvitation'])
    ->name('public.nueva_orden');
Route::post('/nueva-orden/{invitation}', [ServiceController::class, 'publicStore'])
    ->name('public.nueva_orden.store');

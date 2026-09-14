<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Services\ServiceController;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-servicios/area', function () {
        $services = Service::with(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician', 'currentStep'])
            ->where(function ($q) {
                $q->where('status', 'registrado')
                    ->orWhere(function ($q2) {
                        $q2->where('service_type', 'interno')
                            ->whereIn('status', ['aprobado', 'en_progreso', 'completado', 'entregado']);
                    });
            })
            ->whereHas('serviceEquipment', function ($q) {
                $q->whereRaw('LOWER(type_text) NOT LIKE ?', ['%endoscop%']);
            })
            ->latest()
            ->get();

        return view('structure.gestion_servicios.Area_Endoscopia.Area', compact('services'));
    })->name('gestion.servicios.area');

    Route::get('/gestion-servicios/area-endoscopia', function () {
        $services = Service::with(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician', 'currentStep'])
            ->where(function ($q) {
                $q->where('status', 'registrado')
                    ->orWhere(function ($q2) {
                        $q2->where('service_type', 'interno')
                            ->whereIn('status', ['aprobado', 'en_progreso', 'completado', 'entregado']);
                    });
            })
            ->whereHas('serviceEquipment', function ($q) {
                $q->whereRaw('LOWER(type_text) LIKE ?', ['%endoscop%']);
            })
            ->latest()
            ->get();

        return view('structure.gestion_servicios.Area_Endoscopia.Area_E', compact('services'));
    })->name('gestion.servicios.area_endoscopia');

    Route::get('/gestion-servicios/area-endoscopia/{service}/resumen', function (Service $service) {
        $service->load(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician', 'currentStep', 'spareParts.refaccion']);

        return view('structure.gestion_servicios.Area_Endoscopia.Resumen', compact('service'));
    })->name('gestion.servicios.area_endoscopia.resumen');

    Route::post('/gestion-servicios/area-endoscopia/{service}/aprobar', function (Request $request, Service $service) {
        $request->validate([
            'service_type' => 'required|in:interno,externo',
        ]);

        $service->update([
            'service_type' => $request->input('service_type'),
        ]);

        return redirect()->route('gestion.servicios.historial.aprobaciones.show', $service)
            ->with('success', "Orden {$service->service_number} enviada a aprobaciones como mantenimiento {$request->input('service_type')}.");
    })->name('gestion.servicios.area_endoscopia.aprobar');

    Route::get('/gestion-servicios/area-endoscopia/{service}/cotizacion', [ServiceController::class, 'cotizacion'])
        ->name('gestion.servicios.area_endoscopia.cotizacion');
    Route::post('/gestion-servicios/area-endoscopia/{service}/cotizacion', [ServiceController::class, 'storeCotizacion'])
        ->name('gestion.servicios.area_endoscopia.cotizacion.store');
    Route::get('/gestion-servicios/area-endoscopia/{service}/cotizacion/pdf', function (Service $service) {
        $service->load(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician', 'spareParts.refaccion']);

        $totalRefacciones = $service->spareParts->sum('subtotal');
        $total = $totalRefacciones + ($service->mano_obra ?? 0);

        $pdf = Pdf::loadView('structure.gestion_servicios.Area_Endoscopia.CotizacionPdf', compact('service', 'totalRefacciones', 'total'));

        return $pdf->download('cotizacion-' . ($service->service_number ?? ('OS-' . $service->id)) . '.pdf');
    })->name('gestion.servicios.area_endoscopia.cotizacion.pdf');

    Route::get('/gestion-servicios/area-endoscopia/{service}/editar', [ServiceController::class, 'edit'])
        ->name('gestion.servicios.area_endoscopia.edit');
    Route::put('/gestion-servicios/area-endoscopia/{service}', [ServiceController::class, 'update'])
        ->name('gestion.servicios.area_endoscopia.update');
    Route::delete('/gestion-servicios/area-endoscopia/{service}', [ServiceController::class, 'destroy'])
        ->name('gestion.servicios.area_endoscopia.destroy');
});

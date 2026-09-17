<?php

use Illuminate\Support\Facades\Route;
use App\Models\Service;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-servicios/servicio-externo/interno', function () {
        $services = Service::with(['customer', 'serviceEquipment', 'internalTechnician', 'currentStep'])
            ->where('service_type', 'interno')
            ->whereIn('status', ['aprobado', 'en_progreso', 'completado', 'entregado'])
            ->latest()
            ->get();

        return view('structure.gestion_servicios.Servicio_Externo.Interno', compact('services'));
    })->name('gestion.servicios.servicio_externo.interno');

    Route::get('/gestion-servicios/servicio-externo/externo', function () {
        $services = Service::with(['customer', 'serviceEquipment', 'externalTechnician', 'currentStep'])
            ->where('service_type', 'externo')
            ->whereIn('status', ['aprobado', 'en_progreso', 'completado', 'entregado'])
            ->latest()
            ->get();

        return view('structure.gestion_servicios.Servicio_Externo.Externo', compact('services'));
    })->name('gestion.servicios.servicio_externo.externo');
});

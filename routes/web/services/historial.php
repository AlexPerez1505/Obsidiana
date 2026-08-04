<?php

use Illuminate\Support\Facades\Route;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\EquipmentType;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::view('/gestion-servicios/historial-servicios', 'structure.gestion_servicios.historial_servicios.menu_historial_servicios')
        ->name('gestion.servicios.historial');
    Route::get('/gestion-servicios/historial-servicios/nueva-orden', function () {
        $customers = Customer::with('seller')->latest()->get();

        if ($clienteId = request('cliente_id')) {
            $selected = $customers->firstWhere('id', $clienteId);
            if ($selected) {
                $customers = $customers->filter(fn ($customer) => $customer->id != $clienteId)->prepend($selected)->values();
            }
        }

        $equipmentTypes = EquipmentType::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();

        return view('structure.gestion_servicios.historial_servicios.registro_servicio.c_registro_serv', compact('customers', 'equipmentTypes', 'brands'));
    })->name('gestion.servicios.historial.nueva_orden');
});

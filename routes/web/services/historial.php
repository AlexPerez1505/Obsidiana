<?php

use Illuminate\Support\Facades\Route;
use App\Models\Customer;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::view('/gestion-servicios/historial-servicios', 'structure.gestion_servicios.historial_servicios.menu_historial_servicios')
        ->name('gestion.servicios.historial');
    Route::get('/gestion-servicios/historial-servicios/nueva-orden', function () {
        $customers = Customer::with('seller')->latest()->get();
        return view('structure.gestion_servicios.historial_servicios.nueva_orden.c_nueva_orden', compact('customers'));
    })->name('gestion.servicios.historial.nueva_orden');
});

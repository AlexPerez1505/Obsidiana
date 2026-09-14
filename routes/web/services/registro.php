<?php

use Illuminate\Support\Facades\Route;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\EquipmentType;
use App\Models\User;
use App\Http\Controllers\Services\ServiceController;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-servicios/registro', function () {
        $equipmentTypes = EquipmentType::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $customers = Customer::with('asesor')->latest()->get();
        $technicians = User::where('status', User::STATUS_APPROVED)->orderBy('name')->get();

        return view('structure.gestion_servicios.Registro.Registro', compact('equipmentTypes', 'brands', 'customers', 'technicians'));
    })->name('gestion.servicios.registro');

    Route::post('/gestion-servicios/registro', [ServiceController::class, 'store'])
        ->name('gestion.servicios.registro.store');
});

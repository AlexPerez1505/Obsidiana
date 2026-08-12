<?php

use App\Http\Controllers\Services\ServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-servicios/historial-servicios/aprobaciones', [ServiceController::class, 'pendingApprovals'])
        ->name('service-tracking.approvals');
    Route::post('/gestion-servicios/historial-servicios/seguimiento/{tracking}/aprobar', [ServiceController::class, 'approveStep'])
        ->name('service-tracking.approve');
    Route::post('/gestion-servicios/historial-servicios/seguimiento/{tracking}/rechazar', [ServiceController::class, 'rejectStep'])
        ->name('service-tracking.reject');
});

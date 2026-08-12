<?php

use App\Http\Controllers\Services\QrController;
use App\Http\Controllers\Services\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/qr/{token}', [QrController::class, 'show'])->name('qr.show');
Route::post('/qr/{token}/verify-code', [QrController::class, 'verifyCode'])->name('qr.verify-code');
Route::post('/qr/{token}/complete', [QrController::class, 'complete'])->name('qr.complete');

Route::middleware(['auth', 'verified', 'approved', 'admin'])
    ->prefix('gestion-servicios/historial-servicios')
    ->group(function () {
        Route::get('/aprobaciones', [ServiceController::class, 'pendingApprovals'])
            ->name('gestion.servicios.aprobaciones');

        Route::post('/seguimiento/{tracking}/aprobar', [ServiceController::class, 'approveService'])
            ->name('gestion.servicios.tracking.aprobar');
    });

Route::middleware(['auth', 'verified', 'approved'])
    ->prefix('gestion-servicios/historial-servicios')
    ->group(function () {
        Route::get('/mantenimiento', [ServiceController::class, 'maintenanceOrders'])
            ->name('gestion.servicios.mantenimiento');
    });

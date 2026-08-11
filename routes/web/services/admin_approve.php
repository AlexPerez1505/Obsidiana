<?php

use App\Http\Controllers\Services\ServiceController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;



Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-servicios/historial-servicios/aprobaciones', [ServiceController::class, 'pendingApprovals'])
        ->name('service-tracking.approvals');
    Route::get('/gestion-servicios/historial-servicios/mantenimiento', [ServiceController::class, 'approvedMaintenance'])
        ->name('service-tracking.maintenance');
    
    // Ruta simple de aprobación
    Route::post('/gestion-servicios/historial-servicios/seguimiento/{id}/aprobar', function ($id) {
        try {
            abort_unless(auth()->user()?->isAdmin(), 403);
            
            // Obtener el tracking primero
            $tracking = \App\Models\ServiceTracking::findOrFail($id);
            
            // Actualizar usando Eloquent
            $tracking->update([
                'status' => 'completado',
                'finished_at' => now(),
                'performed_by' => auth()->id(),
            ]);
            
            // Verificar que se guardó
            $tracking->refresh();
            
            if ($tracking->status !== 'completado') {
                return back()->with('error', 'No se pudo actualizar el status del tracking');
            }
            
            $service = $tracking->service;
            $service->load('currentStep');
            $currentOrder = $tracking->serviceStep->order;
            
            // Crear el siguiente paso si existe
            $nextStep = \App\Models\ServiceStep::where('service_type', $service->service_type)
                ->where('order', '>', $currentOrder)
                ->orderBy('order')
                ->first();
            
            if ($nextStep) {
                $newToken = $nextStep->requires_qr ? \Illuminate\Support\Str::random(32) : null;
                
                try {
                    $newTracking = \App\Models\ServiceTracking::create([
                        'service_id' => $service->id,
                        'service_step_id' => $nextStep->id,
                        'status' => 'pendiente',
                        'qr_token' => $newToken,
                        'qr_expires_at' => $nextStep->requires_qr ? now()->addDay() : null,
                        'started_at' => now(),
                    ]);
                    
                    if (!$newTracking) {
                        \Log::error('ServiceTracking::create returned null', [
                            'service_id' => $service->id,
                            'service_step_id' => $nextStep->id,
                        ]);
                        return back()->with('error', 'No se pudo crear el siguiente paso');
                    }
                } catch (\Exception $e) {
                    \Log::error('Error creating next tracking', [
                        'error' => $e->getMessage(),
                        'service_id' => $service->id,
                        'service_step_id' => $nextStep->id,
                    ]);
                    return back()->with('error', 'Error al crear el siguiente paso: ' . $e->getMessage());
                }
                
                $service->update([
                    'current_step_id' => $nextStep->id,
                    'qr_token' => $newToken,
                    'qr_expires_at' => $nextStep->requires_qr ? now()->addDay() : null,
                    'status' => 'en_progreso',
                ]);
            } else {
                $service->update([
                    'current_step_id' => null,
                    'qr_token' => null,
                    'qr_expires_at' => null,
                    'status' => 'entregado',
                    'finished_at' => now(),
                ]);
            }
            
            // Redirigir a la ruta correcta según el tipo de servicio
            if ($service->service_type === 'externo') {
                return redirect()->route('gestion.servicios.historial.externo.show', $service->id)
                    ->with('success', 'Paso aprobado correctamente.');
            }
            
            return redirect()->route('gestion.servicios.historial.show', $service->id)
                ->with('success', 'Paso aprobado correctamente.');
        } catch (\Exception $e) {
            \Log::error('Error en approveStep', ['error' => $e->getMessage(), 'tracking_id' => $id]);
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    })
    ->name('service-tracking.approve')
    ->where('id', '[0-9]+');
    
    Route::post('/gestion-servicios/historial-servicios/seguimiento/{id}/rechazar', function ($id) {
        try {
            abort_unless(auth()->user()?->isAdmin(), 403);
            
            // Obtener el tracking primero
            $tracking = \App\Models\ServiceTracking::findOrFail($id);
            
            // Actualizar usando Eloquent
            $tracking->update([
                'status' => 'rechazado',
                'finished_at' => now(),
                'performed_by' => auth()->id(),
            ]);
            
            // Verificar que se guardó
            $tracking->refresh();
            
            if ($tracking->status !== 'rechazado') {
                return back()->with('error', 'No se pudo actualizar el status del tracking');
            }
            
            $service = $tracking->service;
            
            // Redirigir a la ruta correcta según el tipo de servicio
            if ($service->service_type === 'externo') {
                return redirect()->route('gestion.servicios.historial.externo.show', $service->id)
                    ->with('error', 'Paso rechazado.');
            }
            
            return redirect()->route('gestion.servicios.historial.show', $service->id)
                ->with('error', 'Paso rechazado.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    })
    ->name('service-tracking.reject')
    ->where('id', '[0-9]+');
});

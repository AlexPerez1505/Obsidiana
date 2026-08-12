<?php

use App\Models\Service;
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/test/insert-services-for-approval', function () {
    // Solo permitir en desarrollo
    if (app()->environment('production')) {
        abort(403, 'Esta ruta no está disponible en producción');
    }

    try {
        // Obtener datos necesarios
        $adminUser = User::where('email', 'admin@example.com')->first();
        if (!$adminUser) {
            $adminUser = User::where('is_admin', true)->first();
        }
        if (!$adminUser) {
            $adminUser = User::first();
        }

        $customer = DB::table('clientes')->first();
        $technician = User::where('id', '!=', $adminUser->id)->first() ?? $adminUser;

        if (!$adminUser || !$customer) {
            return response()->json([
                'error' => 'No se encontraron usuarios o clientes en la base de datos',
                'admin_user' => $adminUser,
                'customer' => $customer,
            ], 400);
        }

        // Obtener pasos
        $autorizacionAdminStep = ServiceStep::where('slug', 'autorizacion_admin')->first();
        $validacionOsStep = ServiceStep::where('slug', 'validacion_os')->first();
        $salidaForaneaStep = ServiceStep::where('slug', 'salida_foranea')->first();
        $regresoForaneaStep = ServiceStep::where('slug', 'regreso_foranea')->first();

        if (!$autorizacionAdminStep || !$validacionOsStep) {
            return response()->json([
                'error' => 'No se encontraron los pasos de servicio requeridos',
                'autorizacion_admin' => $autorizacionAdminStep,
                'validacion_os' => $validacionOsStep,
            ], 400);
        }

        // ===== SERVICIO INTERNO =====
        $internalService = Service::create([
            'service_number' => 'OS-TEST-INT-' . date('YmdHis'),
            'customer_id' => $customer->id,
            'service_type' => 'interno',
            'internal_technician_id' => $technician->id,
            'external_technician_id' => null,
            'registered_by' => $adminUser->id,
            'current_step_id' => $autorizacionAdminStep->id,
            'qr_token' => 'test-qr-int-' . uniqid(),
            'qr_expires_at' => now()->addDay(),
            'signature' => null,
            'status' => 'en_progreso',
            'started_at' => now(),
            'finished_at' => null,
        ]);

        ServiceTracking::create([
            'service_id' => $internalService->id,
            'service_step_id' => $autorizacionAdminStep->id,
            'status' => 'pendiente',
            'performed_by' => null,
            'qr_token' => 'test-qr-int-' . uniqid(),
            'qr_expires_at' => now()->addDay(),
            'notes' => null,
            'evidence_1_path' => null,
            'evidence_2_path' => null,
            'evidence_3_path' => null,
            'video_path' => null,
            'signature' => null,
            'started_at' => now(),
            'finished_at' => null,
        ]);

        // ===== SERVICIO EXTERNO =====
        $externalService = Service::create([
            'service_number' => 'OS-TEST-EXT-' . date('YmdHis'),
            'customer_id' => $customer->id,
            'service_type' => 'externo',
            'internal_technician_id' => null,
            'external_technician_id' => $technician->id,
            'registered_by' => $adminUser->id,
            'current_step_id' => $validacionOsStep->id,
            'qr_token' => 'test-qr-ext-' . uniqid(),
            'qr_expires_at' => now()->addDay(),
            'signature' => null,
            'status' => 'en_progreso',
            'started_at' => now(),
            'finished_at' => null,
        ]);

        if ($salidaForaneaStep) {
            ServiceTracking::create([
                'service_id' => $externalService->id,
                'service_step_id' => $salidaForaneaStep->id,
                'status' => 'completado',
                'performed_by' => $technician->id,
                'qr_token' => null,
                'qr_expires_at' => null,
                'notes' => 'Equipo enviado a mantenimiento foráneo',
                'evidence_1_path' => null,
                'evidence_2_path' => null,
                'evidence_3_path' => null,
                'video_path' => null,
                'signature' => null,
                'started_at' => now()->subDays(2),
                'finished_at' => now()->subDay(),
            ]);
        }

        if ($regresoForaneaStep) {
            ServiceTracking::create([
                'service_id' => $externalService->id,
                'service_step_id' => $regresoForaneaStep->id,
                'status' => 'completado',
                'performed_by' => $technician->id,
                'qr_token' => null,
                'qr_expires_at' => null,
                'notes' => 'Equipo regresó de mantenimiento foráneo',
                'evidence_1_path' => null,
                'evidence_2_path' => null,
                'evidence_3_path' => null,
                'video_path' => null,
                'signature' => null,
                'started_at' => now()->subDay(),
                'finished_at' => now()->subHours(12),
            ]);
        }

        ServiceTracking::create([
            'service_id' => $externalService->id,
            'service_step_id' => $validacionOsStep->id,
            'status' => 'pendiente',
            'performed_by' => null,
            'qr_token' => 'test-qr-ext-val-' . uniqid(),
            'qr_expires_at' => now()->addDay(),
            'notes' => null,
            'evidence_1_path' => null,
            'evidence_2_path' => null,
            'evidence_3_path' => null,
            'video_path' => null,
            'signature' => null,
            'started_at' => now()->subHours(12),
            'finished_at' => null,
        ]);

        // Verificar datos
        $approvals = ServiceTracking::with(['service.customer', 'serviceStep'])
            ->whereIn('status', ['pendiente', 'rechazado'])
            ->whereHas('serviceStep', fn ($q) => $q->where('requires_approval', true))
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Servicios de prueba insertados correctamente',
            'internal_service' => [
                'id' => $internalService->id,
                'service_number' => $internalService->service_number,
                'service_type' => $internalService->service_type,
                'status' => $internalService->status,
            ],
            'external_service' => [
                'id' => $externalService->id,
                'service_number' => $externalService->service_number,
                'service_type' => $externalService->service_type,
                'status' => $externalService->status,
            ],
            'pending_approvals_count' => $approvals->count(),
            'pending_approvals' => $approvals->map(fn ($a) => [
                'service_number' => $a->service->service_number,
                'step_name' => $a->serviceStep->name,
                'status' => $a->status,
            ]),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

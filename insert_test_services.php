<?php
/**
 * Script para insertar servicios de prueba que requieren aprobación
 * 
 * Uso: php insert_test_services.php
 * 
 * Este script inserta:
 * - 1 servicio interno en paso "Autorización admin" (requiere aprobación)
 * - 1 servicio externo en paso "Validación OS" (requiere aprobación)
 */

// Cargar Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Service;
use App\Models\ServiceTracking;
use App\Models\ServiceStep;
use App\Models\User;
use Illuminate\Support\Facades\DB;

try {
    echo "Iniciando inserción de servicios de prueba...\n";

    // Obtener datos necesarios
    $adminUser = User::where('email', 'admin@example.com')->first();
    if (!$adminUser) {
        $adminUser = User::where('is_admin', true)->orWhere('role', 'admin')->first();
    }
    if (!$adminUser) {
        $adminUser = User::first();
    }

    $customer = DB::table('clientes')->first();
    $technician = User::where('id', '!=', $adminUser->id)->first() ?? $adminUser;

    if (!$adminUser || !$customer) {
        echo "❌ Error: No se encontraron usuarios o clientes en la base de datos.\n";
        exit(1);
    }

    echo "✓ Usuario admin: {$adminUser->email} (ID: {$adminUser->id})\n";
    echo "✓ Cliente: {$customer->nombre} (ID: {$customer->id})\n";
    echo "✓ Técnico: {$technician->email ?? 'N/A'} (ID: {$technician->id})\n\n";

    // Obtener pasos
    $autorizacionAdminStep = ServiceStep::where('slug', 'autorizacion_admin')->first();
    $validacionOsStep = ServiceStep::where('slug', 'validacion_os')->first();
    $salidaForaneaStep = ServiceStep::where('slug', 'salida_foranea')->first();
    $regresoForaneaStep = ServiceStep::where('slug', 'regreso_foranea')->first();

    if (!$autorizacionAdminStep || !$validacionOsStep) {
        echo "❌ Error: No se encontraron los pasos de servicio requeridos.\n";
        echo "   - Autorización admin: " . ($autorizacionAdminStep ? "✓" : "✗") . "\n";
        echo "   - Validación OS: " . ($validacionOsStep ? "✓" : "✗") . "\n";
        exit(1);
    }

    echo "✓ Pasos encontrados:\n";
    echo "  - Autorización admin (ID: {$autorizacionAdminStep->id})\n";
    echo "  - Validación OS (ID: {$validacionOsStep->id})\n";
    echo "  - Salida foránea (ID: {$salidaForaneaStep->id})\n";
    echo "  - Regreso foráneo (ID: {$regresoForaneaStep->id})\n\n";

    // ===== SERVICIO INTERNO =====
    echo "Creando servicio interno...\n";
    
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

    echo "✓ Servicio interno creado: {$internalService->service_number} (ID: {$internalService->id})\n";

    // Crear tracking para servicio interno
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

    echo "✓ Tracking creado para paso: Autorización admin (pendiente)\n\n";

    // ===== SERVICIO EXTERNO =====
    echo "Creando servicio externo...\n";
    
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

    echo "✓ Servicio externo creado: {$externalService->service_number} (ID: {$externalService->id})\n";

    // Crear trackings completados para pasos anteriores
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
        echo "✓ Tracking completado: Salida foránea\n";
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
        echo "✓ Tracking completado: Regreso foráneo\n";
    }

    // Crear tracking pendiente para validación OS
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

    echo "✓ Tracking creado para paso: Validación OS (pendiente)\n\n";

    // Verificar datos
    echo "=== RESUMEN ===\n";
    echo "Servicios de prueba creados:\n";
    
    $services = Service::whereIn('service_number', [
        $internalService->service_number,
        $externalService->service_number
    ])->with(['currentStep', 'customer'])->get();

    foreach ($services as $service) {
        echo "  • {$service->service_number} ({$service->service_type}) - {$service->customer->nombre}\n";
        echo "    Paso actual: {$service->currentStep->name}\n";
    }

    echo "\nAprobaciones pendientes:\n";
    $approvals = ServiceTracking::with(['service.customer', 'serviceStep'])
        ->whereIn('status', ['pendiente', 'rechazado'])
        ->whereHas('serviceStep', fn ($q) => $q->where('requires_approval', true))
        ->get();

    foreach ($approvals as $approval) {
        echo "  • {$approval->service->service_number} - {$approval->serviceStep->name} ({$approval->status})\n";
    }

    echo "\n✅ Servicios de prueba insertados correctamente.\n";
    echo "Ahora puedes acceder a la página de aprobaciones para verlos.\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

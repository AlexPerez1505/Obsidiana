<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InsertTestServicesForApproval extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:insert-test-services-for-approval';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserta servicios de prueba que requieren aprobación de administrador';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando inserción de servicios de prueba...');

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
                $this->error('Error: No se encontraron usuarios o clientes en la base de datos.');
                return 1;
            }

            $this->info("✓ Usuario admin: {$adminUser->email} (ID: {$adminUser->id})");
            $this->info("✓ Cliente: {$customer->nombre} (ID: {$customer->id})");
            $technicianEmail = $technician->email ?? 'N/A';
            $this->info("✓ Técnico: {$technicianEmail} (ID: {$technician->id})");
            $this->newLine();

            // Obtener pasos
            $autorizacionAdminStep = ServiceStep::where('slug', 'autorizacion_admin')->first();
            $validacionOsStep = ServiceStep::where('slug', 'validacion_os')->first();
            $salidaForaneaStep = ServiceStep::where('slug', 'salida_foranea')->first();
            $regresoForaneaStep = ServiceStep::where('slug', 'regreso_foranea')->first();

            if (!$autorizacionAdminStep || !$validacionOsStep) {
                $this->error('Error: No se encontraron los pasos de servicio requeridos.');
                $this->line('   - Autorización admin: ' . ($autorizacionAdminStep ? '✓' : '✗'));
                $this->line('   - Validación OS: ' . ($validacionOsStep ? '✓' : '✗'));
                return 1;
            }

            $this->info('✓ Pasos encontrados:');
            $this->line("  - Autorización admin (ID: {$autorizacionAdminStep->id})");
            $this->line("  - Validación OS (ID: {$validacionOsStep->id})");
            $this->line("  - Salida foránea (ID: {$salidaForaneaStep->id})");
            $this->line("  - Regreso foráneo (ID: {$regresoForaneaStep->id})");
            $this->newLine();

            // ===== SERVICIO INTERNO =====
            $this->info('Creando servicio interno...');
            
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

            $this->info("✓ Servicio interno creado: {$internalService->service_number} (ID: {$internalService->id})");

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

            $this->info('✓ Tracking creado para paso: Autorización admin (pendiente)');
            $this->newLine();

            // ===== SERVICIO EXTERNO =====
            $this->info('Creando servicio externo...');
            
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

            $this->info("✓ Servicio externo creado: {$externalService->service_number} (ID: {$externalService->id})");

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
                $this->info('✓ Tracking completado: Salida foránea');
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
                $this->info('✓ Tracking completado: Regreso foráneo');
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

            $this->info('✓ Tracking creado para paso: Validación OS (pendiente)');
            $this->newLine();

            // Verificar datos
            $this->info('=== RESUMEN ===');
            $this->info('Servicios de prueba creados:');
            
            $services = Service::whereIn('service_number', [
                $internalService->service_number,
                $externalService->service_number
            ])->with(['currentStep', 'customer'])->get();

            foreach ($services as $service) {
                $this->line("  • {$service->service_number} ({$service->service_type}) - {$service->customer->nombre}");
                $this->line("    Paso actual: {$service->currentStep->name}");
            }

            $this->newLine();
            $this->info('Aprobaciones pendientes:');
            $approvals = ServiceTracking::with(['service.customer', 'serviceStep'])
                ->whereIn('status', ['pendiente', 'rechazado'])
                ->whereHas('serviceStep', fn ($q) => $q->where('requires_approval', true))
                ->get();

            foreach ($approvals as $approval) {
                $this->line("  • {$approval->service->service_number} - {$approval->serviceStep->name} ({$approval->status})");
            }

            $this->newLine();
            $this->info('✅ Servicios de prueba insertados correctamente.');
            $this->info('Ahora puedes acceder a la página de aprobaciones para verlos.');

            return 0;

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}

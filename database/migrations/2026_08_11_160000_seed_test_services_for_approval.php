<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Obtener IDs necesarios
        $adminUser = DB::table('users')->where('email', 'admin@example.com')->first();
        $testUser = DB::table('users')->where('email', '!=', 'admin@example.com')->first();
        $customer = DB::table('clientes')->first();
        
        if (!$adminUser || !$customer || !$testUser) {
            return; // No hay datos base para crear servicios de prueba
        }

        // Obtener los pasos que requieren aprobación
        $autorizacionAdminStep = DB::table('service_steps')
            ->where('slug', 'autorizacion_admin')
            ->first();
        
        $validacionOsStep = DB::table('service_steps')
            ->where('slug', 'validacion_os')
            ->first();

        if (!$autorizacionAdminStep || !$validacionOsStep) {
            return; // Los pasos no existen
        }

        // Crear servicio interno en paso de aprobación
        $internalService = DB::table('services')->insertGetId([
            'service_number' => 'OS-TEST-INT-001',
            'customer_id' => $customer->id,
            'service_type' => 'interno',
            'internal_technician_id' => $testUser->id,
            'external_technician_id' => null,
            'registered_by' => $adminUser->id,
            'current_step_id' => $autorizacionAdminStep->id,
            'qr_token' => 'test-qr-int-' . uniqid(),
            'qr_expires_at' => now()->addDay(),
            'signature' => null,
            'status' => 'en_progreso',
            'started_at' => now(),
            'finished_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear tracking para el servicio interno
        DB::table('service_trackings')->insert([
            'service_id' => $internalService,
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear servicio externo en paso de aprobación (Validación OS)
        $externalService = DB::table('services')->insertGetId([
            'service_number' => 'OS-TEST-EXT-001',
            'customer_id' => $customer->id,
            'service_type' => 'externo',
            'internal_technician_id' => null,
            'external_technician_id' => $testUser->id,
            'registered_by' => $adminUser->id,
            'current_step_id' => $validacionOsStep->id,
            'qr_token' => 'test-qr-ext-' . uniqid(),
            'qr_expires_at' => now()->addDay(),
            'signature' => null,
            'status' => 'en_progreso',
            'started_at' => now(),
            'finished_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear trackings para los pasos anteriores del servicio externo (completados)
        $salidaForaneaStep = DB::table('service_steps')
            ->where('slug', 'salida_foranea')
            ->first();
        
        $regresoForaneaStep = DB::table('service_steps')
            ->where('slug', 'regreso_foranea')
            ->first();

        if ($salidaForaneaStep) {
            DB::table('service_trackings')->insert([
                'service_id' => $externalService,
                'service_step_id' => $salidaForaneaStep->id,
                'status' => 'completado',
                'performed_by' => $testUser->id,
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
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDay(),
            ]);
        }

        if ($regresoForaneaStep) {
            DB::table('service_trackings')->insert([
                'service_id' => $externalService,
                'service_step_id' => $regresoForaneaStep->id,
                'status' => 'completado',
                'performed_by' => $testUser->id,
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
                'created_at' => now()->subDay(),
                'updated_at' => now()->subHours(12),
            ]);
        }

        // Crear tracking para el paso de validación (pendiente de aprobación)
        DB::table('service_trackings')->insert([
            'service_id' => $externalService,
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
            'created_at' => now()->subHours(12),
            'updated_at' => now()->subHours(12),
        ]);
    }

    public function down(): void
    {
        // Eliminar los servicios de prueba
        $serviceIds = DB::table('services')
            ->whereIn('service_number', ['OS-TEST-INT-001', 'OS-TEST-EXT-001'])
            ->pluck('id');

        DB::table('service_trackings')
            ->whereIn('service_id', $serviceIds)
            ->delete();

        DB::table('services')
            ->whereIn('service_number', ['OS-TEST-INT-001', 'OS-TEST-EXT-001'])
            ->delete();
    }
};

<?php

namespace Database\Seeders;

use App\Models\ServiceStep;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceStepsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $steps = [
            ['name' => 'Llenado de información del equipo', 'slug' => 'llenado-equipo', 'purpose' => 'equipo', 'order' => 1, 'requires_qr' => false],
            ['name' => 'Generación de QR', 'slug' => 'generacion-qr', 'purpose' => 'generar_qr', 'order' => 2, 'requires_qr' => false],
            ['name' => 'Aprobación por autoridades de Nuevo Servicio', 'slug' => 'aprobacion-autoridades', 'purpose' => 'aprobacion', 'order' => 3, 'requires_qr' => false, 'requires_approval' => true],
            ['name' => 'Entrada del equipo a las instalaciones', 'slug' => 'entrada-equipo', 'purpose' => 'entrada', 'order' => 4, 'requires_qr' => true],
            ['name' => 'Salida hacia técnico externo', 'slug' => 'salida-tecnico-externo', 'purpose' => 'salida', 'order' => 5, 'requires_qr' => true],
            ['name' => 'Notificación de llegada del técnico externo', 'slug' => 'notificacion-llegada-tecnico', 'purpose' => 'llegada', 'order' => 6, 'requires_qr' => true],
            ['name' => 'Llenado del mantenimiento', 'slug' => 'llenado-mantenimiento', 'purpose' => 'mantenimiento', 'order' => 7, 'requires_qr' => true],
            ['name' => 'Notificación de finalizado por técnico externo', 'slug' => 'notificacion-finalizado', 'purpose' => 'finalizado', 'order' => 8, 'requires_qr' => true],
            ['name' => 'Notificación de envío de servicio', 'slug' => 'notificacion-envio-servicio', 'purpose' => 'envio_servicio', 'order' => 9, 'requires_qr' => true],
            ['name' => 'Regreso a las instalaciones', 'slug' => 'regreso-instalaciones', 'purpose' => 'regreso', 'order' => 10, 'requires_qr' => true],
            ['name' => 'Generación de OS', 'slug' => 'generacion-os', 'purpose' => 'os', 'order' => 11, 'requires_qr' => false],
            ['name' => 'Validación de OS', 'slug' => 'validacion-os', 'purpose' => 'validacion_os', 'order' => 12, 'requires_qr' => false, 'requires_approval' => true],
            ['name' => 'Marcar como enviado a cliente', 'slug' => 'marcar-enviado-cliente', 'purpose' => 'enviado_cliente', 'order' => 13, 'requires_qr' => false],
        ];

        foreach ($steps as $step) {
            ServiceStep::create($step + ['service_type' => 'externo', 'description' => null]);
        }
    }
}

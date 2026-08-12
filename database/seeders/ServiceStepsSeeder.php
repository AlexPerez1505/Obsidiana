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
            ['name' => 'Registro de servicio', 'slug' => 'registro-servicio', 'purpose' => 'registro', 'order' => 1, 'requires_qr' => false],
            ['name' => 'Llenado de información del equipo', 'slug' => 'llenado-equipo', 'purpose' => 'equipo', 'order' => 2, 'requires_qr' => false],
            ['name' => 'Generación de QR', 'slug' => 'generacion-qr', 'purpose' => 'generar_qr', 'order' => 3, 'requires_qr' => false],
            ['name' => 'Aprobación por autoridades', 'slug' => 'aprobacion-autoridades', 'purpose' => 'aprobacion', 'order' => 4, 'requires_qr' => false, 'requires_approval' => true],
            ['name' => 'Entrada del equipo', 'slug' => 'entrada-equipo', 'purpose' => 'entrada', 'order' => 5, 'requires_qr' => true],
            ['name' => 'Salida foránea', 'slug' => 'salida-foranea', 'purpose' => 'salida', 'order' => 6, 'requires_qr' => true],
            ['name' => 'Notificación de llegada del técnico', 'slug' => 'notificacion-llegada', 'purpose' => 'llegada', 'order' => 7, 'requires_qr' => true],
            ['name' => 'Llenado de mantenimiento', 'slug' => 'llenado-mantenimiento', 'purpose' => 'mantenimiento', 'order' => 8, 'requires_qr' => true],
            ['name' => 'Notificación de finalizado', 'slug' => 'notificacion-finalizado', 'purpose' => 'finalizado', 'order' => 9, 'requires_qr' => true],
            ['name' => 'Regreso foráneo', 'slug' => 'regreso-foraneo', 'purpose' => 'regreso', 'order' => 10, 'requires_qr' => true],
            ['name' => 'Generación de OS', 'slug' => 'generacion-os', 'purpose' => 'os', 'order' => 11, 'requires_qr' => false],
            ['name' => 'Validación de OS', 'slug' => 'validacion-os', 'purpose' => 'validacion_os', 'order' => 12, 'requires_qr' => false, 'requires_approval' => true],
            ['name' => 'Escaneo antes de salir con el cliente', 'slug' => 'escaneo-salida-cliente', 'purpose' => 'salida_cliente', 'order' => 13, 'requires_qr' => true],
            ['name' => 'Cliente feliz', 'slug' => 'cliente-feliz', 'purpose' => 'entrega', 'order' => 14, 'requires_qr' => false],
        ];

        foreach ($steps as $step) {
            ServiceStep::create($step + ['service_type' => 'externo', 'description' => null]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\ServiceStep;
use Illuminate\Database\Seeder;

class InternalServiceStepsSeeder extends Seeder
{
    public function run(): void
    {
        $steps = [
            ['name' => 'Llenado de información del equipo', 'slug' => 'interno-llenado-equipo', 'purpose' => 'equipo', 'order' => 1, 'requires_qr' => false],
            ['name' => 'Aprobación por el Admin', 'slug' => 'interno-aprobacion-admin', 'purpose' => 'aprobacion', 'order' => 2, 'requires_qr' => false, 'requires_approval' => true],
            ['name' => 'Entrada del equipo', 'slug' => 'interno-entrada-equipo', 'purpose' => 'entrada', 'order' => 3, 'requires_qr' => false],
            ['name' => 'Mantenimiento', 'slug' => 'interno-mantenimiento', 'purpose' => 'mantenimiento', 'order' => 4, 'requires_qr' => false],
            ['name' => 'Generación de OS por parte de Víctor', 'slug' => 'interno-generacion-os', 'purpose' => 'os', 'order' => 5, 'requires_qr' => false],
            ['name' => 'Validación de OS', 'slug' => 'interno-validacion-os', 'purpose' => 'validacion_os', 'order' => 6, 'requires_qr' => false, 'requires_approval' => true],
            ['name' => 'Registrar equipo como salida al cliente', 'slug' => 'interno-salida-cliente', 'purpose' => 'salida_cliente', 'order' => 7, 'requires_qr' => false],
        ];

        foreach ($steps as $step) {
            ServiceStep::firstOrCreate(
                ['slug' => $step['slug'], 'service_type' => 'interno'],
                $step + ['description' => null]
            );
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('service_steps')->whereIn('slug', [
            'autorizacion_admin',
            'entrega_cierre',
            'salida_foranea',
            'regreso_foranea',
            'validacion_os',
            'salida_cliente',
        ])->pluck('slug')->all();

        $steps = [
            [
                'service_type' => 'interno',
                'name' => 'Autorización admin',
                'slug' => 'autorizacion_admin',
                'purpose' => 'aprobacion',
                'order' => 1,
                'requires_qr' => true,
                'requires_approval' => true,
                'description' => 'Validación inicial del administrador.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_type' => 'interno',
                'name' => 'Entrega / Cierre',
                'slug' => 'entrega_cierre',
                'purpose' => 'cierre',
                'order' => 2,
                'requires_qr' => true,
                'requires_approval' => false,
                'description' => 'Entrega final del equipo al cliente.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_type' => 'externo',
                'name' => 'Salida a mantenimiento foráneo',
                'slug' => 'salida_foranea',
                'purpose' => 'salida',
                'order' => 1,
                'requires_qr' => true,
                'requires_approval' => false,
                'description' => 'Salida del equipo al taller externo.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_type' => 'externo',
                'name' => 'Regreso de mantenimiento foráneo',
                'slug' => 'regreso_foranea',
                'purpose' => 'regreso',
                'order' => 2,
                'requires_qr' => true,
                'requires_approval' => false,
                'description' => 'Regreso del equipo al taller principal.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_type' => 'externo',
                'name' => 'Validación OS',
                'slug' => 'validacion_os',
                'purpose' => 'validacion',
                'order' => 3,
                'requires_qr' => true,
                'requires_approval' => true,
                'description' => 'Validación de la orden de servicio por administrador.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_type' => 'externo',
                'name' => 'Salida para cliente',
                'slug' => 'salida_cliente',
                'purpose' => 'entrega',
                'order' => 4,
                'requires_qr' => true,
                'requires_approval' => false,
                'description' => 'Entrega final del equipo al cliente.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($steps as $step) {
            if (! in_array($step['slug'], $existing, true)) {
                DB::table('service_steps')->insert($step);
            }
        }
    }

    public function down(): void
    {
        DB::table('service_steps')->whereIn('slug', [
            'autorizacion_admin',
            'entrega_cierre',
            'salida_foranea',
            'regreso_foranea',
            'validacion_os',
            'salida_cliente',
        ])->delete();
    }
};

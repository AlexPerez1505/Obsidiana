<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $requesterId = DB::table('users')->where('is_admin', true)->value('id')
            ?? DB::table('users')->value('id');
        $now = now();

        $rows = [
            [
                'folio' => 'SOL-0007',
                'category' => 'Papeleria',
                'material_name' => 'Hojas carta',
                'quantity' => 4,
                'unit' => 'Paquete',
                'required_date' => now()->addDays(2)->toDateString(),
                'urgency' => 'Normal',
            ],
            [
                'folio' => 'SOL-0008',
                'category' => 'Seguridad e Higiene',
                'material_name' => 'Guantes nitrilo',
                'quantity' => 2,
                'unit' => 'Caja',
                'required_date' => now()->addDays(3)->toDateString(),
                'urgency' => 'Urgente',
            ],
        ];

        foreach ($rows as $row) {
            if (DB::table('material_requests')->where('folio', $row['folio'])->exists()) {
                continue;
            }

            DB::table('material_requests')->insert(array_merge($row, [
                'justification' => 'Solicitud inicial cargada desde el apartado de materiales.',
                'status' => 'pendiente',
                'requested_by' => $requesterId,
                'submitted_at' => $now,
                'metadata' => json_encode(['source' => 'admin_materiales_seed'], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('material_requests')
            ->where('metadata->source', 'admin_materiales_seed')
            ->delete();
    }
};

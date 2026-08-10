<?php

// Cargar Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Iniciando limpieza de servicios duplicados...\n";

// Obtener servicios agrupados por customer_id
$duplicates = DB::table('services')
    ->select('customer_id', DB::raw('COUNT(*) as count'), DB::raw('MAX(id) as latest_id'))
    ->groupBy('customer_id')
    ->having(DB::raw('COUNT(*)'), '>', 1)
    ->get();

if ($duplicates->isEmpty()) {
    echo "✓ No hay servicios duplicados.\n";
    exit(0);
}

$totalDeleted = 0;

foreach ($duplicates as $group) {
    // Obtener todos los servicios de este cliente excepto el más reciente
    $toDelete = DB::table('services')
        ->where('customer_id', $group->customer_id)
        ->where('id', '!=', $group->latest_id)
        ->pluck('id')
        ->toArray();

    if (!empty($toDelete)) {
        echo "Cliente ID {$group->customer_id}: Encontrados " . count($toDelete) . " duplicados\n";
        
        // Eliminar service_trackings asociados
        $trackingDeleted = DB::table('service_trackings')
            ->whereIn('service_id', $toDelete)
            ->delete();
        echo "  - Eliminados $trackingDeleted service_trackings\n";

        // Eliminar service_equipment asociados
        $equipmentDeleted = DB::table('service_equipment')
            ->whereIn('service_id', $toDelete)
            ->delete();
        echo "  - Eliminados $equipmentDeleted service_equipment\n";

        // Eliminar los servicios
        $deleted = DB::table('services')
            ->whereIn('id', $toDelete)
            ->delete();

        $totalDeleted += $deleted;
        echo "  - Eliminados $deleted servicios\n";
    }
}

echo "\n✓ Limpieza completada. Total de servicios eliminados: $totalDeleted\n";

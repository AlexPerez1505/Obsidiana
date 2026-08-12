<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateServices extends Command
{
    protected $signature = 'services:clean-duplicates';
    protected $description = 'Elimina servicios duplicados, manteniendo solo el más reciente por cliente';

    public function handle()
    {
        $this->info('Iniciando limpieza de servicios duplicados...');

        // Obtener servicios agrupados por customer_id
        $duplicates = DB::table('services')
            ->select('customer_id', DB::raw('COUNT(*) as count'), DB::raw('MAX(id) as latest_id'))
            ->groupBy('customer_id')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('✓ No hay servicios duplicados.');
            return;
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
                // Eliminar service_trackings asociados
                DB::table('service_trackings')
                    ->whereIn('service_id', $toDelete)
                    ->delete();

                // Eliminar service_equipment asociados
                DB::table('service_equipment')
                    ->whereIn('service_id', $toDelete)
                    ->delete();

                // Eliminar los servicios
                $deleted = DB::table('services')
                    ->whereIn('id', $toDelete)
                    ->delete();

                $totalDeleted += $deleted;

                $this->line("  • Cliente ID {$group->customer_id}: Eliminados {$deleted} servicios duplicados");
            }
        }

        $this->info("✓ Limpieza completada. Total de servicios eliminados: {$totalDeleted}");
    }
}

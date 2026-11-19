<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Agenda\Avisos;
use App\Services\Agenda\Calendario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Resumen diario para cada usuario: lo de hoy, lo vencido y lo que viene.
 * Uno por usuario y día (tabla agenda_resumenes): reintentar no lo repite.
 * Quien no tiene nada pendiente no recibe nada.
 */
class AgendaResumen extends Command
{
    protected $signature = 'agenda:resumen {--usuario= : Solo para este usuario (id)}';

    protected $description = 'Manda el resumen diario de la agenda';

    public function handle(Calendario $calendario, Avisos $avisos): int
    {
        $hoy = $calendario->hoy()->toDateString();
        $enviados = 0;

        $usuarios = User::query()->where('status', User::STATUS_APPROVED)
            ->when($this->option('usuario'), fn ($q, $id) => $q->whereKey($id))->get();

        foreach ($usuarios as $u) {
            if (! $u->can('agenda.ver') || ! Avisos::preferencias($u)['resumen']) {
                continue;
            }

            $p = $calendario->paneles($u);
            $deHoy = collect($p['hoy'])->whereIn('estado', ['pendiente', 'en_proceso', 'programado']);
            $vencidos = count($p['vencidos']);
            $eventos = collect($p['proximos'])->where('clase', 'evento')->take(3);

            if ($deHoy->isEmpty() && $vencidos === 0 && $eventos->isEmpty()) {
                continue;
            }

            // Se aparta el día antes de mandar: si ya existía, no se repite.
            if (DB::table('agenda_resumenes')->insertOrIgnore(['user_id' => $u->id, 'fecha' => $hoy, 'created_at' => now(), 'updated_at' => now()]) === 0) {
                continue;
            }

            $partes = [];
            $partes[] = $deHoy->count().' para hoy'.($deHoy->isNotEmpty() ? ' ('.$deHoy->take(3)->pluck('titulo')->implode('; ').($deHoy->count() > 3 ? '…' : '').')' : '');
            if ($vencidos) {
                $partes[] = $vencidos.' vencido(s)';
            }
            if ($eventos->isNotEmpty()) {
                $partes[] = 'Próximos: '.$eventos->map(fn ($e) => $e['titulo'].' ('.\Carbon\Carbon::parse($e['fecha'])->format('d/m').')')->implode('; ');
            }

            $enviados += $avisos->enviar($u, 'resumen', 'Tu agenda de hoy', implode(' · ', $partes), route('agenda.index'), 'resumen-'.$hoy);
        }

        $this->info("Resúmenes enviados: {$enviados}");

        return self::SUCCESS;
    }
}

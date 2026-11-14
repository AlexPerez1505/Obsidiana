<?php

namespace App\Console\Commands;

use App\Models\AgendaActividad;
use App\Models\AgendaEvent;
use App\Models\ClienteSeguimiento;
use App\Services\Agenda\Historial;
use App\Services\Agenda\Recordatorios;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Incorpora lo que ya existía a la agenda nueva. Se puede correr las veces
 * que sea: no duplica nada.
 *
 *  1. Seguimientos de Clientes: ya salen en la agenda por sí solos (es la
 *     misma tabla). Aquí solo se les programan sus recordatorios y se deja
 *     constancia en su historial la primera vez.
 *  2. Eventos de la agenda anterior (agenda_events): se copian como
 *     actividades (legado_id evita repetirlos). Los originales no se tocan.
 */
class AgendaSincronizar extends Command
{
    protected $signature = 'agenda:sincronizar {--simular : Solo cuenta lo que haría, sin guardar}';

    protected $description = 'Incorpora seguimientos y eventos existentes a la agenda sin duplicarlos';

    private const TIPOS_VIEJOS = [
        'training' => 'capacitacion',
        'delivery' => 'entrega',
        'install' => 'entrega',
        'meeting' => 'reunion',
        'maintenance' => 'servicio',
    ];

    public function handle(Recordatorios $recordatorios): int
    {
        $simular = (bool) $this->option('simular');

        $abiertos = ClienteSeguimiento::pendientes()->get();
        $nuevosHist = 0;
        foreach ($abiertos as $s) {
            $ya = $s->historialAgenda()->where('accion', 'importado')->exists();
            if ($simular) {
                $nuevosHist += $ya ? 0 : 1;
                continue;
            }
            DB::transaction(function () use ($s, $ya, $recordatorios, &$nuevosHist) {
                if (! $ya) {
                    Historial::registrar($s, 'importado', null, null, ['fecha' => $s->fecha?->toDateString(), 'user_id' => (int) $s->user_id]);
                    $nuevosHist++;
                }
                $recordatorios->programar($s);
            });
        }
        $this->info("Seguimientos abiertos: {$abiertos->count()} (incorporados por primera vez: {$nuevosHist}); recordatorios al día.");

        $importados = 0;
        $viejos = AgendaEvent::query()->whereNotIn('id', AgendaActividad::withTrashed()->whereNotNull('legado_id')->pluck('legado_id'))->get();
        foreach ($viejos as $e) {
            if ($simular) {
                $importados++;
                continue;
            }
            DB::transaction(function () use ($e, $recordatorios, &$importados) {
                $tipo = self::TIPOS_VIEJOS[$e->event_type] ?? 'otro';
                $participantes = is_array($e->participants) ? implode(', ', $e->participants) : (string) $e->participants;
                $a = AgendaActividad::create([
                    'titulo' => $e->title,
                    'descripcion' => trim(collect([$e->notes, $participantes ? 'Participantes: '.$participantes : null])->filter()->implode("\n")) ?: null,
                    'tipo' => $tipo,
                    'clase' => AgendaActividad::claseDe($tipo),
                    'estado' => $e->status === 'cancelled' ? 'cancelado' : AgendaActividad::estadoInicial($tipo),
                    'fecha_inicio' => $e->start_date,
                    'fecha_fin' => $e->end_date ?? $e->start_date,
                    'hora_inicio' => $e->start_time,
                    'todo_el_dia' => ! $e->start_time,
                    'responsable_id' => $e->created_by,
                    'visibilidad' => 'empresa',
                    'ocupa_horario' => false,
                    'legado_id' => $e->id,
                    'created_by' => $e->created_by,
                    'updated_by' => $e->updated_by,
                ]);
                Historial::registrar($a, 'importado', null, null, ['agenda_event_id' => $e->id]);
                $recordatorios->programar($a);
                $importados++;
            });
        }
        $this->info(($simular ? 'Se importarían' : 'Eventos de la agenda anterior importados').": {$importados}");

        return self::SUCCESS;
    }
}

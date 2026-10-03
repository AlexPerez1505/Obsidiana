<?php

namespace App\Support;

use App\Models\CompanyEvent;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Lo que la empresa marca en el calendario desde Asistencia (días festivos y
 * eventos de equipo) con la misma forma que usa la Agenda, para pintarlos ahí
 * sin duplicar nada: se administran en un solo lugar.
 */
class CalendarioEmpresa
{
    public const COLOR_FESTIVO = '#a855f7';
    public const COLOR_EQUIPO = '#14b8a6';

    /**
     * @return array<int, array<string, mixed>>  entradas de solo lectura
     */
    public static function eventos(Carbon $desde, Carbon $hasta, ?User $usuario = null): array
    {
        $desde = $desde->copy()->startOfDay();
        $hasta = $hasta->copy()->startOfDay();
        $lista = [];

        foreach (Holiday::whereDate('date', '>=', $desde)->whereDate('date', '<=', $hasta)->orderBy('date')->get() as $h) {
            $lista[] = self::entrada(
                id: 'festivo-'.$h->id,
                titulo: $h->name,
                inicio: $h->date,
                fin: $h->date,
                estado: 'festivo',
                tipo: 'Día festivo',
                motivo: 'Día festivo: no se toma asistencia ni se generan faltas.'
            );
        }

        // Un evento "solo para algunas personas" lo ve quien participa o quien administra asistencia
        $ve = fn (CompanyEvent $e) => $e->scope === 'todos'
            || ($usuario && ($usuario->isAdmin() || $usuario->can('asistencia.ver') || $e->aplicaA($usuario->id)));

        $eventos = CompanyEvent::whereDate('date_from', '<=', $hasta)->whereDate('date_to', '>=', $desde)->orderBy('date_from')->get();

        foreach ($eventos->filter($ve) as $e) {
            $motivo = match ($e->type) {
                'descanso' => 'Descanso del equipo: sin checada ni falta.',
                'sin_checar' => 'Día sin checar: cuenta como asistencia.',
                default => 'Horario especial '.substr((string) $e->time_in, 0, 5).'–'.substr((string) $e->time_out, 0, 5).'.',
            };

            $lista[] = self::entrada(
                id: 'empresa-'.$e->id,
                titulo: $e->name,
                inicio: $e->date_from,
                fin: $e->date_to,
                estado: 'equipo',
                tipo: $e->tipoLabel(),
                motivo: $motivo.($e->note ? ' '.$e->note : ''),
                participantes: $e->scope === 'todos' ? 'Todo el equipo' : ''
            );
        }

        return $lista;
    }

    private static function entrada(string $id, string $titulo, Carbon $inicio, Carbon $fin, string $estado, string $tipo, string $motivo, string $participantes = ''): array
    {
        $esFestivo = $estado === 'festivo';

        return [
            'id' => $id,
            'start_date' => $inicio->toDateString(),
            'end_date' => $fin->toDateString(),
            'time_value' => '07:00',   // arriba en las vistas por hora
            'time' => '',
            'time_end' => '',
            'title' => $titulo,
            'type' => $esFestivo ? 'festivo' : 'empresa',
            'type_label' => $tipo,
            'status' => $estado,
            'status_label' => $esFestivo ? 'Día festivo' : 'Evento de la empresa',
            'status_color' => $esFestivo ? self::COLOR_FESTIVO : self::COLOR_EQUIPO,
            'reason' => $motivo,
            'notes' => '',
            'participants' => $participantes,
            'creator' => 'Asistencia (RH)',
            'creator_label' => 'Origen: Asistencia (RH)',
            'can_delete' => false,
            'delete_url' => '',
            'readonly' => true,
        ];
    }
}

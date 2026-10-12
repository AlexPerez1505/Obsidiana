<?php

namespace App\Console\Commands;

use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Cierra días de asistencia: a quien debía checar y no lo hizo, sin justificante,
 * se le pone falta (por justificar). Por defecto cierra ayer. Se puede repetir
 * sobre un día sin riesgo: recalcula, no duplica.
 */
class AsistenciaCerrarDia extends Command
{
    protected $signature = 'asistencia:cerrar {fecha? : AAAA-MM-DD (por defecto, ayer)} {--desde= : Cierra todos los días desde esta fecha hasta ayer}';

    protected $description = 'Cierra el día de asistencia y genera las faltas automáticas';

    public function handle(ServicioDeAsistencia $asistencia): int
    {
        $hoy = ServicioDeAsistencia::diaLocal(now());

        $desde = $this->option('desde')
            ? Carbon::parse($this->option('desde'), ServicioDeAsistencia::tz())->startOfDay()
            : ($this->argument('fecha') ? Carbon::parse($this->argument('fecha'), ServicioDeAsistencia::tz())->startOfDay() : $hoy->copy()->subDay());
        $hasta = $this->option('desde') ? $hoy->copy()->subDay() : $desde->copy();

        if ($hasta->greaterThanOrEqualTo($hoy)) {
            $this->error('Solo se pueden cerrar días anteriores a hoy.');

            return self::FAILURE;
        }

        for ($d = $desde->copy(); $d->lessThanOrEqualTo($hasta); $d->addDay()) {
            $r = $asistencia->cerrarDia($d);
            $asistencia->marcarCerrado($d);
            $this->info("{$d->toDateString()}: {$r['faltas']} falta(s), {$r['justificadas']} justificada(s) ({$r['revisados']} revisados)");
        }

        return self::SUCCESS;
    }
}

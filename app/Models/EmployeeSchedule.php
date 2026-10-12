<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSchedule extends Model
{
    public const DIAS = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];

    protected $fillable = ['user_id', 'weekdays', 'day_hours', 'time_in', 'time_out', 'tolerance_minutes', 'active'];

    protected function casts(): array
    {
        return ['weekdays' => 'array', 'day_hours' => 'array', 'active' => 'boolean', 'tolerance_minutes' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** ¿Le toca trabajar ese día de la semana? (ISO: 1 lunes … 7 domingo) */
    public function trabajaEl(\DateTimeInterface $fecha): bool
    {
        return in_array((int) $fecha->format('N'), array_map('intval', $this->weekdays ?? []), true);
    }

    /**
     * Horas de un día de la semana (1–7). Si ese día tiene horario propio se
     * usa; si no, el horario general.
     *
     * @return array{in: string, out: string}  formato HH:MM
     */
    public function horasDelDia(int $diaSemana): array
    {
        $propio = $this->day_hours[(string) $diaSemana] ?? $this->day_hours[$diaSemana] ?? null;

        return [
            'in' => substr($propio['in'] ?? $this->time_in, 0, 5),
            'out' => substr($propio['out'] ?? $this->time_out, 0, 5),
        ];
    }

    public function horasDe(\DateTimeInterface $fecha): array
    {
        return $this->horasDelDia((int) $fecha->format('N'));
    }

    public function entradaHHMM(): string
    {
        return substr($this->time_in, 0, 5);
    }

    public function salidaHHMM(): string
    {
        return substr($this->time_out, 0, 5);
    }

    public function diasTexto(): string
    {
        return collect($this->weekdays ?? [])->sort()->map(fn ($d) => self::DIAS[$d] ?? $d)->implode(', ');
    }

    /** "Lun–Vie 09:00–18:00 · Sáb 09:00–10:00": días con el mismo horario se agrupan. */
    public function resumen(): string
    {
        $dias = collect($this->weekdays ?? [])->map(fn ($d) => (int) $d)->sort()->values();

        if ($dias->isEmpty()) {
            return 'Sin días laborales';
        }

        $grupos = [];
        foreach ($dias as $d) {
            $h = $this->horasDelDia($d);
            $clave = $h['in'].'–'.$h['out'];
            $grupos[$clave][] = $d;
        }

        return collect($grupos)->map(function ($ds, $horas) {
            // Rangos consecutivos: [1,2,3,4,5] → Lun–Vie
            $partes = [];
            $ini = $prev = null;
            foreach ($ds as $d) {
                if ($ini === null) {
                    $ini = $prev = $d;
                } elseif ($d === $prev + 1) {
                    $prev = $d;
                } else {
                    $partes[] = $this->rango($ini, $prev);
                    $ini = $prev = $d;
                }
            }
            $partes[] = $this->rango($ini, $prev);

            return implode(', ', $partes).' '.$horas;
        })->implode(' · ');
    }

    private function rango(int $a, int $b): string
    {
        return $a === $b ? self::DIAS[$a] : (self::DIAS[$a].($b === $a + 1 ? ', ' : '–').self::DIAS[$b]);
    }
}

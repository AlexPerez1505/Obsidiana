<?php

namespace App\Services\Asistencia;

use App\Models\AsistenciaJustificacion;
use App\Models\AttendancePunch;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * El día de cada persona en palabras: "Entró 9:02 · salió a comer 14:00 ·
 * regresó 14:32 · salió 18:01", "Entró 12:58 y no registró salida" o "No
 * registró entrada ni salida".
 *
 * La pantalla de Checadas antes mostraba cada toque del lector suelto; aquí
 * se agrupan por persona, se comparan con su horario de ese día y se dice
 * qué falta y si ya se le pidió justificarlo.
 */
class ResumenDeChecadas
{
    /**
     * Estados de un día, en el orden en que importan (los problemas primero).
     * tono: el color del aviso en pantalla.
     */
    public const ESTADOS = [
        'sin_registro' => ['texto' => 'Sin registro', 'tono' => 'bad'],
        'sin_salida' => ['texto' => 'Sin salida', 'tono' => 'bad'],
        'por_llegar' => ['texto' => 'No ha llegado', 'tono' => 'warn'],
        'trabajando' => ['texto' => 'Trabajando', 'tono' => 'info'],
        'completo' => ['texto' => 'Completo', 'tono' => 'ok'],
        'justificado' => ['texto' => 'Justificado', 'tono' => 'info'],
        'no_laboral' => ['texto' => 'No le tocaba', 'tono' => ''],
    ];

    /** Los estados que se le pueden pedir justificar. */
    public const PROBLEMAS = ['sin_registro', 'sin_salida'];

    public function __construct(private readonly ServicioDeAsistencia $asistencia) {}

    /**
     * @return Collection<int, array>  una fila por persona, problemas primero
     */
    public function del(Carbon $dia, ?int $soloEmpleado = null): Collection
    {
        $dia = $dia->copy()->startOfDay();
        $tz = ServicioDeAsistencia::tz();
        $ahora = now()->setTimezone($tz);
        $hoy = ServicioDeAsistencia::diaLocal(now());

        $empleados = $this->asistencia->empleados()
            ->when($soloEmpleado, fn ($c) => $c->where('id', $soloEmpleado));

        $checadas = AttendancePunch::with(['device', 'anuladaPor', 'creadaPor'])
            ->whereDate('work_date', $dia)
            ->when($soloEmpleado, fn ($q) => $q->where('user_id', $soloEmpleado))
            ->orderBy('punched_at')
            ->get()
            ->groupBy('user_id');

        // Quien checó pero ya no está en la lista de empleados (se le quitó el
        // horario después) también aparece: su checada existe.
        $sueltos = User::whereIn('id', $checadas->keys()->diff($empleados->pluck('id')))->get();

        $justificaciones = AsistenciaJustificacion::whereDate('work_date', $dia)
            ->when($soloEmpleado, fn ($q) => $q->where('user_id', $soloEmpleado))
            ->get()->keyBy('user_id');

        $orden = array_flip(array_keys(self::ESTADOS));

        return $empleados->concat($sueltos)
            ->map(fn (User $u) => $this->persona($u, $dia, $checadas->get($u->id, collect()), $justificaciones->get($u->id), $ahora, $hoy))
            ->sortBy(fn (array $f) => sprintf('%02d %s', $orden[$f['estado']] ?? 99, $f['user']->name))
            ->values();
    }

    /** Conteos para los números de arriba. */
    public function conteos(Collection $filas): array
    {
        return [
            'esperados' => $filas->where('esperado', true)->count(),
            'completos' => $filas->where('estado', 'completo')->count(),
            'en_curso' => $filas->whereIn('estado', ['trabajando', 'por_llegar'])->count(),
            'sin_salida' => $filas->where('estado', 'sin_salida')->count(),
            'sin_registro' => $filas->where('estado', 'sin_registro')->count(),
            'retardos' => $filas->filter(fn ($f) => $f['retardo'] > 0)->count(),
            'tempranos' => $filas->filter(fn ($f) => $f['temprano'] > 0)->count(),
            'por_revisar' => $filas->filter(fn ($f) => $f['justificacion']?->estado === AsistenciaJustificacion::RESPONDIDA)->count(),
        ];
    }

    private function persona(User $u, Carbon $dia, Collection $todas, ?AsistenciaJustificacion $just, Carbon $ahora, Carbon $hoy): array
    {
        $validas = $todas->whereNull('voided_at')->values();
        $horario = $this->asistencia->horarioDelDia($u, $dia);
        $festivo = $this->asistencia->esFestivo($dia);
        $esperado = $this->asistencia->esperado($u, $dia);
        $modalidad = $this->asistencia->modalidadDe($u, $dia);
        $esHoy = $dia->equalTo($hoy);
        $futuro = $dia->greaterThan($hoy);

        $h = fn (AttendancePunch $p) => $p->horaLocal()->format('G:i');

        // Tramos entrada → salida (la 1a y 3a son entrada/regreso; la 2a y 4a, salidas)
        $tramos = [];
        foreach ($validas->chunk(2) as $par) {
            $par = $par->values();
            $tramos[] = ['entra' => $h($par[0]), 'sale' => isset($par[1]) ? $h($par[1]) : null];
        }

        $minutos = 0;
        for ($i = 0; $i + 1 < $validas->count(); $i += 2) {
            $minutos += (int) floor($validas[$i]->punched_at->diffInSeconds($validas[$i + 1]->punched_at) / 60);
        }

        $limiteEntrada = $horario ? Carbon::parse($dia->toDateString().' '.$horario['in'], $ahora->getTimezone())->addMinutes($horario['tolerance']) : null;
        $horaSalida = $horario ? Carbon::parse($dia->toDateString().' '.$horario['out'], $ahora->getTimezone()) : null;

        $retardo = 0;
        if ($validas->isNotEmpty() && $limiteEntrada && ! $festivo && ! in_array($modalidad['type'] ?? null, ['descanso_equipo', 'evento'], true)) {
            $entrada = $validas->first()->horaLocal()->copy()->startOfMinute();
            if ($entrada->greaterThan($limiteEntrada)) {
                $retardo = max(1, (int) $limiteEntrada->diffInMinutes($entrada));
            }
        }

        // Se fue antes de su hora (sin contar el día de hoy, que sigue en curso)
        $temprano = 0;
        if ($validas->count() >= 2 && $validas->count() % 2 === 0 && $horaSalida && ! $festivo) {
            $ultima = $validas->last()->horaLocal()->copy()->startOfMinute();
            if ($ultima->lessThan($horaSalida->copy()->subMinutes(10))) {
                $temprano = (int) $ultima->diffInMinutes($horaSalida);
            }
        }

        // ¿En qué quedó el día?
        if ($validas->isEmpty()) {
            if ($modalidad) {
                [$estado, $frase] = ['justificado', $modalidad['label']];
            } elseif (! $esperado) {
                [$estado, $frase] = ['no_laboral', $festivo ? 'Día festivo' : 'No le tocaba trabajar'];
            } elseif ($futuro || ($esHoy && $limiteEntrada && $ahora->lessThan($limiteEntrada))) {
                [$estado, $frase] = ['por_llegar', 'Entra a las '.$horario['in']];
            } elseif ($esHoy && $horaSalida && $ahora->lessThan($horaSalida)) {
                [$estado, $frase] = ['por_llegar', 'No ha registrado entrada (debía entrar a las '.$horario['in'].')'];
            } else {
                [$estado, $frase] = ['sin_registro', 'No registró entrada ni salida'];
            }
        } else {
            $frase = $this->frase($tramos);
            $abierto = $validas->count() % 2 === 1;

            if (! $abierto) {
                $estado = 'completo';
            } elseif ($esHoy && (! $horaSalida || $ahora->lessThan($horaSalida->copy()->addHours(2)))) {
                $estado = 'trabajando';
            } else {
                $estado = 'sin_salida';
                $frase .= ' y no registró salida';
            }
        }

        // Una justificación aceptada cambia cómo se lee el día.
        if ($just?->estado === AsistenciaJustificacion::ACEPTADA && in_array($estado, self::PROBLEMAS, true)) {
            $estado = 'justificado';
        }

        return [
            'user' => $u,
            'estado' => $estado,
            'frase' => $frase,
            'tramos' => $tramos,
            'minutos' => $minutos,
            'retardo' => $retardo,
            'temprano' => $temprano,
            'horario' => $horario,
            'esperado' => $esperado,
            'modalidad' => $modalidad,
            'checadas' => $todas,
            'justificacion' => $just,
            'puede_pedir' => in_array($estado, self::PROBLEMAS, true) && (! $just || $just->estado === AsistenciaJustificacion::RECHAZADA),
        ];
    }

    /** "Entró 9:02 · salió 14:00 · regresó 14:32 · salió 18:01" */
    private function frase(array $tramos): string
    {
        $partes = [];

        foreach ($tramos as $i => $t) {
            $partes[] = ($i === 0 ? 'Entró ' : 'regresó ').$t['entra'];
            if ($t['sale']) {
                // Si después hubo regreso, esa salida fue un descanso (comida)
                $partes[] = (isset($tramos[$i + 1]) ? 'salió a comer ' : 'salió ').$t['sale'];
            }
        }

        return implode(' · ', $partes);
    }
}

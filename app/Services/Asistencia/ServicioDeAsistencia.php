<?php

namespace App\Services\Asistencia;

use App\Models\AttendanceModality;
use App\Models\CompanyEvent;
use App\Models\AttendancePunch;
use App\Models\BiometricDevice;
use App\Models\EmployeeAbsence;
use App\Models\EmployeeAttendance;
use App\Models\EmployeePermissionRequest;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeVacationRequest;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Convierte las checadas del lector en asistencia.
 *
 * Todo toque se guarda tal cual en attendance_punches; aquí se interpreta:
 * la primera checada del día es la entrada, la última la salida y las de en
 * medio son movimientos (salida a comer, regreso, comisión…). El resumen del
 * día se escribe en employee_attendances y se puede recalcular las veces que
 * haga falta sin tocar la evidencia original.
 */
class ServicioDeAsistencia
{
    /** Marca que identifica las faltas creadas por el sistema. */
    public const FALTA_AUTO = 'Falta automática: sin checada ni justificante';

    private ?Carbon $inicioMemo = null;

    public static function tz(): string
    {
        return config('asistencia.timezone', 'America/Mexico_City');
    }

    /** Fecha local de un instante. */
    public static function diaLocal(Carbon $instante): Carbon
    {
        return $instante->copy()->setTimezone(self::tz())->startOfDay();
    }

    // ------------------------------------------------------------------
    // Checadas
    // ------------------------------------------------------------------

    /**
     * Registra un toque del lector.
     *
     * @param  array{uuid?: ?string, punched_at?: ?string, confidence?: ?int, method?: ?string, note?: ?string, created_by?: ?int}  $d
     * @return array{ok: bool, duplicada: bool, punch: ?AttendancePunch, resultado: array}
     */
    public function registrarChecada(User $user, ?BiometricDevice $device, array $d = []): array
    {
        $uuid = $d['uuid'] ?? (string) Str::uuid();

        // El agente puede reenviar (sin red, reintento): misma llave, mismo resultado
        if ($previa = AttendancePunch::where('uuid', $uuid)->first()) {
            return ['ok' => true, 'duplicada' => true, 'punch' => $previa, 'resultado' => $this->resultado($previa, true)];
        }

        $ahora = now();
        $cuando = ! empty($d['punched_at']) ? Carbon::parse($d['punched_at'])->utc() : $ahora->copy();

        // Un reloj mal puesto no puede adelantar checadas al futuro
        if ($cuando->greaterThan($ahora)) {
            $cuando = $ahora->copy();
        }

        // Dedo puesto dos veces seguidas
        $rebote = (int) config('asistencia.rebote_segundos', 60);
        $reciente = AttendancePunch::validas()->where('user_id', $user->id)
            ->where('punched_at', '>', $cuando->copy()->subSeconds($rebote))
            ->where('punched_at', '<=', $cuando->copy()->addSeconds($rebote))
            ->latest('punched_at')->first();

        if ($reciente && ($d['method'] ?? 'fingerprint') === 'fingerprint') {
            return ['ok' => true, 'duplicada' => true, 'punch' => $reciente, 'resultado' => $this->resultado($reciente, true)];
        }

        $dia = self::diaLocal($cuando);

        $punch = DB::transaction(function () use ($user, $device, $d, $uuid, $cuando, $dia) {
            $n = AttendancePunch::validas()->where('user_id', $user->id)->whereDate('work_date', $dia)->count() + 1;

            return AttendancePunch::create([
                'uuid' => $uuid,
                'user_id' => $user->id,
                'device_id' => $device?->id,
                'punched_at' => $cuando,
                'method' => $d['method'] ?? 'fingerprint',
                'confidence' => $d['confidence'] ?? null,
                'kind' => $n === 1 ? 'entrada' : ($n % 2 === 0 ? 'salida' : 'regreso'),
                'seq' => $n,
                'work_date' => $dia->toDateString(),
                'note' => $d['note'] ?? null,
                'created_by' => $d['created_by'] ?? null,
            ]);
        });

        $this->inicioMemo = null;
        $this->recalcularDia($user, $dia);

        return ['ok' => true, 'duplicada' => false, 'punch' => $punch, 'resultado' => $this->resultado($punch, false)];
    }

    public static function reglasChecadaManual(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            'motivo' => ['required', 'string', 'max:255'],
        ];
    }

    public static function mensajesChecadaManual(): array
    {
        return ['motivo.required' => 'Escribe el motivo de la checada manual: queda como evidencia.'];
    }

    /**
     * Checada manual (olvidó checar, falló el lector): queda marcada como
     * manual, con su motivo y quién la hizo. La fecha y la hora son locales.
     */
    public function registrarManual(User $user, string $fecha, string $hora, string $motivo, int $creadoPor): AttendancePunch
    {
        $cuando = Carbon::parse($fecha.' '.$hora, self::tz())->utc();

        if ($cuando->isFuture()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['hora' => 'No se puede registrar una checada en el futuro.']);
        }

        $r = $this->registrarChecada($user, null, [
            'punched_at' => $cuando->toDateTimeString(),
            'method' => 'manual',
            'note' => $motivo,
            'created_by' => $creadoPor,
        ]);

        // Una checada hecha a mano fuera de orden cambia entrada/salida del día
        $this->renumerar($user->id, Carbon::parse($fecha));
        $this->recalcularDia($user, Carbon::parse($fecha));

        return $r['punch']->refresh();
    }

    /** Lo que se le muestra al empleado (y devuelve la API al agente). */
    public function resultado(AttendancePunch $punch, bool $duplicada): array
    {
        $punch->loadMissing('user');
        $dia = $punch->work_date;
        $fila = EmployeeAttendance::where('user_id', $punch->user_id)->whereDate('attendance_date', $dia)->first();

        $estado = null;
        $mensaje = null;

        if ($punch->kind === 'entrada' && $fila) {
            $estado = $fila->status;   // puntual | retardo
            $mensaje = $estado === EmployeeAttendance::RETARDO
                ? "Retardo de {$fila->late_minutes} min"
                : 'Puntual';
        } elseif ($punch->kind === 'salida') {
            $mensaje = 'Hasta luego';
        } elseif ($punch->kind === 'regreso') {
            $mensaje = 'Bienvenido de vuelta';
        }

        return [
            'punch_id' => $punch->id,
            'duplicada' => $duplicada,
            'employee' => [
                'id' => $punch->user_id,
                'number' => $punch->user->checador_id ?: $punch->user->payroll_number ?: 'EMP-'.str_pad((string) $punch->user_id, 4, '0', STR_PAD_LEFT),
                'name' => $punch->user->name,
            ],
            'kind' => $punch->kind,
            'kind_label' => strtoupper($punch->kindLabel()),
            'time' => $punch->horaLocal()->format('H:i:s'),
            'status' => $estado,
            'message' => $mensaje,
        ];
    }

    // ------------------------------------------------------------------
    // Resumen del día
    // ------------------------------------------------------------------

    public function horarioDe(User $user): ?EmployeeSchedule
    {
        $h = $user->relationLoaded('horario') ? $user->horario : $user->horario()->first();

        return $h && $h->active ? $h : null;
    }

    public function esFestivo(Carbon $dia): bool
    {
        return Holiday::whereDate('date', $dia)->exists();
    }

    /** ¿Debía presentarse ese día? Con horario activo, día laboral y no festivo. */
    public function esperado(User $user, Carbon $dia): bool
    {
        // Antes de su fecha de ingreso no se le esperaba
        if ($user->fecha_ingreso && $dia->copy()->startOfDay()->lessThan($user->fecha_ingreso->copy()->startOfDay())) {
            return false;
        }

        // Un horario especial convierte un día de descanso en laborable (ej. sábado de inventario)
        if ($this->horarioDelDia($user, $dia) && ! $this->esFestivo($dia)) {
            return true;
        }

        return false;
    }

    /** Eventos de empresa que caen en un día (memorizados por día durante la petición). */
    private array $eventosMemo = [];

    private function eventosDelDia(Carbon $dia): \Illuminate\Support\Collection
    {
        $k = $dia->toDateString();

        return $this->eventosMemo[$k] ??= CompanyEvent::whereDate('date_from', '<=', $dia)->whereDate('date_to', '>=', $dia)->get();
    }

    /** El evento de empresa de un tipo que le toca a este empleado ese día, si hay. */
    public function eventoDe(User $user, Carbon $dia, string $tipo): ?CompanyEvent
    {
        return $this->eventosDelDia($dia)->first(fn (CompanyEvent $e) => $e->type === $tipo && $e->aplicaA($user->id));
    }

    /**
     * Horario que rige ese día para ese empleado, con este orden de prioridad:
     * evento de horario especial → horario propio de ese día de la semana
     * (sábado distinto, por ejemplo) → horario general.
     *
     * @return array{in: string, out: string, tolerance: int}|null  null si no le toca trabajar
     */
    public function horarioDelDia(User $user, Carbon $dia): ?array
    {
        $h = $this->horarioDe($user);

        if (! $h) {
            return null;
        }

        if ($e = $this->eventoDe($user, $dia, 'horario_especial')) {
            // Un horario especial puede caer en un día que normalmente no se trabaja
            if ($e->time_in && $e->time_out) {
                return [
                    'in' => substr($e->time_in, 0, 5), 'out' => substr($e->time_out, 0, 5),
                    'tolerance' => $e->tolerance_minutes ?? $h->tolerance_minutes,
                ];
            }
        }

        if (! $h->trabajaEl($dia)) {
            return null;
        }

        return $h->horasDe($dia) + ['tolerance' => $h->tolerance_minutes];
    }

    /**
     * Qué justifica que no checara: una modalidad de RH, unas vacaciones o un
     * permiso de día completo aprobado. Devuelve el tipo o null.
     */
    public function modalidadDe(User $user, Carbon $dia): ?array
    {
        $m = AttendanceModality::where('user_id', $user->id)
            ->whereDate('date_from', '<=', $dia)->whereDate('date_to', '>=', $dia)
            ->latest('id')->first();

        if ($m) {
            return ['type' => $m->type, 'requires_fingerprint' => $m->requires_fingerprint, 'label' => $m->tipoLabel()];
        }

        if (EmployeeVacationRequest::where('user_id', $user->id)->where('status', 'aprobada')
            ->whereDate('start_date', '<=', $dia)->whereDate('end_date', '>=', $dia)->exists()) {
            return ['type' => 'vacaciones', 'requires_fingerprint' => false, 'label' => 'Vacaciones'];
        }

        if (EmployeePermissionRequest::where('user_id', $user->id)->where('status', 'aprobada')
            ->whereDate('permission_date', $dia)->whereNull('start_time')->exists()) {
            return ['type' => 'permiso', 'requires_fingerprint' => false, 'label' => 'Permiso'];
        }

        // Eventos de toda la empresa o de un grupo: lo más específico (lo individual) ya se revisó arriba
        if ($e = $this->eventoDe($user, $dia, 'descanso')) {
            return ['type' => 'descanso_equipo', 'requires_fingerprint' => false, 'label' => $e->name];
        }

        if ($e = $this->eventoDe($user, $dia, 'sin_checar')) {
            return ['type' => 'evento', 'requires_fingerprint' => false, 'label' => $e->name];
        }

        return null;
    }

    /** Hay que llamarlo si cambian los eventos dentro de la misma petición. */
    public function olvidarEventos(): void
    {
        $this->eventosMemo = [];
    }

    /**
     * Recalcula el resumen de un día a partir de las checadas válidas.
     * No inventa faltas: eso es trabajo del cierre del día.
     */
    public function recalcularDia(User $user, Carbon $dia): ?EmployeeAttendance
    {
        $dia = $dia->copy()->startOfDay();
        $checadas = AttendancePunch::validas()->where('user_id', $user->id)->whereDate('work_date', $dia)
            ->orderBy('punched_at')->get();

        $mod = $this->modalidadDe($user, $dia);
        $fila = EmployeeAttendance::withTrashed()->where('user_id', $user->id)->whereDate('attendance_date', $dia)->first();

        // Sin checadas no hay nada que resumir (el cierre decide si es falta o justificada)
        if ($checadas->isEmpty()) {
            if ($fila && $fila->status !== EmployeeAttendance::FALTA && ! $mod) {
                $fila->delete();
            } elseif ($fila && $mod && $fila->status === EmployeeAttendance::FALTA) {
                $this->marcarJustificada($user, $dia, $mod);
            }

            return null;
        }

        $primera = $checadas->first()->horaLocal();
        $ultima = $checadas->last()->horaLocal();
        $n = $checadas->count();

        // Minutos trabajados: pares entrada→salida; una checada impar queda abierta
        $minutos = 0;
        for ($i = 0; $i + 1 < $n; $i += 2) {
            $minutos += (int) floor($checadas[$i]->punched_at->diffInSeconds($checadas[$i + 1]->punched_at) / 60);
        }

        $tarde = 0;
        $estado = EmployeeAttendance::PUNTUAL;
        $h = $this->horarioDelDia($user, $dia);
        // En un día sin checar o de descanso del equipo no hay hora de entrada que cumplir
        $sinHora = in_array($mod['type'] ?? null, ['descanso_equipo', 'evento'], true);

        if ($h && ! $sinHora && ! $this->esFestivo($dia)) {
            // Se compara por minuto: 09:05:59 sigue siendo puntual, 09:06:00 ya es retardo
            $entrada = $primera->copy()->startOfMinute();
            // El límite se arma en hora local, igual que la checada; usa el horario de ESE día
            $limite = Carbon::parse($dia->toDateString().' '.$h['in'], self::tz())->addMinutes($h['tolerance']);

            if ($entrada->greaterThan($limite)) {
                $tarde = max(1, (int) $limite->diffInMinutes($entrada));
                $estado = EmployeeAttendance::RETARDO;
            }
        }

        $datos = [
            'user_id' => $user->id,
            'attendance_date' => $dia->toDateString(),
            'check_in' => $primera->format('H:i:s'),
            'check_out' => $n >= 2 ? $ultima->format('H:i:s') : null,
            'late_minutes' => $tarde,
            'worked_minutes' => $minutos,
            'punches_count' => $n,
            'modality' => $mod['type'] ?? null,
            'incomplete' => $n % 2 === 1,
            'status' => $estado,
        ];

        if ($fila) {
            if ($fila->trashed()) {
                $fila->restore();
            }
            $fila->update($datos);
        } else {
            $fila = EmployeeAttendance::create($datos);
        }

        $this->quitarFaltaAutomatica($user, $dia);

        return $fila;
    }

    /** Recalcula un rango de días de un empleado (al cambiar modalidades, por ejemplo). */
    public function recalcularRango(User $user, Carbon $desde, Carbon $hasta): void
    {
        for ($d = $desde->copy()->startOfDay(); $d->lessThanOrEqualTo($hasta); $d->addDay()) {
            if ($d->isFuture() && ! $d->isToday()) {
                break;
            }
            $this->recalcularDia($user, $d);

            // Los días ya cerrados se vuelven a evaluar completos (falta ↔ justificada)
            if ($d->lessThan(self::diaLocal(now()))) {
                $this->cerrarDiaDe($user, $d);
            }
        }
    }

    // ------------------------------------------------------------------
    // Cierre del día: faltas automáticas
    // ------------------------------------------------------------------

    /** Empleados que deben checar: cuentas aprobadas con horario activo. */
    public function empleados()
    {
        return $this->consultaEmpleados()->orderBy('name')->get();
    }

    /** La misma lista sin ejecutar, para paginarla o filtrarla (API). */
    public function consultaEmpleados(): \Illuminate\Database\Eloquent\Builder
    {
        return User::with('horario')->where('status', User::STATUS_APPROVED)
            ->whereHas('horario', fn ($q) => $q->where('active', true));
    }

    /**
     * Cierra un día completo para todos. Devuelve conteos.
     *
     * @return array{faltas: int, justificadas: int, revisados: int}
     */
    public function cerrarDia(Carbon $dia): array
    {
        $dia = $dia->copy()->startOfDay();
        $r = ['faltas' => 0, 'justificadas' => 0, 'revisados' => 0];

        // Antes de que el checador empezara a operar nadie pudo checar: no hay faltas que generar
        $inicio = $this->inicioDeOperacion();
        if (! $inicio || $dia->lessThan($inicio)) {
            return $r;
        }

        foreach ($this->empleados() as $u) {
            $r['revisados']++;
            $res = $this->cerrarDiaDe($u, $dia);
            if ($res === EmployeeAttendance::FALTA) {
                $r['faltas']++;
            } elseif ($res === EmployeeAttendance::JUSTIFICADA) {
                $r['justificadas']++;
            }
        }

        return $r;
    }

    /** Primer día con una checada válida: desde ahí el checador "existe". */
    public function inicioDeOperacion(): ?Carbon
    {
        $min = AttendancePunch::validas()->min('work_date');

        return $min ? Carbon::parse($min)->startOfDay() : null;
    }

    /**
     * Un festivo cambia lo que se esperaba de ese día. Si ya pasó: las faltas
     * automáticas desaparecen (festivo nuevo) o regresan (festivo eliminado).
     * Las faltas puestas a mano por RH no se tocan.
     */
    public function corregirDiaPorFestivo(Carbon $dia): void
    {
        $dia = $dia->copy()->startOfDay();

        if ($dia->greaterThanOrEqualTo(self::diaLocal(now()))) {
            return;   // hoy y el futuro los resuelve el cierre normal
        }

        if (! $this->esFestivo($dia)) {
            $this->cerrarDia($dia);

            return;
        }

        foreach ($this->empleados() as $u) {
            $fila = EmployeeAttendance::where('user_id', $u->id)->whereDate('attendance_date', $dia)->first();

            if ($fila && $fila->status === EmployeeAttendance::FALTA) {
                $fila->delete();
            }
            $this->quitarFaltaAutomatica($u, $dia);
        }
    }

    public function marcarCerrado(Carbon $dia): void
    {
        \Illuminate\Support\Facades\Cache::put('asistencia:cerrado:'.$dia->toDateString(), true, now()->addDays(40));
    }

    /**
     * Respaldo del cron: al abrir el panel se cierran los días recientes que
     * aún no se hayan cerrado (hasta una semana atrás).
     */
    public function cerrarPendientes(int $dias = 7): void
    {
        $hoy = self::diaLocal(now());

        for ($i = $dias; $i >= 1; $i--) {
            $d = $hoy->copy()->subDays($i);

            if (! \Illuminate\Support\Facades\Cache::has('asistencia:cerrado:'.$d->toDateString())) {
                $this->cerrarDia($d);
                $this->marcarCerrado($d);
            }
        }
    }

    /** Cierra un día de un empleado. Devuelve 'falta', 'justificada' o null (nada que hacer). */
    public function cerrarDiaDe(User $user, Carbon $dia): ?string
    {
        $dia = $dia->copy()->startOfDay();

        $inicio = $this->inicioMemo ??= $this->inicioDeOperacion();
        if (! $inicio || $dia->lessThan($inicio)) {
            return null;
        }

        if (! $this->esperado($user, $dia)) {
            return null;
        }

        $tiene = AttendancePunch::validas()->where('user_id', $user->id)->whereDate('work_date', $dia)->exists();

        if ($tiene) {
            $this->recalcularDia($user, $dia);

            return null;
        }

        if ($mod = $this->modalidadDe($user, $dia)) {
            $this->marcarJustificada($user, $dia, $mod);

            return EmployeeAttendance::JUSTIFICADA;
        }

        DB::transaction(function () use ($user, $dia) {
            $fila = EmployeeAttendance::withTrashed()->where('user_id', $user->id)->whereDate('attendance_date', $dia)->first();
            $datos = [
                'user_id' => $user->id, 'attendance_date' => $dia->toDateString(),
                'check_in' => null, 'check_out' => null, 'late_minutes' => 0, 'worked_minutes' => 0,
                'punches_count' => 0, 'modality' => null, 'incomplete' => false, 'status' => EmployeeAttendance::FALTA,
            ];

            if ($fila) {
                $fila->trashed() ? $fila->restore() : null;
                $fila->update($datos);
            } else {
                EmployeeAttendance::create($datos);
            }

            EmployeeAbsence::withTrashed()->firstOrCreate(
                ['user_id' => $user->id, 'absence_date' => $dia->toDateString()],
                ['reason' => self::FALTA_AUTO, 'status' => 'por_justificar']
            )->restore();
        });

        return EmployeeAttendance::FALTA;
    }

    private function marcarJustificada(User $user, Carbon $dia, array $mod): void
    {
        $fila = EmployeeAttendance::withTrashed()->where('user_id', $user->id)->whereDate('attendance_date', $dia)->first();
        $datos = [
            'user_id' => $user->id, 'attendance_date' => $dia->toDateString(),
            'check_in' => null, 'check_out' => null, 'late_minutes' => 0, 'worked_minutes' => 0,
            'punches_count' => 0, 'modality' => $mod['type'], 'incomplete' => false,
            'status' => EmployeeAttendance::JUSTIFICADA,
        ];

        if ($fila) {
            $fila->trashed() ? $fila->restore() : null;
            $fila->update($datos);
        } else {
            EmployeeAttendance::create($datos);
        }

        $this->quitarFaltaAutomatica($user, $dia);
    }

    /** Si ya hay checada o justificante, la falta que puso el sistema se retira (las de RH no se tocan). */
    private function quitarFaltaAutomatica(User $user, Carbon $dia): void
    {
        EmployeeAbsence::where('user_id', $user->id)->whereDate('absence_date', $dia)
            ->where('reason', self::FALTA_AUTO)->where('status', 'por_justificar')->delete();
    }

    // ------------------------------------------------------------------
    // Anular una checada (nunca se borra ni se edita)
    // ------------------------------------------------------------------

    public function anular(AttendancePunch $punch, int $userId, string $motivo): void
    {
        if ($punch->voided_at) {
            return;
        }

        $punch->update(['voided_at' => now(), 'voided_by' => $userId, 'void_reason' => $motivo]);

        // Las checadas del día se renumeran para que entrada/salida sigan teniendo sentido
        $this->renumerar($punch->user_id, $punch->work_date);
        $this->recalcularDia($punch->user, $punch->work_date);

        if ($punch->work_date->lessThan(self::diaLocal(now()))) {
            $this->cerrarDiaDe($punch->user, $punch->work_date);
        }
    }

    public function renumerar(int $userId, Carbon $dia): void
    {
        $n = 0;
        foreach (AttendancePunch::validas()->where('user_id', $userId)->whereDate('work_date', $dia)->orderBy('punched_at')->get() as $p) {
            $n++;
            $p->update(['seq' => $n, 'kind' => $n === 1 ? 'entrada' : ($n % 2 === 0 ? 'salida' : 'regreso')]);
        }
    }
}

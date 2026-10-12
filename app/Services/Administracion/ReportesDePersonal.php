<?php

namespace App\Services\Administracion;

use App\Models\EmployeeReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Reportes de personal (asistencias, faltas, vacaciones, permisos e
 * incidencias): la consulta con filtros, el resumen por colaborador, los
 * indicadores y el alta. Lo usan la pantalla de Reportes y la API, para
 * que las cifras salgan iguales en las dos.
 */
class ReportesDePersonal
{
    public const TIPOS = [
        EmployeeReport::TYPE_ATTENDANCE => ['ui' => 'attendance', 'label' => 'Asistencia'],
        EmployeeReport::TYPE_ABSENCE => ['ui' => 'absence', 'label' => 'Falta'],
        EmployeeReport::TYPE_VACATION => ['ui' => 'vacation', 'label' => 'Vacaciones'],
        EmployeeReport::TYPE_PERMISSION => ['ui' => 'permission', 'label' => 'Permiso'],
        EmployeeReport::TYPE_INCIDENT => ['ui' => 'incident', 'label' => 'Incidencia'],
    ];

    public const ESTADOS = [
        EmployeeReport::STATUS_PENDING => 'Pendiente',
        EmployeeReport::STATUS_VALIDATED => 'Validada',
        EmployeeReport::STATUS_APPROVED => 'Aprobada',
        EmployeeReport::STATUS_JUSTIFY => 'Justificar',
        EmployeeReport::STATUS_REVIEW => 'Revision',
        EmployeeReport::STATUS_REJECTED => 'Rechazada',
        EmployeeReport::STATUS_CANCELLED => 'Cancelada',
    ];

    /** Lo que sigue abierto: cuenta como pendiente en los indicadores. */
    private const ABIERTOS = [
        EmployeeReport::STATUS_PENDING,
        EmployeeReport::STATUS_JUSTIFY,
        EmployeeReport::STATUS_REVIEW,
    ];

    public static function reglasAlta(): array
    {
        return [
            'employee_name' => ['required', 'string', 'max:150'],
            'area' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(self::TIPOS))],
            'status' => ['required', Rule::in(array_keys(self::ESTADOS))],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'late_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'detail' => ['required', 'string', 'max:1000'],
            'attendance' => ['nullable', 'integer', 'min:0', 'max:100'],
            'pending' => ['nullable', 'integer', 'min:0', 'max:999'],
            'summary_status' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Reportes que cumplen los filtros (start_date, end_date, area, person),
     * del más reciente al más viejo. Lo que no venga no filtra.
     */
    public function consulta(array $filtros): Builder
    {
        $query = EmployeeReport::with('employee')->latest('start_date');

        if (filled($filtros['start_date'] ?? null)) {
            $query->whereDate('start_date', '>=', $filtros['start_date']);
        }

        if (filled($filtros['end_date'] ?? null)) {
            $query->whereDate('start_date', '<=', $filtros['end_date']);
        }

        if (array_key_exists('area', $filtros) && $filtros['area'] !== 'all') {
            $query->where('area', $filtros['area']);
        }

        if (trim((string) ($filtros['person'] ?? '')) !== '') {
            $person = trim($filtros['person']);
            $query->where(function ($builder) use ($person) {
                $builder->where('employee_name', 'like', "%{$person}%")
                    ->orWhereHas('employee', fn ($employee) => $employee->where('name', 'like', "%{$person}%"));
            });
        }

        return $query;
    }

    public function registrar(array $data, User $quien): EmployeeReport
    {
        $employee = User::where('name', $data['employee_name'])->first();
        $type = $this->tipoDeBase($data['type']);

        return EmployeeReport::create([
            'user_id' => $employee?->id,
            'created_by' => $quien->id,
            'employee_name' => $data['employee_name'],
            'employee_initials' => $this->iniciales($data['employee_name']),
            'type' => $type,
            'label' => self::TIPOS[$type]['label'],
            'status' => $data['status'],
            'area' => $data['area'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'late_minutes' => $data['late_minutes'] ?? null,
            'detail' => $data['detail'],
            'metadata' => [
                'attendance' => isset($data['attendance']) ? $data['attendance'].'%' : null,
                'pending' => (int) ($data['pending'] ?? 0),
                'summary_status' => ($data['summary_status'] ?? null) ?: (self::ESTADOS[$data['status']] ?? 'Generado'),
                'source' => 'admin_generated_report',
            ],
            'generated_at' => now(),
        ]);
    }

    /** Renglones de la tabla de movimientos, ya con textos legibles. */
    public function renglones(Collection $reports): array
    {
        return $reports
            ->map(fn (EmployeeReport $report): array => [
                'employee' => $this->nombreEmpleado($report),
                'area' => $report->area ?: 'Sin area',
                'type' => self::TIPOS[$report->type]['ui'] ?? 'incident',
                'label' => $report->label ?: (self::TIPOS[$report->type]['label'] ?? 'Reporte'),
                'date' => $this->fechaVisible($report),
                'detail' => $report->detail ?: 'Sin detalle',
                'status' => self::ESTADOS[$report->status] ?? ucfirst((string) $report->status),
            ])
            ->values()
            ->all();
    }

    /** Un resumen por colaborador (y área), ordenado por nombre. */
    public function resumenPorColaborador(Collection $reports): array
    {
        return $reports
            ->groupBy(fn (EmployeeReport $report): string => $this->nombreEmpleado($report).'|'.($report->area ?: 'Sin area'))
            ->map(function (Collection $items): array {
                /** @var EmployeeReport $first */
                $first = $items->first();
                $metadata = $first->metadata ?? [];
                $pending = $items->whereIn('status', self::ABIERTOS)->count();

                return [
                    'name' => $this->nombreEmpleado($first),
                    'initials' => $first->employee_initials ?: $this->iniciales($this->nombreEmpleado($first)),
                    'area' => $first->area ?: 'Sin area',
                    'attendance' => $metadata['attendance'] ?? $this->tasaAsistencia($items),
                    'pending' => (string) ($metadata['pending'] ?? $pending),
                    'status' => $metadata['summary_status'] ?? (self::ESTADOS[$first->status] ?? 'En seguimiento'),
                ];
            })
            ->sortBy('name')
            ->values()
            ->all();
    }

    public function indicadores(Collection $reports, array $employees): array
    {
        $averageAttendance = collect($employees)
            ->map(fn (array $employee): int => (int) str_replace('%', '', $employee['attendance']))
            ->filter()
            ->avg();
        $permissionPending = $reports
            ->where('type', EmployeeReport::TYPE_PERMISSION)
            ->where('status', EmployeeReport::STATUS_PENDING)
            ->count();

        return [
            ['label' => 'Colaboradores', 'value' => count($employees), 'trend' => 'En seguimiento', 'type' => 'employee'],
            ['label' => 'Asistencias', 'value' => $averageAttendance ? round($averageAttendance).'%' : '0%', 'trend' => 'Promedio', 'type' => 'attendance'],
            ['label' => 'Faltas', 'value' => $reports->where('type', EmployeeReport::TYPE_ABSENCE)->count(), 'trend' => 'Periodo', 'type' => 'absence'],
            ['label' => 'Vacaciones', 'value' => $reports->where('type', EmployeeReport::TYPE_VACATION)->count(), 'trend' => 'Activas', 'type' => 'vacation'],
            ['label' => 'Permisos', 'value' => $reports->where('type', EmployeeReport::TYPE_PERMISSION)->count(), 'trend' => $permissionPending.' pendientes', 'type' => 'permission'],
            ['label' => 'Incidencias', 'value' => $reports->where('type', EmployeeReport::TYPE_INCIDENT)->count(), 'trend' => 'Revision', 'type' => 'incident'],
        ];
    }

    public function pendientesPorModulo(Collection $reports): array
    {
        return [
            ['type' => 'permission', 'count' => $this->abiertos($reports, EmployeeReport::TYPE_PERMISSION), 'title' => 'Permisos por aprobar', 'detail' => 'Solicitudes de salida personal en espera.', 'tone' => 'permission'],
            ['type' => 'absence', 'count' => $this->abiertos($reports, EmployeeReport::TYPE_ABSENCE), 'title' => 'Faltas por justificar', 'detail' => 'Registros sin evidencia cargada.', 'tone' => 'absence'],
            ['type' => 'incident', 'count' => $this->abiertos($reports, EmployeeReport::TYPE_INCIDENT), 'title' => 'Incidencias abiertas', 'detail' => 'Retardos y ajustes de asistencia.', 'tone' => 'incident'],
            ['type' => 'vacation', 'count' => $this->abiertos($reports, EmployeeReport::TYPE_VACATION), 'title' => 'Vacaciones por validar', 'detail' => 'Periodos pendientes de autorizacion.', 'tone' => 'vacation'],
            ['type' => 'attendance', 'count' => $this->abiertos($reports, EmployeeReport::TYPE_ATTENDANCE), 'title' => 'Asistencias por revisar', 'detail' => 'Entradas y salidas pendientes de cierre.', 'tone' => 'attendance'],
        ];
    }

    /** Áreas que de verdad aparecen en los reportes, para el filtro. */
    public function areas(): array
    {
        return EmployeeReport::query()
            ->whereNotNull('area')
            ->where('area', '<>', '')
            ->distinct()
            ->orderBy('area')
            ->pluck('area')
            ->values()
            ->all();
    }

    private function abiertos(Collection $reports, string $type): int
    {
        return $reports->where('type', $type)->whereIn('status', self::ABIERTOS)->count();
    }

    private function tipoDeBase(string $type): string
    {
        return match ($type) {
            'attendance' => EmployeeReport::TYPE_ATTENDANCE,
            'absence' => EmployeeReport::TYPE_ABSENCE,
            'vacation' => EmployeeReport::TYPE_VACATION,
            'permission' => EmployeeReport::TYPE_PERMISSION,
            'incident' => EmployeeReport::TYPE_INCIDENT,
            default => $type,
        };
    }

    public function nombreEmpleado(EmployeeReport $report): string
    {
        return $report->employee_name ?: ($report->employee?->name ?: 'Sin colaborador');
    }

    private function fechaVisible(EmployeeReport $report): string
    {
        $start = $report->start_date;
        $end = $report->end_date;

        if ($end && ! $end->isSameDay($start)) {
            return $start->format('d').'-'.$end->format('d').' '.$this->mes($end->month).' '.$end->format('Y');
        }

        return $start->format('d').' '.$this->mes($start->month).' '.$start->format('Y');
    }

    private function mes(int $month): string
    {
        return [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ][$month];
    }

    private function iniciales(string $name): string
    {
        return collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->implode('');
    }

    private function tasaAsistencia(Collection $reports): string
    {
        $total = $reports->count();

        if ($total === 0) {
            return '0%';
        }

        $attendance = $reports->where('type', EmployeeReport::TYPE_ATTENDANCE)->count();

        return round(($attendance / $total) * 100).'%';
    }
}

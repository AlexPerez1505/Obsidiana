<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeReport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    private const TYPE_META = [
        EmployeeReport::TYPE_ATTENDANCE => ['ui' => 'attendance', 'label' => 'Asistencia'],
        EmployeeReport::TYPE_ABSENCE => ['ui' => 'absence', 'label' => 'Falta'],
        EmployeeReport::TYPE_VACATION => ['ui' => 'vacation', 'label' => 'Vacaciones'],
        EmployeeReport::TYPE_PERMISSION => ['ui' => 'permission', 'label' => 'Permiso'],
        EmployeeReport::TYPE_INCIDENT => ['ui' => 'incident', 'label' => 'Incidencia'],
    ];

    private const STATUS_LABELS = [
        EmployeeReport::STATUS_PENDING => 'Pendiente',
        EmployeeReport::STATUS_VALIDATED => 'Validada',
        EmployeeReport::STATUS_APPROVED => 'Aprobada',
        EmployeeReport::STATUS_JUSTIFY => 'Justificar',
        EmployeeReport::STATUS_REVIEW => 'Revision',
        EmployeeReport::STATUS_REJECTED => 'Rechazada',
        EmployeeReport::STATUS_CANCELLED => 'Cancelada',
    ];

    public function index(Request $request): View
    {
        $filters = [
            'start_date' => $request->input('start_date', '2026-08-01'),
            'end_date' => $request->input('end_date', '2026-08-31'),
            'area' => $request->input('area', 'all'),
            'person' => $request->input('person', ''),
        ];

        $query = EmployeeReport::with('employee')->latest('start_date');

        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_date', '<=', $request->input('end_date'));
        }

        if ($filters['area'] !== 'all') {
            $query->where('area', $filters['area']);
        }

        if (trim($filters['person']) !== '') {
            $person = trim($filters['person']);
            $query->where(function ($builder) use ($person) {
                $builder->where('employee_name', 'like', "%{$person}%")
                    ->orWhereHas('employee', fn ($employee) => $employee->where('name', 'like', "%{$person}%"));
            });
        }

        $reports = $query->get();
        $employees = $this->employeeSummaries($reports);

        return view('admin.reportes.index', [
            'employees' => $employees,
            'metrics' => $this->metrics($reports, $employees),
            'records' => $this->records($reports),
            'pendingModules' => $this->pendingModules($reports),
            'areas' => $this->areas(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'employee_name' => ['required', 'string', 'max:150'],
            'area' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(self::TYPE_META))],
            'status' => ['required', Rule::in(array_keys(self::STATUS_LABELS))],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'late_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'detail' => ['required', 'string', 'max:1000'],
            'attendance' => ['nullable', 'integer', 'min:0', 'max:100'],
            'pending' => ['nullable', 'integer', 'min:0', 'max:999'],
            'summary_status' => ['nullable', 'string', 'max:50'],
        ]);

        $employee = User::where('name', $data['employee_name'])->first();
        $type = $this->uiTypeToDatabaseType($data['type']);

        EmployeeReport::create([
            'user_id' => $employee?->id,
            'created_by' => $request->user()->id,
            'employee_name' => $data['employee_name'],
            'employee_initials' => $this->initials($data['employee_name']),
            'type' => $type,
            'label' => self::TYPE_META[$type]['label'],
            'status' => $data['status'],
            'area' => $data['area'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'late_minutes' => $data['late_minutes'] ?? null,
            'detail' => $data['detail'],
            'metadata' => [
                'attendance' => isset($data['attendance']) ? $data['attendance'] . '%' : null,
                'pending' => (int) ($data['pending'] ?? 0),
                'summary_status' => $data['summary_status'] ?: (self::STATUS_LABELS[$data['status']] ?? 'Generado'),
                'source' => 'admin_generated_report',
            ],
            'generated_at' => now(),
        ]);

        return redirect()
            ->route('admin.reports.index')
            ->with('status', 'Reporte generado correctamente.');
    }

    private function records(Collection $reports): array
    {
        return $reports
            ->map(fn (EmployeeReport $report): array => [
                'employee' => $this->employeeName($report),
                'area' => $report->area ?: 'Sin area',
                'type' => self::TYPE_META[$report->type]['ui'] ?? 'incident',
                'label' => $report->label ?: (self::TYPE_META[$report->type]['label'] ?? 'Reporte'),
                'date' => $this->displayDate($report),
                'detail' => $report->detail ?: 'Sin detalle',
                'status' => self::STATUS_LABELS[$report->status] ?? ucfirst((string) $report->status),
            ])
            ->values()
            ->all();
    }

    private function uiTypeToDatabaseType(string $type): string
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

    private function employeeSummaries(Collection $reports): array
    {
        return $reports
            ->groupBy(fn (EmployeeReport $report): string => $this->employeeName($report) . '|' . ($report->area ?: 'Sin area'))
            ->map(function (Collection $items): array {
                /** @var EmployeeReport $first */
                $first = $items->first();
                $metadata = $first->metadata ?? [];
                $pending = $items->whereIn('status', [
                    EmployeeReport::STATUS_PENDING,
                    EmployeeReport::STATUS_JUSTIFY,
                    EmployeeReport::STATUS_REVIEW,
                ])->count();

                return [
                    'name' => $this->employeeName($first),
                    'initials' => $first->employee_initials ?: $this->initials($this->employeeName($first)),
                    'area' => $first->area ?: 'Sin area',
                    'attendance' => $metadata['attendance'] ?? $this->attendanceRate($items),
                    'pending' => (string) ($metadata['pending'] ?? $pending),
                    'status' => $metadata['summary_status'] ?? (self::STATUS_LABELS[$first->status] ?? 'En seguimiento'),
                ];
            })
            ->sortBy('name')
            ->values()
            ->all();
    }

    private function metrics(Collection $reports, array $employees): array
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
            ['label' => 'Asistencias', 'value' => $averageAttendance ? round($averageAttendance) . '%' : '0%', 'trend' => 'Promedio', 'type' => 'attendance'],
            ['label' => 'Faltas', 'value' => $reports->where('type', EmployeeReport::TYPE_ABSENCE)->count(), 'trend' => 'Periodo', 'type' => 'absence'],
            ['label' => 'Vacaciones', 'value' => $reports->where('type', EmployeeReport::TYPE_VACATION)->count(), 'trend' => 'Activas', 'type' => 'vacation'],
            ['label' => 'Permisos', 'value' => $reports->where('type', EmployeeReport::TYPE_PERMISSION)->count(), 'trend' => $permissionPending . ' pendientes', 'type' => 'permission'],
            ['label' => 'Incidencias', 'value' => $reports->where('type', EmployeeReport::TYPE_INCIDENT)->count(), 'trend' => 'Revision', 'type' => 'incident'],
        ];
    }

    private function pendingModules(Collection $reports): array
    {
        return [
            ['type' => 'permission', 'count' => $this->pendingCount($reports, EmployeeReport::TYPE_PERMISSION), 'title' => 'Permisos por aprobar', 'detail' => 'Solicitudes de salida personal en espera.', 'tone' => 'permission'],
            ['type' => 'absence', 'count' => $this->pendingCount($reports, EmployeeReport::TYPE_ABSENCE), 'title' => 'Faltas por justificar', 'detail' => 'Registros sin evidencia cargada.', 'tone' => 'absence'],
            ['type' => 'incident', 'count' => $this->pendingCount($reports, EmployeeReport::TYPE_INCIDENT), 'title' => 'Incidencias abiertas', 'detail' => 'Retardos y ajustes de asistencia.', 'tone' => 'incident'],
            ['type' => 'vacation', 'count' => $this->pendingCount($reports, EmployeeReport::TYPE_VACATION), 'title' => 'Vacaciones por validar', 'detail' => 'Periodos pendientes de autorizacion.', 'tone' => 'vacation'],
            ['type' => 'attendance', 'count' => $this->pendingCount($reports, EmployeeReport::TYPE_ATTENDANCE), 'title' => 'Asistencias por revisar', 'detail' => 'Entradas y salidas pendientes de cierre.', 'tone' => 'attendance'],
        ];
    }

    private function pendingCount(Collection $reports, string $type): int
    {
        return $reports
            ->where('type', $type)
            ->whereIn('status', [
                EmployeeReport::STATUS_PENDING,
                EmployeeReport::STATUS_JUSTIFY,
                EmployeeReport::STATUS_REVIEW,
            ])
            ->count();
    }

    private function areas(): array
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

    private function employeeName(EmployeeReport $report): string
    {
        return $report->employee_name ?: ($report->employee?->name ?: 'Sin colaborador');
    }

    private function displayDate(EmployeeReport $report): string
    {
        $start = $report->start_date;
        $end = $report->end_date;

        if ($end && ! $end->isSameDay($start)) {
            return $start->format('d') . '-' . $end->format('d') . ' ' . $this->month($end->month) . ' ' . $end->format('Y');
        }

        return $start->format('d') . ' ' . $this->month($start->month) . ' ' . $start->format('Y');
    }

    private function month(int $month): string
    {
        return [
            1 => 'Ene',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dic',
        ][$month];
    }

    private function initials(string $name): string
    {
        return collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->implode('');
    }

    private function attendanceRate(Collection $reports): string
    {
        $total = $reports->count();

        if ($total === 0) {
            return '0%';
        }

        $attendance = $reports->where('type', EmployeeReport::TYPE_ATTENDANCE)->count();

        return round(($attendance / $total) * 100) . '%';
    }
}

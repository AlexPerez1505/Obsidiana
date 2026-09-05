@extends('layouts.dashboard')
@section('title', 'Agenda')
@section('page-title', 'Agenda')
@section('page-sub', 'Gestion Administrativa > Agenda')

@php
    use App\Models\AgendaEvent;
    use Illuminate\Support\Carbon;

    $today = now()->toDateString();
    $view = request()->query('view', 'mes');

    if (request()->query('date')) {
        $refDate = Carbon::parse(request()->query('date'));
    } elseif (request()->query('month') || request()->query('year')) {
        $refDate = Carbon::createFromDate(
            (int) request()->query('year', now()->year),
            (int) request()->query('month', now()->month),
            1
        );
    } else {
        $refDate = now();
    }

    if ($view === 'dia') {
        $current = $refDate->copy()->startOfDay();
        $rangeStart = $current->copy();
        $rangeEnd = $current->copy()->endOfDay();
        $prev = $current->copy()->subDay();
        $next = $current->copy()->addDay();
        $periodLabel = ucfirst($current->locale('es')->translatedFormat('l j \d\e F'));
    } elseif ($view === 'semana') {
        $rangeStart = $refDate->copy()->startOfWeek(Carbon::MONDAY);
        $rangeEnd = $refDate->copy()->endOfWeek(Carbon::SUNDAY);
        $current = $rangeStart->copy();
        $prev = $rangeStart->copy()->subWeek();
        $next = $rangeStart->copy()->addWeek();
        $periodLabel = 'Semana del '.$rangeStart->format('j').' al '.$rangeEnd->format('j').' de '.ucfirst($rangeEnd->locale('es')->translatedFormat('F'));
    } else {
        $view = 'mes';
        $current = $refDate->copy()->startOfMonth();
        $rangeStart = $current->copy()->startOfMonth();
        $rangeEnd = $current->copy()->endOfMonth();
        $prev = $current->copy()->subMonth();
        $next = $current->copy()->addMonth();
        $periodLabel = ucfirst($current->locale('es')->translatedFormat('F')).' '.$current->year;
    }

    $statusMeta = [
        'completado' => ['label' => 'Completado', 'color' => '#22c55e'],
        'en_espera' => ['label' => 'En espera', 'color' => '#f59e0b'],
        'cancelado' => ['label' => 'Cancelado', 'color' => '#ef4444'],
        'programado' => ['label' => 'Próximamente', 'color' => '#3b82f6'],
    ];

    $typeLabels = [
        'training' => 'Capacitación',
        'delivery' => 'Entrega de equipo',
        'install' => 'Instalación',
        'maintenance' => 'Mantenimiento',
        'meeting' => 'Reunión',
        'congress' => 'Congreso',
    ];

    $currentUserId = (int) auth()->id();
    $visibilityScope = function ($query) use ($currentUserId) {
        $query->where('visibility', 'publico')
            ->orWhere('created_by', $currentUserId)
            ->orWhere(function ($q) use ($currentUserId) {
                $q->where('visibility', 'participantes')
                    ->whereJsonContains('participants', $currentUserId);
            });
    };

    $dbEvents = AgendaEvent::with('creator')
        ->whereDate('start_date', '<=', $rangeEnd->toDateString())
        ->whereDate('end_date', '>=', $rangeStart->toDateString())
        ->where($visibilityScope)
        ->orderBy('start_date')
        ->orderBy('start_time')
        ->get();

    $usersById = App\Models\User::query()->pluck('name', 'id');

    $events = [];
    $eventList = [];

    foreach ($dbEvents as $event) {
        $start = $event->start_date->copy()->max($rangeStart);
        $end = $event->end_date->copy()->min($rangeEnd);

        $participantIds = is_array($event->participants) ? $event->participants : [];
        $participantNames = collect($participantIds)
            ->map(fn ($id) => $usersById[(int) $id] ?? null)
            ->filter()
            ->implode(', ');

        $timeStart = $event->start_time ? Carbon::parse($event->start_time)->format('h:i a') : '';
        $timeEnd = '';
        if ($event->start_time) {
            $timeEnd = Carbon::parse($event->start_time)
                ->addMinutes((int) ($event->duration_minutes ?? 60))
                ->format('h:i a');
        }

        $canDelete = (int) $event->created_by === $currentUserId || (auth()->user()?->isAdmin() ?? false);

        $eventData = [
            'id' => $event->id,
            'start_date' => $event->start_date->toDateString(),
            'end_date' => $event->end_date->toDateString(),
            'time_value' => $event->start_time ? Carbon::parse($event->start_time)->format('H:i') : '09:00',
            'time' => $timeStart,
            'time_end' => $timeEnd,
            'title' => $event->title,
            'type' => $event->event_type,
            'type_label' => $typeLabels[$event->event_type] ?? $event->event_type,
            'status' => $event->status,
            'status_label' => $statusMeta[$event->status]['label'] ?? $event->status,
            'status_color' => $statusMeta[$event->status]['color'] ?? '#3b82f6',
            'reason' => (string) ($event->reason ?? ''),
            'notes' => (string) $event->notes,
            'participants' => $participantNames,
            'creator' => $event->creator?->name ?? 'Sin asignar',
            'can_delete' => $canDelete,
            'delete_url' => route('admin.agenda.destroy', $event->id),
        ];

        $eventList[] = $eventData;

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $events[$cursor->toDateString()][] = array_merge($eventData, [
                'is_start' => $cursor->toDateString() === $event->start_date->toDateString(),
                'is_end' => $cursor->toDateString() === $event->end_date->toDateString(),
            ]);
        }
    }

    if ($view === 'semana') {
        $gridStart = $rangeStart->copy();
        $gridEnd = $rangeEnd->copy();
    } else {
        $gridStart = $current->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $current->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
    }

    $calendar = [];
    for ($cursor = $gridStart->copy(); $cursor->lte($gridEnd); $cursor->addDay()) {
        $calendar[] = [
            'date' => $cursor->toDateString(),
            'num' => $cursor->day,
            'muted' => $view === 'mes' && $cursor->month !== $current->month,
            'is_today' => $cursor->toDateString() === $today,
        ];
    }

    $weekDayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

    $upcoming = AgendaEvent::query()
        ->whereDate('end_date', '>=', $today)
        ->where($visibilityScope)
        ->orderBy('start_date')
        ->orderBy('start_time')
        ->limit(6)
        ->get();

@endphp

@push('head')
<style>
    .agenda-page {
        display: flex;
        flex-direction: column;
        gap: 18px;
        color: #e5edf8;
    }

    .agenda-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
    }

    .agenda-head h2 {
        margin: 0;
        font-size: 26px;
        font-weight: 800;
        color: #f1f5f9;
    }

    .agenda-head p {
        margin: 4px 0 0;
        font-size: 14px;
        color: #8ba3c7;
    }

    .agenda-actions {
        display: flex;
        gap: 12px;
        flex: 0 0 auto;
    }

    .agenda-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        min-height: 42px;
        padding: 0 18px;
        border-radius: 9px;
        font-family: inherit;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
    }

    .agenda-btn svg {
        width: 18px;
        height: 18px;
    }

    .agenda-btn--ghost {
        border: 1px solid #274366;
        background: rgba(15, 27, 46, .85);
        color: #c9d8ee;
    }

    .agenda-btn--ghost:hover {
        border-color: #3b82f6;
        color: #fff;
    }

    .agenda-btn--primary {
        border: 0;
        background: #3b82f6;
        color: #fff;
        box-shadow: 0 8px 20px rgba(59, 130, 246, .3);
    }

    .agenda-btn--primary:hover {
        background: #2563eb;
    }

    .agenda-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 250px;
        gap: 20px;
        align-items: start;
    }

    .agenda-card {
        background: rgba(8, 17, 33, .9);
        border: 1px solid rgba(56, 189, 248, .35);
        border-radius: 14px;
        padding: 18px;
        box-shadow: 0 0 0 1px rgba(56, 189, 248, .08), 0 18px 45px rgba(2, 8, 23, .5);
    }

    .agenda-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .agenda-view {
        display: inline-flex;
        gap: 8px;
    }

    .agenda-view a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .agenda-view button,
    .agenda-view a {
        min-width: 74px;
        height: 38px;
        padding: 0 16px;
        border: 1px solid #274366;
        border-radius: 9px;
        background: rgba(15, 27, 46, .85);
        color: #c9d8ee;
        font-family: inherit;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }

    .agenda-view button.is-active,
    .agenda-view a.is-active {
        background: #3b82f6;
        border-color: #3b82f6;
        color: #fff;
        box-shadow: 0 6px 16px rgba(59, 130, 246, .35);
    }

    .agenda-nav {
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .agenda-nav-btn {
        width: 34px;
        height: 34px;
        border: 1px solid #274366;
        border-radius: 9px;
        background: rgba(15, 27, 46, .85);
        color: #c9d8ee;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        text-decoration: none;
    }

    .agenda-nav-btn:hover {
        border-color: #3b82f6;
        color: #fff;
    }

    .agenda-nav-btn svg {
        width: 16px;
        height: 16px;
    }

    .agenda-month-pill {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        height: 38px;
        padding: 0 18px;
        border: 1px solid #274366;
        border-radius: 9px;
        background: rgba(15, 27, 46, .85);
        color: #f1f5f9;
        font-size: 14px;
        font-weight: 800;
    }

    .agenda-month-pill svg {
        width: 17px;
        height: 17px;
        color: #8ba3c7;
    }

    .agenda-month-pill b {
        color: #3b82f6;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        border: 1px solid #1b3350;
        border-radius: 10px;
        overflow: hidden;
    }

    .calendar-day-name {
        padding: 12px 8px;
        background: rgba(13, 30, 54, .95);
        border-right: 1px solid #1b3350;
        color: #9db4d4;
        font-size: 12px;
        font-weight: 700;
        text-align: center;
    }

    .calendar-day-name:nth-child(7) {
        border-right: 0;
    }

    .calendar-cell {
        position: relative;
        min-height: 88px;
        padding: 8px;
        border-right: 1px solid #152a45;
        border-top: 1px solid #152a45;
        background: rgba(7, 15, 29, .9);
        color: inherit;
        font: inherit;
        text-align: left;
        cursor: pointer;
        transition: background .15s ease;
    }

    .calendar-cell:hover {
        background: rgba(30, 55, 92, .55);
    }

    .calendar-cell:nth-child(7n) {
        border-right: 0;
    }

    .calendar-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 26px;
        height: 26px;
        padding: 0 6px;
        border-radius: 999px;
        color: #cbd5e1;
        font-size: 13px;
        font-weight: 700;
    }

    .calendar-number.is-muted {
        color: #4d6486;
    }

    .calendar-number.is-today {
        background: #3b82f6;
        color: #fff;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, .22);
    }

    .calendar-events {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin-top: 6px;
    }

    .calendar-event {
        width: 100%;
        padding: 4px 7px;
        border: 0;
        border-radius: 6px;
        font-family: inherit;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.15;
        text-align: left;
        cursor: pointer;
        color: #eaf2ff;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .day-view {
        border: 1px solid #1b3350;
        border-radius: 10px;
        overflow: hidden;
        background: rgba(7, 15, 29, .9);
    }

    .day-view-head {
        padding: 12px 16px;
        background: rgba(13, 30, 54, .95);
        border-bottom: 1px solid #1b3350;
        color: #9db4d4;
        font-size: 13px;
        font-weight: 800;
        text-transform: capitalize;
    }

    .day-row {
        display: grid;
        grid-template-columns: 72px 1fr;
        border-bottom: 1px solid #152a45;
        min-height: 48px;
        cursor: pointer;
        transition: background .15s ease;
    }

    .day-row:last-child {
        border-bottom: 0;
    }

    .day-row:hover {
        background: rgba(30, 55, 92, .55);
    }

    .day-hour {
        padding: 12px 10px;
        border-right: 1px solid #152a45;
        color: #61789a;
        font-size: 12px;
        font-weight: 700;
        text-align: right;
    }

    .day-slot {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 6px 10px;
    }

    .day-slot .calendar-event {
        white-space: normal;
        font-size: 11px;
        padding: 6px 9px;
    }

    .day-empty {
        margin: 0;
        padding: 14px;
        text-align: center;
        font-size: 12px;
        color: #61789a;
        border-top: 1px solid #152a45;
    }

    :root[data-theme="light"] .day-view {
        background: #ffffff;
        border-color: #e2e8f0;
    }

    :root[data-theme="light"] .day-view-head {
        background: #f1f5f9;
        border-bottom-color: #e2e8f0;
        color: #64748b;
    }

    :root[data-theme="light"] .day-row {
        border-bottom-color: #e2e8f0;
    }

    :root[data-theme="light"] .day-row:hover {
        background: #eff6ff;
    }

    :root[data-theme="light"] .day-hour {
        border-right-color: #e2e8f0;
        color: #94a3b8;
    }

    :root[data-theme="light"] .day-empty {
        border-top-color: #e2e8f0;
        color: #94a3b8;
    }

    /* ===== Vista Semana (rejilla de horas) ===== */
    .week-view {
        border: 1px solid #1b3350;
        border-radius: 10px;
        overflow: hidden;
        background: rgba(7, 15, 29, .9);
    }

    .week-grid {
        display: grid;
        grid-template-columns: 64px repeat(7, minmax(0, 1fr));
        max-height: 560px;
        overflow-y: auto;
    }

    .week-corner,
    .week-day-head {
        position: sticky;
        top: 0;
        z-index: 3;
        padding: 12px 8px;
        background: rgba(13, 30, 54, .97);
        border-bottom: 1px solid #1b3350;
        border-right: 1px solid #1b3350;
        color: #9db4d4;
        font-size: 12px;
        font-weight: 700;
        text-align: center;
    }

    .week-corner {
        z-index: 4;
        text-align: left;
        padding-left: 12px;
    }

    .week-day-head.is-today {
        background: #1d4ed8;
        color: #fff;
    }

    .week-hour {
        padding: 10px;
        border-right: 1px solid #152a45;
        border-bottom: 1px solid #152a45;
        background: rgba(7, 15, 29, .9);
        color: #61789a;
        font-size: 12px;
        font-weight: 700;
        text-align: right;
    }

    .week-cell {
        min-height: 52px;
        padding: 4px 6px;
        border-right: 1px solid #152a45;
        border-bottom: 1px solid #152a45;
        display: flex;
        flex-direction: column;
        gap: 3px;
        cursor: pointer;
        transition: background .15s ease;
    }

    .week-cell:hover {
        background: rgba(30, 55, 92, .55);
    }

    .week-cell.is-today {
        background: rgba(37, 99, 235, .16);
    }

    .week-cell.is-today:hover {
        background: rgba(37, 99, 235, .28);
    }

    .week-cell .calendar-event {
        font-size: 10px;
        padding: 3px 6px;
    }

    /* ===== Vista Día (horas + panel Citas) ===== */
    .day-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 240px;
        gap: 16px;
        align-items: start;
    }

    .day-view--scroll {
        max-height: 560px;
        overflow-y: auto;
    }

    .day-citas {
        min-height: 200px;
    }

    .day-citas-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .day-cita-item {
        display: grid;
        grid-template-columns: 4px 1fr;
        gap: 10px;
        align-items: center;
        width: 100%;
        padding: 4px 0;
        border: 0;
        background: transparent;
        font-family: inherit;
        font-size: 13px;
        color: #dbe7f8;
        text-align: left;
        cursor: pointer;
    }

    .day-cita-item .cita-line {
        align-self: stretch;
        border-radius: 999px;
    }

    .day-cita-item b {
        display: block;
        font-size: 12px;
        color: #9db4d4;
        margin-bottom: 2px;
    }

    .day-citas-empty {
        margin: 18px 0;
        text-align: center;
        font-size: 12px;
        color: #61789a;
    }

    :root[data-theme="light"] .week-view {
        background: #ffffff;
        border-color: #e2e8f0;
    }

    :root[data-theme="light"] .week-corner,
    :root[data-theme="light"] .week-day-head {
        background: #f8fafc;
        border-bottom-color: #e2e8f0;
        border-right-color: #e2e8f0;
        color: #64748b;
    }

    :root[data-theme="light"] .week-day-head.is-today {
        background: #2563eb;
        color: #fff;
    }

    :root[data-theme="light"] .week-hour {
        background: #ffffff;
        border-right-color: #e2e8f0;
        border-bottom-color: #e2e8f0;
        color: #94a3b8;
    }

    :root[data-theme="light"] .week-cell {
        border-right-color: #e2e8f0;
        border-bottom-color: #e2e8f0;
    }

    :root[data-theme="light"] .week-cell:hover {
        background: #eff6ff;
    }

    :root[data-theme="light"] .week-cell.is-today {
        background: #dbeafe;
    }

    :root[data-theme="light"] .day-cita-item {
        color: #334155;
    }

    :root[data-theme="light"] .day-cita-item b {
        color: #64748b;
    }

    :root[data-theme="light"] .day-citas-empty {
        color: #94a3b8;
    }

    @media (max-width: 900px) {
        .day-layout {
            grid-template-columns: 1fr;
        }
    }

    .agenda-side {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .side-card {
        background: rgba(8, 17, 33, .9);
        border: 1px solid rgba(56, 189, 248, .3);
        border-radius: 14px;
        padding: 16px;
    }

    .side-card h3 {
        margin: 0 0 14px;
        font-size: 14px;
        font-weight: 800;
        color: #f1f5f9;
    }

    .filter-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 7px 0;
        font-size: 13px;
        font-weight: 600;
        color: #c9d8ee;
        cursor: pointer;
        user-select: none;
    }

    .filter-item input {
        width: 16px;
        height: 16px;
        accent-color: #3b82f6;
        cursor: pointer;
    }

    .filter-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        flex: 0 0 auto;
    }

    .upcoming-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .upcoming-item {
        display: grid;
        grid-template-columns: 4px 1fr auto;
        gap: 10px;
        align-items: center;
        font-size: 13px;
        color: #dbe7f8;
    }

    .upcoming-delete {
        width: 30px;
        height: 30px;
        border: 1px solid #274366;
        border-radius: 8px;
        background: rgba(239, 68, 68, .12);
        color: #f87171;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .upcoming-delete:hover {
        background: rgba(239, 68, 68, .25);
        border-color: #ef4444;
    }

    .upcoming-delete svg {
        width: 15px;
        height: 15px;
    }

    .upcoming-delete-form {
        margin: 0;
        display: inline-flex;
    }

    .upcoming-line {
        border-radius: 999px;
    }

    .upcoming-item b {
        display: block;
        font-size: 12px;
        color: #9db4d4;
        margin-bottom: 2px;
    }

    .upcoming-empty {
        margin: 6px 0 4px;
        text-align: center;
        font-size: 12px;
        color: #61789a;
    }

    .agenda-modal {
        position: fixed;
        inset: 0;
        z-index: 100;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
        background: rgba(2, 6, 23, .6);
    }

    .agenda-modal.is-open {
        display: flex;
    }

    .agenda-dialog {
        width: min(480px, 100%);
        background: #0c1a2e;
        border: 1px solid rgba(56, 189, 248, .35);
        border-radius: 14px;
        box-shadow: 0 22px 60px rgba(2, 6, 23, .55);
        overflow: hidden;
    }

    .agenda-dialog--wizard {
        width: min(900px, 100%);
        max-height: 92vh;
        display: flex;
        flex-direction: column;
    }

    .wizard-body {
        padding: 18px 20px;
        overflow-y: auto;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
        gap: 16px;
        align-items: start;
    }

    .wizard-card {
        display: grid;
        gap: 13px;
        background: rgba(13, 30, 54, .55);
        border: 1px solid rgba(56, 189, 248, .28);
        border-radius: 14px;
        padding: 16px;
    }

    .wizard-card h4 {
        margin: 0;
        font-size: 15px;
        font-weight: 800;
        color: #f1f5f9;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .wizard-card-num {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        background: #3b82f6;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex: 0 0 auto;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, .18);
    }

    .wizard-col {
        display: grid;
        gap: 16px;
    }

    @media (max-width: 860px) {
        .wizard-body {
            grid-template-columns: 1fr;
        }
    }

    .wizard-two-col {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
        gap: 18px;
        align-items: start;
    }

    .mini-cal {
        background: rgba(148, 163, 184, .14);
        border: 1px solid #274366;
        border-radius: 12px;
        padding: 12px;
    }

    .mini-cal-nav {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 10px;
    }

    .mini-cal-nav-group {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(15, 27, 46, .7);
        border: 1px solid #274366;
        border-radius: 9px;
        padding: 4px 8px;
        color: #f1f5f9;
        font-size: 13px;
        font-weight: 800;
    }

    .mini-cal-nav-group button {
        border: 0;
        background: transparent;
        color: #8ba3c7;
        cursor: pointer;
        padding: 2px 6px;
        font-size: 14px;
    }

    .mini-cal-nav-group button:hover {
        color: #fff;
    }

    .mini-cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 3px;
    }

    .mini-cal-dow {
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        color: #9db4d4;
        padding: 6px 0;
    }

    .mini-cal-day {
        aspect-ratio: 1;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: #dbe7f8;
        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }

    .mini-cal-day:hover {
        background: rgba(59, 130, 246, .25);
    }

    .mini-cal-day.is-muted {
        color: #55688a;
    }

    .mini-cal-day.is-today {
        color: #38bdf8;
        font-weight: 800;
    }

    .mini-cal-day.is-selected {
        background: #3b82f6;
        color: #fff;
        font-weight: 800;
        box-shadow: 0 6px 16px rgba(59, 130, 246, .4);
    }

    .mini-cal-day:disabled {
        color: #3b4d6b;
        cursor: default;
    }

    .time-col {
        display: grid;
        gap: 12px;
    }

    .time-col > p {
        margin: 0;
        font-size: 13px;
        font-weight: 700;
        color: #c9d8ee;
    }

    .avail-bar {
        display: grid;
        grid-template-columns: repeat(28, 1fr);
        gap: 2px;
        height: 26px;
    }

    .avail-seg {
        border-radius: 3px;
        background: #22c55e;
    }

    .avail-seg.is-busy {
        background: #ef4444;
    }

    .avail-seg.is-blocked {
        background: #64748b;
    }

    .avail-seg.is-past {
        background: #1e3a5f;
    }

    .time-inputs {
        display: flex;
        align-items: flex-end;
        gap: 10px;
    }

    .time-inputs label,
    .duration-label {
        display: block;
        margin-bottom: 5px;
        font-size: 11px;
        font-weight: 700;
        color: #9db4d4;
    }

    .time-box {
        width: 64px;
        padding: 10px 8px;
        border: 1px solid #274366;
        border-radius: 9px;
        background: #0a1526;
        color: #f1f5f9;
        font-family: inherit;
        font-size: 18px;
        font-weight: 800;
        text-align: center;
        outline: none;
        color-scheme: dark;
    }

    .time-sep {
        font-size: 22px;
        font-weight: 800;
        color: #9db4d4;
        padding-bottom: 8px;
    }

    .duration-row {
        display: flex;
        align-items: flex-end;
        gap: 10px;
    }

    .duration-btn {
        width: 44px;
        height: 44px;
        border: 1px solid #274366;
        border-radius: 9px;
        background: #0a1526;
        color: #f1f5f9;
        font-size: 20px;
        font-weight: 800;
        cursor: pointer;
    }

    .duration-btn:hover {
        border-color: #3b82f6;
    }

    .duration-value {
        width: 70px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #274366;
        border-radius: 9px;
        background: #0a1526;
        font-size: 18px;
        font-weight: 800;
        color: #f1f5f9;
    }

    .duration-unit {
        padding-bottom: 12px;
        font-size: 12px;
        color: #9db4d4;
    }

    .time-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .time-summary p {
        margin: 0;
        font-size: 13px;
        color: #9db4d4;
    }

    .time-summary b {
        color: #f1f5f9;
    }

    .avail-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 14px;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 800;
    }

    .avail-badge.is-free {
        background: rgba(34, 197, 94, .15);
        border: 1px solid rgba(34, 197, 94, .55);
        color: #4ade80;
    }

    .avail-badge.is-busy {
        background: rgba(239, 68, 68, .15);
        border: 1px solid rgba(239, 68, 68, .55);
        color: #f87171;
    }

    .avail-legend {
        display: flex;
        gap: 16px;
        font-size: 12px;
        color: #9db4d4;
    }

    .avail-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
    }

    .summary-list {
        display: grid;
        gap: 10px;
        margin: 0;
    }

    .summary-row {
        display: grid;
        grid-template-columns: 170px 1fr;
        gap: 10px;
        padding: 10px 12px;
        border: 1px solid #1b3350;
        border-radius: 9px;
        background: rgba(15, 27, 46, .6);
        font-size: 13px;
    }

    .summary-row dt {
        color: #9db4d4;
        font-weight: 700;
    }

    .summary-row dd {
        margin: 0;
        color: #f1f5f9;
        font-weight: 600;
    }

    .wizard-footer {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 20px 18px;
        border-top: 1px solid #1b3350;
    }

    .wizard-footer .agenda-btn {
        min-width: 120px;
    }

    .wizard-footer [hidden] {
        display: none;
    }

    .agenda-dialog-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 18px 20px;
        border-bottom: 1px solid #1b3350;
    }

    .agenda-dialog-head h3 {
        margin: 0;
        font-size: 17px;
        color: #f1f5f9;
    }

    .agenda-close {
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 50%;
        background: rgba(30, 55, 92, .6);
        color: #dbe7f8;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .agenda-close svg {
        width: 18px;
        height: 18px;
    }

    .agenda-form {
        display: grid;
        gap: 13px;
        padding: 18px 20px 20px;
    }

    .agenda-form label {
        display: block;
        margin-bottom: 5px;
        font-size: 12px;
        font-weight: 700;
        color: #9db4d4;
    }

    .agenda-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .agenda-form-row--three {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .agenda-form input,
    .agenda-form select,
    .agenda-form textarea {
        width: 100%;
        padding: 11px 12px;
        border: 1px solid #274366;
        border-radius: 9px;
        background: #0a1526;
        color: #e5edf8;
        font: inherit;
        outline: none;
        color-scheme: dark;
    }

    .agenda-form textarea {
        min-height: 84px;
        resize: vertical;
    }

    .agenda-form input:focus,
    .agenda-form select:focus,
    .agenda-form textarea:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .18);
    }

    .agenda-save {
        justify-self: end;
        min-height: 42px;
        padding: 0 20px;
        border: 0;
        border-radius: 9px;
        background: #3b82f6;
        color: #fff;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    .agenda-save:hover {
        background: #2563eb;
    }

    .event-popover {
        position: fixed;
        z-index: 90;
        width: 250px;
        background: #0c1a2e;
        border: 1px solid rgba(56, 189, 248, .45);
        border-radius: 14px;
        box-shadow: 0 18px 50px rgba(2, 6, 23, .6);
        padding: 16px;
        display: none;
        gap: 10px;
    }

    .event-popover.is-open {
        display: grid;
    }

    .event-popover-head {
        display: flex;
        align-items: center;
        gap: 11px;
    }

    .event-popover-avatar {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        background: #3b82f6;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        font-weight: 800;
        text-transform: uppercase;
        flex: 0 0 auto;
    }

    .event-popover-head b {
        font-size: 14px;
        color: #f1f5f9;
    }

    .event-popover-head small {
        display: block;
        font-size: 11px;
        color: #8ba3c7;
    }

    .event-popover-rows {
        display: grid;
        gap: 4px;
        font-size: 12px;
        color: #c9d8ee;
    }

    .event-popover-rows p {
        margin: 0;
    }

    .event-popover-rows b {
        color: #9db4d4;
        font-weight: 700;
    }

    .event-popover-status {
        justify-self: start;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        border: 1px solid currentColor;
    }

    .event-popover-actions {
        display: grid;
        gap: 8px;
    }

    .event-popover-actions .agenda-btn {
        min-height: 38px;
        font-size: 13px;
    }

    .event-popover-actions .agenda-btn--danger {
        border: 1px solid rgba(239, 68, 68, .55);
        background: rgba(239, 68, 68, .12);
        color: #f87171;
    }

    .event-popover-actions .agenda-btn--danger:hover {
        background: rgba(239, 68, 68, .25);
    }

    :root[data-theme="light"] .agenda-page {
        color: #334155;
    }

    :root[data-theme="light"] .agenda-head h2 {
        color: #0f172a;
    }

    :root[data-theme="light"] .agenda-head p {
        color: #64748b;
    }

    :root[data-theme="light"] .agenda-btn--ghost,
    :root[data-theme="light"] .agenda-view button,
    :root[data-theme="light"] .agenda-view a,
    :root[data-theme="light"] .agenda-nav-btn,
    :root[data-theme="light"] .agenda-month-pill {
        background: #ffffff;
        border-color: #dbe4f0;
        color: #475569;
    }

    :root[data-theme="light"] .agenda-btn--ghost:hover,
    :root[data-theme="light"] .agenda-view button:hover,
    :root[data-theme="light"] .agenda-view a:hover,
    :root[data-theme="light"] .agenda-nav-btn:hover {
        border-color: #3b82f6;
        color: #1d4ed8;
    }

    :root[data-theme="light"] .agenda-month-pill {
        color: #0f172a;
    }

    :root[data-theme="light"] .agenda-card,
    :root[data-theme="light"] .side-card {
        background: #ffffff;
        border-color: #dbe4f0;
        box-shadow: 0 1px 4px rgba(15, 23, 42, .06);
    }

    :root[data-theme="light"] .calendar-grid {
        border-color: #e2e8f0;
    }

    :root[data-theme="light"] .calendar-day-name {
        background: #f1f5f9;
        border-right-color: #e2e8f0;
        color: #64748b;
    }

    :root[data-theme="light"] .calendar-cell {
        background: #ffffff;
        border-right-color: #e2e8f0;
        border-top-color: #e2e8f0;
    }

    :root[data-theme="light"] .calendar-cell:hover {
        background: #eff6ff;
    }

    :root[data-theme="light"] .calendar-number {
        color: #334155;
    }

    :root[data-theme="light"] .calendar-number.is-muted {
        color: #cbd5e1;
    }

    :root[data-theme="light"] .side-card h3 {
        color: #0f172a;
    }

    :root[data-theme="light"] .filter-item {
        color: #475569;
    }

    :root[data-theme="light"] .upcoming-item {
        color: #334155;
    }

    :root[data-theme="light"] .upcoming-item b {
        color: #64748b;
    }

    :root[data-theme="light"] .upcoming-empty {
        color: #94a3b8;
    }

    :root[data-theme="light"] .upcoming-delete {
        border-color: #fecaca;
        background: #fef2f2;
        color: #dc2626;
    }

    :root[data-theme="light"] .upcoming-delete:hover {
        background: #fee2e2;
        border-color: #ef4444;
    }

    :root[data-theme="light"] .event-popover {
        background: #ffffff;
        border-color: #cbd5e1;
        box-shadow: 0 18px 50px rgba(15, 23, 42, .18);
    }

    :root[data-theme="light"] .event-popover-head b {
        color: #0f172a;
    }

    :root[data-theme="light"] .event-popover-head small {
        color: #64748b;
    }

    :root[data-theme="light"] .event-popover-rows {
        color: #475569;
    }

    :root[data-theme="light"] .event-popover-rows b {
        color: #64748b;
    }

    :root[data-theme="light"] .event-popover-actions .agenda-btn--danger {
        border-color: #fecaca;
        background: #fef2f2;
        color: #dc2626;
    }

    :root[data-theme="light"] .event-popover-actions .agenda-btn--danger:hover {
        background: #fee2e2;
    }

    @media (max-width: 1100px) {
        .agenda-shell {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 760px) {
        .agenda-head {
            flex-direction: column;
        }

        .agenda-actions,
        .agenda-actions .agenda-btn {
            width: 100%;
        }

        .calendar-grid {
            overflow-x: auto;
        }

        .calendar-day-name,
        .calendar-cell {
            min-width: 76px;
        }

        .agenda-form-row,
        .agenda-form-row--three {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    <section class="agenda-page">
        <div class="agenda-head">
            <div>
                <h2>Agenda</h2>
                <p>Gestiona tus citas y procedimientos</p>
            </div>

            <div class="agenda-actions">
                <a class="agenda-btn agenda-btn--primary" href="{{ route('admin.agenda.create') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                    Agendar cita
                </a>
            </div>
        </div>

        <div class="agenda-shell">
            <div class="agenda-card">
                <div class="agenda-toolbar">
                    <div class="agenda-view" aria-label="Vista de calendario">
                        <a class="{{ $view === 'dia' ? 'is-active' : '' }}" href="{{ route('admin.agenda.index', ['view' => 'dia', 'date' => $current->toDateString()]) }}">Día</a>
                        <a class="{{ $view === 'semana' ? 'is-active' : '' }}" href="{{ route('admin.agenda.index', ['view' => 'semana', 'date' => $current->toDateString()]) }}">Semana</a>
                        <a class="{{ $view === 'mes' ? 'is-active' : '' }}" href="{{ route('admin.agenda.index', ['view' => 'mes', 'date' => $current->toDateString()]) }}">Mes</a>
                    </div>

                    <div class="agenda-nav">
                        <a class="agenda-nav-btn" href="{{ route('admin.agenda.index', ['view' => $view, 'date' => $prev->toDateString()]) }}" aria-label="Periodo anterior">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"></path></svg>
                        </a>
                        <span class="agenda-month-pill">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 11h18"></path></svg>
                            {{ $periodLabel }}
                        </span>
                        <a class="agenda-nav-btn" href="{{ route('admin.agenda.index', ['view' => $view, 'date' => $next->toDateString()]) }}" aria-label="Periodo siguiente">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"></path></svg>
                        </a>
                        <button class="agenda-nav-btn" type="button" aria-label="Pantalla completa" data-agenda-fullscreen>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>
                        </button>
                    </div>
                </div>

                @if ($view === 'mes')
                <div class="calendar-grid" aria-label="Calendario de {{ $periodLabel }}">
                    @foreach ($weekDayNames as $day)
                        <div class="calendar-day-name">{{ $day }}</div>
                    @endforeach

                    @foreach ($calendar as $day)
                        @php
                            $dayEvents = $events[$day['date']] ?? [];
                        @endphp

                        <div class="calendar-cell" role="button" tabindex="0" data-agenda-day data-agenda-date="{{ $day['date'] }}" aria-label="{{ $dayEvents ? 'Editar cita del '.$day['date'] : 'Agendar cita el '.$day['date'] }}">
                            <span class="calendar-number {{ $day['muted'] ? 'is-muted' : '' }} {{ $day['is_today'] ? 'is-today' : '' }}">{{ $day['num'] }}</span>

                            @if ($dayEvents)
                                <div class="calendar-events">
                                    @foreach ($dayEvents as $event)
                                        @php
                                            $color = $statusMeta[$event['status']]['color'] ?? '#3b82f6';
                                            $isMultiDay = ! $event['is_start'] || ! $event['is_end'];
                                            $rangeLabel = $event['is_start'] ? 'Inicia' : ($event['is_end'] ? 'Termina' : 'Continúa');
                                        @endphp
                                        <button
                                            class="calendar-event"
                                            type="button"
                                            data-agenda-event
                                            data-agenda-status="{{ $event['status'] }}"
                                            data-agenda-id="{{ $event['id'] }}"
                                            data-agenda-date="{{ $day['date'] }}"
                                            data-agenda-json='@json($event)'
                                            style="background: {{ $color }}33; color: {{ $color }}; box-shadow: inset 0 0 0 1px {{ $color }}66;"
                                            aria-label="Ver cita {{ $event['title'] }} del {{ $day['date'] }}"
                                        >
                                            {{ $event['time'] ? $event['time'].' · ' : '' }}{{ $event['title'] }}{{ $isMultiDay ? ' ('.$rangeLabel.')' : '' }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                @elseif ($view === 'semana')
                <div class="week-view" aria-label="Semana de {{ $periodLabel }}">
                    <div class="week-grid">
                        <div class="week-corner">Hora</div>
                        @foreach ($calendar as $i => $day)
                            <div class="week-day-head {{ $day['is_today'] ? 'is-today' : '' }}">
                                {{ $weekDayNames[$i] ?? '' }} {{ $day['num'] }}
                            </div>
                        @endforeach

                        @for ($h = 7; $h <= 20; $h++)
                            <div class="week-hour">{{ $h }}:00</div>
                            @foreach ($calendar as $day)
                                @php
                                    $hourEvents = collect($events[$day['date']] ?? [])->filter(function ($e) use ($h) {
                                        return (int) substr($e['time_value'] ?? '09:00', 0, 2) === $h;
                                    });
                                @endphp
                                <div class="week-cell {{ $day['is_today'] ? 'is-today' : '' }}" data-agenda-day data-agenda-date="{{ $day['date'] }}" role="button" tabindex="0" aria-label="Agendar cita el {{ $day['date'] }} a las {{ $h }}:00">
                                    @foreach ($hourEvents as $event)
                                        @php
                                            $color = $statusMeta[$event['status']]['color'] ?? '#3b82f6';
                                        @endphp
                                        <button
                                            class="calendar-event"
                                            type="button"
                                            data-agenda-event
                                            data-agenda-status="{{ $event['status'] }}"
                                            data-agenda-id="{{ $event['id'] }}"
                                            data-agenda-date="{{ $day['date'] }}"
                                            data-agenda-json='@json($event)'
                                            style="background: {{ $color }}33; color: {{ $color }}; box-shadow: inset 0 0 0 1px {{ $color }}66;"
                                            aria-label="Ver cita {{ $event['title'] }} del {{ $day['date'] }}"
                                        >
                                            {{ $event['time'] ? $event['time'].' · ' : '' }}{{ $event['title'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endforeach
                        @endfor
                    </div>
                </div>
                @else
                    @php
                        $dayEventsAll = $events[$current->toDateString()] ?? [];
                    @endphp
                    <div class="day-layout">
                        <div class="day-view day-view--scroll" aria-label="Horas del {{ $periodLabel }}">
                            @for ($h = 7; $h <= 21; $h++)
                                @php
                                    $hourEvents = collect($dayEventsAll)->filter(function ($e) use ($h) {
                                        return (int) substr($e['time_value'] ?? '09:00', 0, 2) === $h;
                                    });
                                @endphp
                                <div class="day-row" data-agenda-day data-agenda-date="{{ $current->toDateString() }}" role="button" tabindex="0" aria-label="Agendar cita a las {{ Carbon::createFromTime($h)->format('g:i A') }}">
                                    <span class="day-hour">{{ Carbon::createFromTime($h)->format('g:i A') }}</span>
                                    <div class="day-slot">
                                        @foreach ($hourEvents as $event)
                                            @php
                                                $color = $statusMeta[$event['status']]['color'] ?? '#3b82f6';
                                            @endphp
                                            <button
                                                class="calendar-event"
                                                type="button"
                                                data-agenda-event
                                                data-agenda-status="{{ $event['status'] }}"
                                                data-agenda-id="{{ $event['id'] }}"
                                                data-agenda-date="{{ $current->toDateString() }}"
                                                data-agenda-json='@json($event)'
                                                style="background: {{ $color }}33; color: {{ $color }}; box-shadow: inset 0 0 0 1px {{ $color }}66;"
                                            >
                                                {{ $event['time'] ? $event['time'].' · ' : '' }}{{ $event['title'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endfor
                        </div>

                        <aside class="side-card day-citas" aria-label="Citas del día">
                            <h3>Citas</h3>
                            <div class="day-citas-list">
                                @forelse ($dayEventsAll as $event)
                                    @php
                                        $color = $statusMeta[$event['status']]['color'] ?? '#3b82f6';
                                    @endphp
                                    <button
                                        class="day-cita-item"
                                        type="button"
                                        data-agenda-event
                                        data-agenda-status="{{ $event['status'] }}"
                                        data-agenda-id="{{ $event['id'] }}"
                                        data-agenda-date="{{ $current->toDateString() }}"
                                        data-agenda-json='@json($event)'
                                    >
                                        <span class="cita-line" style="background: {{ $color }};"></span>
                                        <span>
                                            <b>{{ $event['time'] ?: 'Todo el día' }}</b>
                                            {{ $event['title'] }}
                                        </span>
                                    </button>
                                @empty
                                    <p class="day-citas-empty">Sin citas para este día</p>
                                @endforelse
                            </div>
                        </aside>
                    </div>
                @endif
            </div>

            <aside class="agenda-side" aria-label="Filtros y próximas citas">
                <div class="side-card">
                    <h3>Filtros rápidos</h3>
                    @foreach ($statusMeta as $status => $meta)
                        <label class="filter-item">
                            <input type="checkbox" checked data-agenda-filter="{{ $status }}">
                            <span class="filter-dot" style="background: {{ $meta['color'] }};"></span>
                            {{ $meta['label'] }}
                        </label>
                    @endforeach
                </div>

                <div class="side-card">
                    <h3>Próximas citas</h3>
                    <div class="upcoming-list">
                        @forelse ($upcoming as $event)
                            @php($color = $statusMeta[$event->status]['color'] ?? '#3b82f6')
                            <div class="upcoming-item">
                                <span class="upcoming-line" style="background: {{ $color }};"></span>
                                <span>
                                    <b>{{ $event->start_date->format('d/m/Y') }} {{ $event->start_time ? '· '.Carbon::parse($event->start_time)->format('h:i a') : '' }}</b>
                                    {{ $event->title }}
                                </span>
                                @if ((int) $event->created_by === (int) auth()->id() || auth()->user()->isAdmin())
                                    <form class="upcoming-delete-form" method="POST" action="{{ route('admin.agenda.destroy', $event) }}" onsubmit="return confirm('¿Eliminar la cita \'{{ addslashes($event->title) }}\'?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="upcoming-delete" type="submit" aria-label="Eliminar cita {{ $event->title }}" title="Eliminar cita">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14zM10 11v6M14 11v6"></path></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="upcoming-empty">Sin citas próximas</p>
                        @endforelse
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <div class="event-popover" id="eventPopover" aria-hidden="true">
        <div class="event-popover-head">
            <span class="event-popover-avatar" id="popAvatar">A</span>
            <div>
                <b id="popTitle">Cita</b>
                <small id="popCreator"></small>
            </div>
        </div>
        <div class="event-popover-rows">
            <p><b>Fecha:</b> <span id="popDate"></span></p>
            <p><b>Motivo:</b> <span id="popReason"></span></p>
            <p><b>Tiempo:</b> <span id="popTime"></span></p>
            <p id="popParticipantsRow"><b>Participantes:</b> <span id="popParticipants"></span></p>
        </div>
        <span class="event-popover-status" id="popStatus"></span>
        <div class="event-popover-actions">
            <a class="agenda-btn agenda-btn--primary" id="popReprogram" href="#">Reprogramar cita</a>
            <button class="agenda-btn agenda-btn--danger" type="button" id="popDelete">Eliminar cita</button>
        </div>
    </div>

    <form id="popoverDeleteForm" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

    <script>
        const agendaCreateUrl = '{{ route('admin.agenda.create') }}';

        document.querySelectorAll('[data-agenda-day]').forEach((day) => {
            day.addEventListener('click', () => {
                window.location.href = agendaCreateUrl + '?date=' + day.dataset.agendaDate;
            });
            day.addEventListener('keydown', (event) => {
                if (event.target.closest('[data-agenda-event]')) {
                    return;
                }

                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    window.location.href = agendaCreateUrl + '?date=' + day.dataset.agendaDate;
                }
            });
        });

        const eventPopover = document.getElementById('eventPopover');
        const popAvatar = document.getElementById('popAvatar');
        const popTitle = document.getElementById('popTitle');
        const popCreator = document.getElementById('popCreator');
        const popDate = document.getElementById('popDate');
        const popReason = document.getElementById('popReason');
        const popTime = document.getElementById('popTime');
        const popParticipants = document.getElementById('popParticipants');
        const popParticipantsRow = document.getElementById('popParticipantsRow');
        const popStatus = document.getElementById('popStatus');
        const popReprogram = document.getElementById('popReprogram');
        const popDelete = document.getElementById('popDelete');
        const popoverDeleteForm = document.getElementById('popoverDeleteForm');
        let popoverHideTimer = null;

        function formatDateEs(dateStr) {
            const parts = (dateStr || '').split('-');
            return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : dateStr;
        }

        function openEventPopover(button) {
            const data = JSON.parse(button.dataset.agendaJson || '{}');

            popAvatar.textContent = (data.title || 'A').charAt(0);
            popAvatar.style.background = (data.status_color || '#3b82f6');
            popTitle.textContent = data.title || 'Cita';
            popCreator.textContent = 'Creada por ' + (data.creator || '—');
            popDate.textContent = formatDateEs(data.start_date) + (data.end_date !== data.start_date ? ' al ' + formatDateEs(data.end_date) : '');
            popReason.textContent = data.reason || data.type_label || '—';
            popTime.textContent = data.time
                ? data.time + (data.time_end ? ' – ' + data.time_end : '')
                : 'Todo el día';
            popParticipants.textContent = data.participants || '—';
            popParticipantsRow.hidden = ! data.participants;
            popStatus.textContent = data.status_label || '—';
            popStatus.style.color = data.status_color || '#3b82f6';
            popReprogram.href = agendaCreateUrl + '?event=' + (data.id || '');
            popDelete.hidden = ! data.can_delete;
            popDelete.dataset.deleteUrl = data.delete_url || '';
            popDelete.dataset.deleteTitle = data.title || '';

            eventPopover.classList.add('is-open');
            eventPopover.setAttribute('aria-hidden', 'false');

            const rect = button.getBoundingClientRect();
            const popRect = eventPopover.getBoundingClientRect();
            let left = rect.right + 10;
            let top = rect.top - 10;

            if (left + popRect.width > window.innerWidth - 12) {
                left = rect.left - popRect.width - 10;
            }

            if (top + popRect.height > window.innerHeight - 12) {
                top = window.innerHeight - popRect.height - 12;
            }

            eventPopover.style.left = Math.max(8, left) + 'px';
            eventPopover.style.top = Math.max(8, top) + 'px';
        }

        function schedulePopoverHide() {
            popoverHideTimer = window.setTimeout(() => {
                eventPopover.classList.remove('is-open');
                eventPopover.setAttribute('aria-hidden', 'true');
            }, 180);
        }

        function cancelPopoverHide() {
            if (popoverHideTimer) {
                window.clearTimeout(popoverHideTimer);
                popoverHideTimer = null;
            }
        }

        document.querySelectorAll('[data-agenda-event]').forEach((item) => {
            item.addEventListener('mouseenter', () => {
                cancelPopoverHide();
                openEventPopover(item);
            });
            item.addEventListener('mouseleave', schedulePopoverHide);
            item.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });

        eventPopover.addEventListener('mouseenter', cancelPopoverHide);
        eventPopover.addEventListener('mouseleave', schedulePopoverHide);

        popDelete.addEventListener('click', () => {
            if (! popDelete.dataset.deleteUrl) {
                return;
            }

            if (confirm('¿Eliminar la cita "' + popDelete.dataset.deleteTitle + '"?')) {
                popoverDeleteForm.action = popDelete.dataset.deleteUrl;
                popoverDeleteForm.submit();
            }
        });

        document.querySelectorAll('[data-agenda-filter]').forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                const status = checkbox.dataset.agendaFilter;
                document.querySelectorAll('[data-agenda-status="' + status + '"]').forEach((item) => {
                    item.style.display = checkbox.checked ? '' : 'none';
                });
            });
        });

        const agendaFullscreenButton = document.querySelector('[data-agenda-fullscreen]');
        if (agendaFullscreenButton) {
            agendaFullscreenButton.addEventListener('click', () => {
                const card = document.querySelector('.agenda-card');
                if (! document.fullscreenElement) {
                    card.requestFullscreen && card.requestFullscreen();
                } else {
                    document.exitFullscreen();
                }
            });
        }

    </script>
@endsection
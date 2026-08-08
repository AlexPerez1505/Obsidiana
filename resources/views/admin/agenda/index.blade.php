@extends('layouts.dashboard')
@section('title', 'Agenda')
@section('page-title', 'Agenda')
@section('page-sub', 'Gestion Administrativa > Agenda')

@php
    $eventColors = [
        'training' => ['bg' => '#9be8f3', 'text' => '#075f6f', 'line' => '#8ee6f0'],
        'delivery' => ['bg' => '#96f5ad', 'text' => '#0f7a2d', 'line' => '#9cf3b1'],
        'install' => ['bg' => '#c7a7ff', 'text' => '#3d178b', 'line' => '#c8a7ff'],
        'meeting' => ['bg' => '#ffd9a3', 'text' => '#9a4f00', 'line' => '#ffd39a'],
        'maintenance' => ['bg' => '#ff9ea5', 'text' => '#a3131e', 'line' => '#ffa0a6'],
        'congress' => ['bg' => '#a9bcff', 'text' => '#053394', 'line' => '#a9bcff'],
    ];

    $eventList = [
        [
            'id' => 'evt-training',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'time' => '10:00 am',
            'time_value' => '10:00',
            'title' => 'Capacitacion',
            'type' => 'training',
            'notes' => 'Preparar materiales y confirmar asistentes.',
            'participants' => 'Ricardo, Marina Sherlyn, Jose Alex',
        ],
        [
            'id' => 'evt-delivery',
            'start_date' => '2026-07-05',
            'end_date' => '2026-07-05',
            'time' => '08:00 am',
            'time_value' => '08:00',
            'title' => 'Entrega de equipo',
            'type' => 'delivery',
            'notes' => 'Confirmar recepcion y evidencia de entrega.',
            'participants' => 'Ing. Joel Diaz, Almacen Central',
        ],
        [
            'id' => 'evt-install',
            'start_date' => '2026-07-09',
            'end_date' => '2026-07-09',
            'time' => '08:00 am',
            'time_value' => '08:00',
            'title' => 'Instalacion',
            'type' => 'install',
            'notes' => 'Validar acceso al area antes de iniciar.',
            'participants' => 'Servicios, Cliente asignado',
        ],
        [
            'id' => 'evt-meeting',
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-13',
            'time' => '10:00 am',
            'time_value' => '10:00',
            'title' => 'Reunion',
            'type' => 'meeting',
            'notes' => 'Revisar pendientes administrativos.',
            'participants' => 'Direccion, Administracion',
        ],
        [
            'id' => 'evt-congress',
            'start_date' => '2026-07-25',
            'end_date' => '2026-07-25',
            'time' => '09:00 am',
            'time_value' => '09:00',
            'title' => 'Congreso',
            'type' => 'congress',
            'notes' => 'Confirmar agenda y participantes.',
            'participants' => 'Marketing, Ventas, Direccion',
        ],
        [
            'id' => 'evt-maintenance',
            'start_date' => '2026-07-28',
            'end_date' => '2026-07-28',
            'time' => '11:00 am',
            'time_value' => '11:00',
            'title' => 'Mantenimiento',
            'type' => 'maintenance',
            'notes' => 'Programar revision preventiva del equipo.',
            'participants' => 'Tecnico interno, Responsable de equipo',
        ],
    ];

    $events = [];

    foreach ($eventList as $event) {
        $start = new DateTimeImmutable($event['start_date']);
        $end = new DateTimeImmutable($event['end_date']);

        for ($cursor = $start; $cursor <= $end; $cursor = $cursor->modify('+1 day')) {
            if ($cursor->format('Y-m') !== '2026-07') {
                continue;
            }

            $dayNumber = (int) $cursor->format('j');
            $events[$dayNumber][] = array_merge($event, [
                'date' => $cursor->format('Y-m-d'),
                'is_start' => $cursor->format('Y-m-d') === $event['start_date'],
                'is_end' => $cursor->format('Y-m-d') === $event['end_date'],
            ]);
        }
    }

    $calendar = [
        ['num' => 28, 'muted' => true],
        ['num' => 29, 'muted' => true],
        ['num' => 30, 'muted' => true],
        ['num' => 1],
        ['num' => 2],
        ['num' => 3],
        ['num' => 4],
        ['num' => 5],
        ['num' => 6],
        ['num' => 7],
        ['num' => 8],
        ['num' => 9],
        ['num' => 10],
        ['num' => 11],
        ['num' => 12],
        ['num' => 13],
        ['num' => 14],
        ['num' => 15],
        ['num' => 16],
        ['num' => 17],
        ['num' => 18],
        ['num' => 19],
        ['num' => 20],
        ['num' => 21],
        ['num' => 22],
        ['num' => 23],
        ['num' => 24],
        ['num' => 25],
        ['num' => 26],
        ['num' => 27],
        ['num' => 28],
        ['num' => 29],
        ['num' => 30],
        ['num' => 31],
        ['num' => 1, 'muted' => true],
    ];

    $upcoming = $eventList;
@endphp

@push('head')
<style>
    .agenda-page {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .agenda-crumb {
        display: flex;
        align-items: center;
        gap: 5px;
        color: var(--muted);
        font-size: 13px;
        font-weight: 700;
    }

    .agenda-crumb a {
        color: var(--primary);
        text-decoration: none;
    }

    .agenda-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    .agenda-title {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .agenda-title-ico {
        width: 34px;
        height: 34px;
        color: var(--primary);
        flex: 0 0 auto;
    }

    .agenda-title h2 {
        margin: 0;
        color: var(--text);
        font-size: 24px;
        line-height: 1.1;
    }

    .agenda-add {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 44px;
        padding: 0 22px;
        border: 0;
        border-radius: 6px;
        background: #2563eb;
        color: #fff;
        font-family: inherit;
        font-size: 15px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 10px 22px rgba(37, 99, 235, .22);
    }

    .agenda-add:hover {
        background: #1d4ed8;
    }

    .agenda-add svg,
    .agenda-filter svg,
    .agenda-icon-btn svg {
        width: 20px;
        height: 20px;
        flex: 0 0 auto;
    }

    .agenda-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 220px;
        gap: 22px;
        align-items: start;
    }

    .agenda-main {
        min-width: 0;
    }

    .agenda-toolbar {
        display: grid;
        grid-template-columns: auto auto auto minmax(130px, 1fr) auto;
        gap: 14px;
        align-items: center;
        margin-bottom: 12px;
    }

    .agenda-today,
    .agenda-icon-btn,
    .agenda-view button,
    .agenda-filter {
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text);
        font-family: inherit;
        font-weight: 700;
        cursor: pointer;
    }

    .agenda-today {
        min-width: 72px;
        height: 42px;
        border-radius: 5px;
    }

    .agenda-icon-btn {
        width: 42px;
        height: 42px;
        border-radius: 5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .agenda-icon-btn:hover,
    .agenda-today:hover,
    .agenda-filter:hover {
        border-color: rgba(37, 99, 235, .45);
        color: var(--primary);
    }

    .agenda-month {
        justify-self: center;
        margin: 0;
        font-size: 15px;
        font-weight: 800;
        color: var(--text);
    }

    .agenda-view {
        justify-self: end;
        display: inline-grid;
        grid-template-columns: repeat(3, 68px);
        overflow: hidden;
        border: 1px solid var(--border);
        border-radius: 5px;
        background: var(--surface);
    }

    .agenda-view button {
        height: 42px;
        border: 0;
        border-left: 1px solid var(--border);
        border-radius: 0;
        background: transparent;
    }

    .agenda-view button:first-child {
        border-left: 0;
    }

    .agenda-view button.is-active {
        background: #dbeafe;
        color: #2563eb;
        box-shadow: inset 0 0 0 1px #3b82f6;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(84px, 1fr));
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 8px;
        overflow: hidden;
    }

    .calendar-day-name {
        min-height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        border-right: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        color: var(--text);
        font-size: 13px;
        font-weight: 800;
        background: var(--surface);
    }

    .calendar-day-name:nth-child(7) {
        border-right: 0;
    }

    .calendar-cell {
        position: relative;
        min-height: 84px;
        padding: 10px 8px 8px;
        border-top: 0;
        border-left: 0;
        border-right: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        background: var(--surface);
        color: inherit;
        font: inherit;
        text-align: left;
        cursor: pointer;
        transition: background .16s ease, box-shadow .16s ease;
    }

    .calendar-cell:hover {
        background: var(--surface-2);
        box-shadow: inset 0 0 0 2px rgba(37, 99, 235, .2);
    }

    .calendar-cell.is-busy {
        background: rgba(37, 99, 235, .08);
        box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .22);
    }

    .calendar-cell.is-range-start {
        box-shadow: inset 4px 0 0 rgba(37, 99, 235, .65), inset 0 0 0 1px rgba(37, 99, 235, .22);
    }

    .calendar-cell.is-range-end {
        box-shadow: inset -4px 0 0 rgba(37, 99, 235, .45), inset 0 0 0 1px rgba(37, 99, 235, .22);
    }

    .calendar-cell:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .35);
        outline-offset: -3px;
    }

    .calendar-cell:nth-child(7n) {
        border-right: 0;
    }

    .calendar-cell:nth-last-child(-n+7) {
        border-bottom: 0;
    }

    .calendar-number {
        display: block;
        min-height: 18px;
        color: var(--text);
        font-size: 14px;
        font-weight: 700;
    }

    .calendar-number.is-muted {
        color: #9ca3af;
    }

    .calendar-cell.is-busy .calendar-number {
        color: #2563eb;
        font-weight: 900;
    }

    .calendar-events {
        display: flex;
        flex-direction: column;
        gap: 5px;
        margin-top: 8px;
    }

    .calendar-event {
        width: 100%;
        padding: 5px 7px 6px;
        border: 0;
        border-radius: 5px;
        font-size: 10px;
        line-height: 1.05;
        font-weight: 700;
        font-family: inherit;
        text-align: left;
        overflow: hidden;
        cursor: pointer;
    }

    .calendar-event:hover {
        filter: saturate(1.08) brightness(.98);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .22);
    }

    .calendar-event:focus-visible {
        outline: 2px solid rgba(37, 99, 235, .5);
        outline-offset: 2px;
    }

    .calendar-event b,
    .upcoming-item b {
        display: block;
        font-size: 10px;
        line-height: 1.1;
    }

    .calendar-event span,
    .upcoming-item span {
        display: block;
        line-height: 1.1;
    }

    .calendar-event small {
        display: block;
        margin-top: 3px;
        font-size: 9px;
        line-height: 1.1;
        opacity: .82;
    }

    .agenda-side {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .agenda-filter {
        align-self: flex-start;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        height: 42px;
        min-width: 104px;
        padding: 0 14px;
        border-radius: 5px;
        color: #2563eb;
        background: #dbeafe;
        box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .55);
    }

    .upcoming-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 18px 12px;
    }

    .upcoming-panel h3 {
        margin: 0 0 14px;
        color: var(--text);
        text-align: center;
        font-size: 15px;
    }

    .upcoming-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .upcoming-item {
        display: grid;
        grid-template-columns: 5px 1fr;
        gap: 12px;
        align-items: start;
        color: var(--text);
        font-size: 14px;
        line-height: 1.12;
        font-weight: 800;
    }

    .upcoming-line {
        width: 5px;
        height: 42px;
        border-radius: 999px;
    }

    .upcoming-item b {
        font-size: 13px;
    }

    .upcoming-item span {
        font-size: 13px;
    }

    .agenda-modal {
        position: fixed;
        inset: 0;
        z-index: 100;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
        background: rgba(2, 6, 23, .52);
    }

    .agenda-modal.is-open {
        display: flex;
    }

    .agenda-dialog {
        width: min(480px, 100%);
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 22px 60px rgba(2, 6, 23, .28);
        overflow: hidden;
    }

    .agenda-dialog-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 18px 20px;
        border-bottom: 1px solid var(--border);
    }

    .agenda-dialog-head h3 {
        margin: 0;
        font-size: 18px;
    }

    .agenda-close {
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 50%;
        background: var(--surface-2);
        color: var(--text);
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
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--surface);
        color: var(--text);
        font: inherit;
        outline: none;
    }

    .agenda-form textarea {
        min-height: 84px;
        resize: vertical;
    }

    .agenda-form input:focus,
    .agenda-form select:focus,
    .agenda-form textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(0, 122, 255, .12);
    }

    .agenda-save {
        justify-self: end;
        min-height: 42px;
        padding: 0 18px;
        border: 0;
        border-radius: 7px;
        background: #2563eb;
        color: #fff;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    :root[data-theme="dark"] .agenda-view button.is-active,
    :root[data-theme="dark"] .agenda-filter {
        background: rgba(37, 99, 235, .18);
    }

    :root[data-theme="dark"] .calendar-event {
        filter: saturate(.95) brightness(.95);
    }

    :root[data-theme="dark"] .calendar-cell:hover {
        background: rgba(10, 132, 255, .12);
    }

    :root[data-theme="dark"] .calendar-cell.is-busy {
        background: rgba(10, 132, 255, .13);
    }

    @media (max-width: 1100px) {
        .agenda-shell {
            grid-template-columns: 1fr;
        }

        .agenda-side {
            display: grid;
            grid-template-columns: auto minmax(220px, 1fr);
            align-items: start;
        }
    }

    @media (max-width: 760px) {
        .agenda-hero,
        .agenda-toolbar {
            grid-template-columns: 1fr;
        }

        .agenda-hero {
            align-items: flex-start;
            flex-direction: column;
        }

        .agenda-add {
            width: 100%;
        }

        .agenda-toolbar {
            display: grid;
            grid-template-columns: repeat(3, 42px);
        }

        .agenda-today {
            grid-column: 1 / -1;
            width: 100%;
        }

        .agenda-month,
        .agenda-view {
            grid-column: 1 / -1;
            justify-self: stretch;
        }

        .agenda-view {
            grid-template-columns: repeat(3, 1fr);
        }

        .calendar-grid {
            grid-template-columns: repeat(7, minmax(72px, 1fr));
            overflow-x: auto;
        }

        .calendar-day-name,
        .calendar-cell {
            min-width: 72px;
        }

        .agenda-side {
            grid-template-columns: 1fr;
        }

        .agenda-filter {
            width: 100%;
        }

        .agenda-form-row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    <section class="agenda-page">
        <nav class="agenda-crumb" aria-label="Ruta">
            <a href="{{ route('dashboard') }}">Gestion Administrativa</a>
            <span>&gt;</span>
            <strong>Agenda</strong>
        </nav>

        <div class="agenda-hero">
            <div class="agenda-title">
                <svg class="agenda-title-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="4" y="3" width="16" height="18" rx="2"></rect>
                    <path d="M8 3v18M4 8h16M4 13h16M4 18h16"></path>
                </svg>
                <h2>Agenda/Evento</h2>
            </div>

            <button class="agenda-add" type="button" data-agenda-modal-open>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                Nuevo Evento
            </button>
        </div>

        <div class="agenda-shell">
            <div class="agenda-main">
                <div class="agenda-toolbar">
                    <button class="agenda-today" type="button">Hoy</button>
                    <button class="agenda-icon-btn" type="button" aria-label="Mes anterior">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"></path></svg>
                    </button>
                    <button class="agenda-icon-btn" type="button" aria-label="Mes siguiente">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"></path></svg>
                    </button>
                    <p class="agenda-month">Julio 2026</p>
                    <div class="agenda-view" aria-label="Vista de calendario">
                        <button type="button" class="is-active">Mes</button>
                        <button type="button">Semana</button>
                        <button type="button">Dia</button>
                    </div>
                </div>

                <div class="calendar-grid" aria-label="Calendario de julio 2026">
                    @foreach (['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'] as $day)
                        <div class="calendar-day-name">{{ $day }}</div>
                    @endforeach

                    @foreach ($calendar as $day)
                        @php
                            $dayEvents = empty($day['muted']) ? ($events[$day['num']] ?? []) : [];
                            $dateMonth = empty($day['muted']) ? '07' : ($loop->index < 3 ? '06' : '08');
                            $selectedDate = '2026-'.$dateMonth.'-'.str_pad($day['num'], 2, '0', STR_PAD_LEFT);
                            $dayClasses = ['calendar-cell'];

                            if ($dayEvents) {
                                $dayClasses[] = 'is-busy';

                                if (collect($dayEvents)->contains('is_start', true)) {
                                    $dayClasses[] = 'is-range-start';
                                }

                                if (collect($dayEvents)->contains('is_end', true)) {
                                    $dayClasses[] = 'is-range-end';
                                }
                            }
                        @endphp

                        <div class="{{ implode(' ', $dayClasses) }}" role="button" tabindex="0" data-agenda-day data-agenda-date="{{ $selectedDate }}" aria-label="{{ $dayEvents ? 'Editar evento del '.$selectedDate : 'Registrar evento el '.$selectedDate }}">
                            <span class="calendar-number {{ ! empty($day['muted']) ? 'is-muted' : '' }}">{{ str_pad($day['num'], 2, '0', STR_PAD_LEFT) }}</span>

                            @if ($dayEvents)
                                <div class="calendar-events">
                                    @foreach ($dayEvents as $event)
                                        @php
                                            $color = $eventColors[$event['type']];
                                            $isMultiDay = $event['start_date'] !== $event['end_date'];
                                            $rangeLabel = $event['is_start'] ? 'Inicia' : ($event['is_end'] ? 'Termina' : 'Continua');
                                        @endphp
                                        <button
                                            class="calendar-event"
                                            type="button"
                                            data-agenda-event
                                            data-agenda-id="{{ $event['id'] }}"
                                            data-agenda-date="{{ $selectedDate }}"
                                            data-agenda-start-date="{{ $event['start_date'] }}"
                                            data-agenda-end-date="{{ $event['end_date'] }}"
                                            data-agenda-title="{{ $event['title'] }}"
                                            data-agenda-time="{{ $event['time_value'] }}"
                                            data-agenda-type="{{ $event['type'] }}"
                                            data-agenda-notes="{{ $event['notes'] }}"
                                            data-agenda-participants="{{ $event['participants'] }}"
                                            style="background: {{ $color['bg'] }}; color: {{ $color['text'] }};"
                                            aria-label="Editar evento {{ $event['title'] }} del {{ $selectedDate }}"
                                        >
                                            <b>{{ $event['time'] }}</b>
                                            <span>{{ $event['title'] }}</span>
                                            @if ($isMultiDay)
                                                <small>{{ $rangeLabel }}</small>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <aside class="agenda-side" aria-label="Proximos eventos">
                <button class="agenda-filter" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 3H2l8 9.5V20l4-2v-5.5L22 3z"></path></svg>
                    Filtrar
                </button>

                <div class="upcoming-panel">
                    <h3>Proximos eventos</h3>
                    <div class="upcoming-list">
                        @foreach ($upcoming as $event)
                            @php($color = $eventColors[$event['type']])
                            <div class="upcoming-item" style="color: {{ $color['text'] }};">
                                <span class="upcoming-line" style="background: {{ $color['line'] }};"></span>
                                <span>
                                    <b>{{ $event['time'] }}</b>
                                    <span>{{ $event['title'] }}</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <div class="agenda-modal" id="agendaModal" aria-hidden="true">
        <div class="agenda-dialog" role="dialog" aria-modal="true" aria-labelledby="agendaDialogTitle">
            <div class="agenda-dialog-head">
                <h3 id="agendaDialogTitle">Nuevo Evento</h3>
                <button class="agenda-close" type="button" data-agenda-modal-close aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                </button>
            </div>
            <form class="agenda-form" onsubmit="saveAgendaEvent(event);">
                <div>
                    <label for="agenda-title">Titulo</label>
                    <input id="agenda-title" type="text" name="title" placeholder="Nombre del evento" required>
                </div>
                <div class="agenda-form-row agenda-form-row--three">
                    <div>
                        <label for="agenda-date">Fecha inicial</label>
                        <input id="agenda-date" type="date" name="start_date" value="2026-07-04" required>
                    </div>
                    <div>
                        <label for="agenda-end-date">Fecha final</label>
                        <input id="agenda-end-date" type="date" name="end_date" value="2026-07-04" required>
                    </div>
                    <div>
                        <label for="agenda-time">Hora</label>
                        <input id="agenda-time" type="time" name="time" value="09:00" required>
                    </div>
                </div>
                <div>
                    <label for="agenda-type">Tipo</label>
                    <select id="agenda-type" name="type">
                        <option>Capacitacion</option>
                        <option>Entrega de equipo</option>
                        <option>Instalacion</option>
                        <option>Mantenimiento</option>
                        <option>Reunion</option>
                        <option>Congreso</option>
                    </select>
                </div>
                <div>
                    <label for="agenda-participants">Participantes</label>
                    <textarea id="agenda-participants" name="participants" placeholder="Nombre de participantes, separados por coma"></textarea>
                </div>
                <div>
                    <label for="agenda-notes">Notas</label>
                    <textarea id="agenda-notes" name="notes" placeholder="Notas del evento"></textarea>
                </div>
                <button class="agenda-save" id="agendaSaveButton" type="submit">Guardar evento</button>
            </form>
        </div>
    </div>

    <script>
        const agendaModal = document.getElementById('agendaModal');
        const agendaTitleInput = document.getElementById('agenda-title');
        const agendaDateInput = document.getElementById('agenda-date');
        const agendaEndDateInput = document.getElementById('agenda-end-date');
        const agendaTimeInput = document.getElementById('agenda-time');
        const agendaTypeInput = document.getElementById('agenda-type');
        const agendaParticipantsInput = document.getElementById('agenda-participants');
        const agendaNotesInput = document.getElementById('agenda-notes');
        const agendaDialogTitle = document.getElementById('agendaDialogTitle');
        const agendaSaveButton = document.getElementById('agendaSaveButton');
        const agendaReservedEvents = @json($eventList);
        const agendaTypeLabels = {
            training: 'Capacitacion',
            delivery: 'Entrega de equipo',
            install: 'Instalacion',
            maintenance: 'Mantenimiento',
            meeting: 'Reunion',
            congress: 'Congreso',
        };

        function findAgendaEventByDate(date) {
            return agendaReservedEvents.find((item) => date >= item.start_date && date <= item.end_date);
        }

        function rangeOverlapsReservedEvent(startDate, endDate, ignoredEventId) {
            return agendaReservedEvents.some((item) => {
                if (ignoredEventId && item.id === ignoredEventId) {
                    return false;
                }

                return startDate <= item.end_date && endDate >= item.start_date;
            });
        }

        function openAgendaModal(selectedDate, eventData) {
            const date = typeof selectedDate === 'string' ? selectedDate : '2026-07-04';
            const isEditing = !!eventData;
            const normalizedEvent = isEditing ? {
                id: eventData.id,
                startDate: eventData.startDate || eventData.start_date,
                endDate: eventData.endDate || eventData.end_date,
                title: eventData.title,
                time: eventData.time || eventData.time_value,
                type: eventData.type,
                notes: eventData.notes || '',
                participants: eventData.participants || '',
            } : null;

            agendaModal.dataset.mode = isEditing ? 'edit' : 'create';
            agendaModal.dataset.eventId = isEditing ? normalizedEvent.id : '';
            agendaDialogTitle.textContent = isEditing ? 'Editar Evento' : 'Nuevo Evento';
            agendaSaveButton.textContent = isEditing ? 'Guardar cambios' : 'Guardar evento';
            agendaTitleInput.value = isEditing ? normalizedEvent.title : '';
            agendaDateInput.value = isEditing ? normalizedEvent.startDate : date;
            agendaEndDateInput.value = isEditing ? normalizedEvent.endDate : date;
            agendaTimeInput.value = isEditing ? normalizedEvent.time : '09:00';
            agendaTypeInput.value = isEditing ? (agendaTypeLabels[normalizedEvent.type] || normalizedEvent.type) : 'Capacitacion';
            agendaParticipantsInput.value = isEditing ? normalizedEvent.participants : '';
            agendaNotesInput.value = isEditing ? normalizedEvent.notes : '';

            agendaModal.classList.add('is-open');
            agendaModal.setAttribute('aria-hidden', 'false');
            window.setTimeout(() => agendaTitleInput.focus(), 80);
        }

        function closeAgendaModal() {
            agendaModal.classList.remove('is-open');
            agendaModal.setAttribute('aria-hidden', 'true');
        }

        function saveAgendaEvent(event) {
            event.preventDefault();

            if (agendaEndDateInput.value < agendaDateInput.value) {
                if (window.showToast) {
                    window.showToast('La fecha final no puede ser menor que la fecha inicial.');
                }

                return;
            }

            if (rangeOverlapsReservedEvent(agendaDateInput.value, agendaEndDateInput.value, agendaModal.dataset.eventId)) {
                if (window.showToast) {
                    window.showToast('Ya existe un evento registrado en ese rango de fechas.');
                }

                return;
            }

            if (window.showToast) {
                const message = agendaModal.dataset.mode === 'edit'
                    ? 'Evento actualizado para el ' + agendaDateInput.value + '.'
                    : 'Evento registrado para el ' + agendaDateInput.value + '.';
                window.showToast(message);
            }

            closeAgendaModal();
        }

        document.querySelectorAll('[data-agenda-modal-open]').forEach((button) => {
            button.addEventListener('click', () => openAgendaModal());
        });

        document.querySelectorAll('[data-agenda-day]').forEach((day) => {
            day.addEventListener('click', () => {
                const currentEvent = findAgendaEventByDate(day.dataset.agendaDate);
                openAgendaModal(day.dataset.agendaDate, currentEvent);
            });
            day.addEventListener('keydown', (event) => {
                if (event.target.closest('[data-agenda-event]')) {
                    return;
                }

                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    const currentEvent = findAgendaEventByDate(day.dataset.agendaDate);
                    openAgendaModal(day.dataset.agendaDate, currentEvent);
                }
            });
        });

        document.querySelectorAll('[data-agenda-event]').forEach((item) => {
            item.addEventListener('click', (event) => {
                event.stopPropagation();
                openAgendaModal(item.dataset.agendaDate, {
                    id: item.dataset.agendaId,
                    startDate: item.dataset.agendaStartDate,
                    endDate: item.dataset.agendaEndDate,
                    title: item.dataset.agendaTitle,
                    time: item.dataset.agendaTime,
                    type: item.dataset.agendaType,
                    notes: item.dataset.agendaNotes,
                    participants: item.dataset.agendaParticipants,
                });
            });
        });

        document.querySelectorAll('[data-agenda-modal-close]').forEach((button) => {
            button.addEventListener('click', closeAgendaModal);
        });

        agendaModal.addEventListener('click', (event) => {
            if (event.target === agendaModal) {
                closeAgendaModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && agendaModal.classList.contains('is-open')) {
                closeAgendaModal();
            }
        });

        document.querySelectorAll('.agenda-view button').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.agenda-view button').forEach((item) => item.classList.remove('is-active'));
                button.classList.add('is-active');
            });
        });
    </script>
@endsection

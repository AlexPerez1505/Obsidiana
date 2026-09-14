@extends('layouts.dashboard')
@section('title', 'Agendar cita')
@section('page-title', 'Agendar cita')
@section('page-sub', 'Gestion Administrativa > Agenda > Agendar cita')

@php
    use App\Models\AgendaEvent;
    use Illuminate\Support\Carbon;

    $today = now()->toDateString();

    $editEvent = null;
    if ($eventId = request()->query('event')) {
        $editEvent = AgendaEvent::query()->find($eventId);
        abort_unless(
            $editEvent && ((int) $editEvent->created_by === (int) auth()->id() || (auth()->user()?->isAdmin() ?? false)),
            403
        );
    }

    $initialDate = $editEvent?->start_date->toDateString() ?? request()->query('date', $today);
    $initialTime = $editEvent?->start_time ? Carbon::parse($editEvent->start_time)->format('H:i') : '09:00';
    $initialDuration = (int) ($editEvent->duration_minutes ?? 60);
    $selectedParticipants = $editEvent?->participants ?? [];

    $typeLabels = [
        'training' => 'Capacitación',
        'delivery' => 'Entrega de equipo',
        'install' => 'Instalación',
        'maintenance' => 'Mantenimiento',
        'meeting' => 'Reunión',
        'congress' => 'Congreso',
    ];

    $usersList = App\Models\User::query()
        ->orderBy('name')
        ->get(['id', 'name', 'email']);

    $dbEvents = AgendaEvent::query()
        ->orderBy('start_date')
        ->orderBy('start_time')
        ->get();

    $eventList = $dbEvents->map(function ($event) {
        return [
            'id' => $event->id,
            'start_date' => $event->start_date->toDateString(),
            'end_date' => $event->end_date->toDateString(),
            'time_value' => $event->start_time ? Carbon::parse($event->start_time)->format('H:i') : '09:00',
            'duration' => (int) ($event->duration_minutes ?? 60),
            'title' => $event->title,
            'type' => $event->event_type,
        ];
    })->values();

    $initial = Carbon::parse($initialDate);
@endphp

@push('head')
<style>
    .create-page {
        display: flex;
        flex-direction: column;
        gap: 18px;
        color: #e5edf8;
    }

    .create-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
    }

    .create-head h2 {
        margin: 0;
        font-size: 26px;
        font-weight: 800;
        color: #f1f5f9;
    }

    .create-head p {
        margin: 4px 0 0;
        font-size: 14px;
        color: #8ba3c7;
    }

    .create-actions {
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
        text-decoration: none;
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

    .create-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
        gap: 16px;
        align-items: start;
    }

    .create-col {
        display: grid;
        gap: 16px;
    }

    .wizard-card {
        display: grid;
        gap: 13px;
        background: rgba(8, 17, 33, .9);
        border: 1px solid rgba(56, 189, 248, .3);
        border-radius: 14px;
        padding: 18px;
        box-shadow: 0 0 0 1px rgba(56, 189, 248, .08);
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

    .agenda-form label {
        display: block;
        margin-bottom: 5px;
        font-size: 12px;
        font-weight: 700;
        color: #9db4d4;
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

    .agenda-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .agenda-form-row--three {
        grid-template-columns: repeat(3, minmax(0, 1fr));
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

    .visibility-options {
        display: grid;
        gap: 8px;
    }

    .visibility-item {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 10px 12px;
        border: 1px solid #274366;
        border-radius: 9px;
        background: #0a1526;
        cursor: pointer;
        transition: border-color .15s ease, background .15s ease;
    }

    .visibility-item:hover {
        background: rgba(59, 130, 246, .1);
    }

    .visibility-item:has(input:checked) {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, .14);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .14);
    }

    .visibility-item input {
        width: 16px;
        height: 16px;
        accent-color: #3b82f6;
        flex: 0 0 auto;
        cursor: pointer;
    }

    .visibility-ico {
        width: 20px;
        height: 20px;
        flex: 0 0 auto;
    }

    .visibility-ico svg {
        width: 20px;
        height: 20px;
    }

    .visibility-info {
        display: grid;
        min-width: 0;
    }

    .visibility-info b {
        font-size: 13px;
        color: #f1f5f9;
    }

    .visibility-info small {
        font-size: 11px;
        color: #61789a;
    }

    .participants-search {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 8px;
        padding: 0 12px;
        border: 1px solid #274366;
        border-radius: 9px;
        background: #0a1526;
    }

    .participants-search svg {
        width: 16px;
        height: 16px;
        color: #61789a;
        flex: 0 0 auto;
    }

    .agenda-form .participants-search input,
    .participants-search input {
        flex: 1;
        min-width: 0;
        padding: 10px 0;
        border: 0;
        border-radius: 0;
        background: transparent;
        color: #e5edf8;
        font: inherit;
        font-size: 13px;
        outline: none;
        box-shadow: none;
    }

    .participants-search input:focus {
        border: 0;
        box-shadow: none;
    }

    .participants-search:focus-within {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .18);
    }

    .participants-count {
        font-size: 11px;
        font-weight: 700;
        color: #3b82f6;
        white-space: nowrap;
    }

    .participant-item[hidden] {
        display: none;
    }

    .participants-list {
        display: grid;
        gap: 6px;
        max-height: 220px;
        overflow-y: auto;
        padding: 8px;
        border: 1px solid #274366;
        border-radius: 9px;
        background: #0a1526;
    }

    .participants-list::-webkit-scrollbar {
        width: 8px;
    }

    .participants-list::-webkit-scrollbar-thumb {
        background: #274366;
        border-radius: 999px;
    }

    .participant-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        border: 1px solid transparent;
        border-radius: 9px;
        cursor: pointer;
        font-size: 13px;
        color: #dbe7f8;
        transition: background .15s ease, border-color .15s ease;
    }

    .participant-item:hover {
        background: rgba(59, 130, 246, .12);
    }

    .participant-item:has(input:checked) {
        background: rgba(59, 130, 246, .16);
        border-color: rgba(59, 130, 246, .55);
    }

    .participant-item input {
        width: 16px;
        height: 16px;
        accent-color: #3b82f6;
        flex: 0 0 auto;
        cursor: pointer;
    }

    .participant-avatar {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        background: rgba(59, 130, 246, .2);
        color: #93c5fd;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 800;
        flex: 0 0 auto;
        text-transform: uppercase;
    }

    .participant-info {
        display: grid;
        min-width: 0;
    }

    .participant-info b {
        font-size: 13px;
        color: #f1f5f9;
    }

    .participant-info small {
        font-size: 11px;
        color: #61789a;
    }

    .participants-empty {
        margin: 0;
        padding: 8px;
        text-align: center;
        font-size: 12px;
        color: #61789a;
    }

    .summary-list {
        display: grid;
        gap: 10px;
        margin: 0;
    }

    .summary-row {
        display: grid;
        grid-template-columns: 150px 1fr;
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

    .conflict-modal {
        position: fixed;
        inset: 0;
        z-index: 100;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
        background: rgba(2, 6, 23, .6);
    }

    .conflict-modal.is-open {
        display: flex;
    }

    .conflict-dialog {
        width: min(460px, 100%);
        background: #0c1a2e;
        border: 1px solid rgba(56, 189, 248, .35);
        border-radius: 14px;
        box-shadow: 0 22px 60px rgba(2, 6, 23, .55);
        padding: 24px;
        text-align: center;
        display: grid;
        gap: 12px;
    }

    .conflict-icon {
        width: 52px;
        height: 52px;
        margin: 0 auto;
        border-radius: 999px;
        background: rgba(245, 158, 11, .15);
        color: #fbbf24;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .conflict-icon svg {
        width: 26px;
        height: 26px;
    }

    .conflict-dialog h3 {
        margin: 0;
        font-size: 17px;
        color: #f1f5f9;
    }

    .conflict-dialog p {
        margin: 0;
        font-size: 13px;
        color: #9db4d4;
    }

    .conflict-question {
        font-weight: 700;
        color: #dbe7f8 !important;
    }

    .conflict-list {
        margin: 0;
        padding: 0;
        list-style: none;
        display: grid;
        gap: 6px;
        max-height: 140px;
        overflow-y: auto;
        text-align: left;
    }

    .conflict-list li {
        padding: 8px 12px;
        border: 1px solid #1b3350;
        border-radius: 8px;
        background: rgba(15, 27, 46, .6);
        font-size: 12px;
        color: #c9d8ee;
    }

    .conflict-list b {
        color: #f1f5f9;
    }

    .conflict-actions {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-top: 4px;
    }

    .create-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    :root[data-theme="light"] .create-page {
        color: #334155;
    }

    :root[data-theme="light"] .create-head h2 {
        color: #0f172a;
    }

    :root[data-theme="light"] .create-head p {
        color: #64748b;
    }

    :root[data-theme="light"] .agenda-btn--ghost {
        background: #ffffff;
        border-color: #dbe4f0;
        color: #475569;
    }

    :root[data-theme="light"] .agenda-btn--ghost:hover {
        border-color: #3b82f6;
        color: #1d4ed8;
    }

    :root[data-theme="light"] .wizard-card {
        background: #ffffff;
        border-color: #dbe4f0;
        box-shadow: 0 1px 4px rgba(15, 23, 42, .06);
    }

    :root[data-theme="light"] .wizard-card h4 {
        color: #0f172a;
    }

    :root[data-theme="light"] .agenda-form label,
    :root[data-theme="light"] .time-inputs label,
    :root[data-theme="light"] .duration-label {
        color: #64748b;
    }

    :root[data-theme="light"] .agenda-form input,
    :root[data-theme="light"] .agenda-form select,
    :root[data-theme="light"] .agenda-form textarea {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
        color-scheme: light;
    }

    :root[data-theme="light"] .agenda-form input:focus,
    :root[data-theme="light"] .agenda-form select:focus,
    :root[data-theme="light"] .agenda-form textarea:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .15);
    }

    :root[data-theme="light"] .agenda-form .participants-search input,
    :root[data-theme="light"] .participants-search input {
        background: transparent;
        border: 0;
    }

    :root[data-theme="light"] .mini-cal {
        background: #f8fafc;
        border-color: #dbe4f0;
    }

    :root[data-theme="light"] .mini-cal-nav-group {
        background: #ffffff;
        border-color: #dbe4f0;
        color: #0f172a;
    }

    :root[data-theme="light"] .mini-cal-nav-group button {
        color: #64748b;
    }

    :root[data-theme="light"] .mini-cal-dow {
        color: #64748b;
    }

    :root[data-theme="light"] .mini-cal-day {
        color: #334155;
    }

    :root[data-theme="light"] .mini-cal-day:hover {
        background: #dbeafe;
    }

    :root[data-theme="light"] .mini-cal-day.is-muted {
        color: #cbd5e1;
    }

    :root[data-theme="light"] .mini-cal-day.is-today {
        color: #2563eb;
    }

    :root[data-theme="light"] .time-col > p {
        color: #475569;
    }

    :root[data-theme="light"] .avail-bar {
        background: #e2e8f0;
        border-radius: 5px;
    }

    :root[data-theme="light"] .time-box,
    :root[data-theme="light"] .duration-btn,
    :root[data-theme="light"] .duration-value {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    :root[data-theme="light"] .time-sep,
    :root[data-theme="light"] .duration-unit {
        color: #64748b;
    }

    :root[data-theme="light"] .time-summary p {
        color: #64748b;
    }

    :root[data-theme="light"] .time-summary b {
        color: #0f172a;
    }

    :root[data-theme="light"] .avail-legend {
        color: #64748b;
    }

    :root[data-theme="light"] .visibility-item {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    :root[data-theme="light"] .visibility-item:hover {
        background: #eff6ff;
    }

    :root[data-theme="light"] .visibility-item:has(input:checked) {
        background: #eff6ff;
        border-color: #3b82f6;
    }

    :root[data-theme="light"] .visibility-info b {
        color: #0f172a;
    }

    :root[data-theme="light"] .visibility-info small {
        color: #94a3b8;
    }

    :root[data-theme="light"] .participants-search,
    :root[data-theme="light"] .participants-list {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    :root[data-theme="light"] .participants-search svg {
        color: #94a3b8;
    }

    :root[data-theme="light"] .participants-search input {
        color: #1e293b;
    }

    :root[data-theme="light"] .participant-item {
        color: #334155;
    }

    :root[data-theme="light"] .participant-item:hover {
        background: #eff6ff;
    }

    :root[data-theme="light"] .participant-item:has(input:checked) {
        background: #dbeafe;
        border-color: #3b82f6;
    }

    :root[data-theme="light"] .participant-info b {
        color: #0f172a;
    }

    :root[data-theme="light"] .participant-info small,
    :root[data-theme="light"] .participants-empty {
        color: #94a3b8;
    }

    :root[data-theme="light"] .participants-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
    }

    :root[data-theme="light"] .summary-row {
        background: #f8fafc;
        border-color: #e2e8f0;
    }

    :root[data-theme="light"] .summary-row dt {
        color: #64748b;
    }

    :root[data-theme="light"] .summary-row dd {
        color: #0f172a;
    }

    :root[data-theme="light"] .conflict-dialog {
        background: #ffffff;
        border-color: #cbd5e1;
        box-shadow: 0 22px 60px rgba(15, 23, 42, .25);
    }

    :root[data-theme="light"] .conflict-dialog h3 {
        color: #0f172a;
    }

    :root[data-theme="light"] .conflict-dialog p {
        color: #64748b;
    }

    :root[data-theme="light"] .conflict-question {
        color: #334155 !important;
    }

    :root[data-theme="light"] .conflict-list li {
        background: #f8fafc;
        border-color: #e2e8f0;
        color: #475569;
    }

    :root[data-theme="light"] .conflict-list b {
        color: #0f172a;
    }

    @media (max-width: 860px) {
        .create-grid {
            grid-template-columns: 1fr;
        }

        .create-head {
            flex-direction: column;
        }

        .create-actions,
        .create-actions .agenda-btn {
            width: 100%;
        }

        .agenda-form-row,
        .agenda-form-row--three {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    <form method="POST" action="{{ $editEvent ? route('admin.agenda.update', $editEvent) : route('admin.agenda.store') }}" id="agendaForm" class="create-page agenda-form">
        @csrf
        @if ($editEvent)
            @method('PUT')
        @endif
        <div class="create-head">
            <div>
                <h2>{{ $editEvent ? 'Reprogramar cita' : 'Agendar cita' }}</h2>
                <p>Completa los datos y selecciona la fecha y hora del procedimiento</p>
            </div>

            <div class="create-actions">
                <a class="agenda-btn agenda-btn--ghost" href="{{ route('admin.agenda.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m6-6l-6 6 6 6"></path></svg>
                    Volver a la agenda
                </a>
            </div>
        </div>

        <div class="create-grid">
            <div class="create-col">
                <div class="wizard-card">
                    <h4><span class="wizard-card-num">1</span> Datos de la cita</h4>
                    <div>
                        <label for="agenda-title">Título</label>
                        <input id="agenda-title" type="text" name="title" placeholder="Nombre de la cita o procedimiento" value="{{ old('title', $editEvent?->title) }}">
                    </div>
                    <div class="agenda-form-row agenda-form-row--three">
                        <div>
                            <label for="agenda-date">Fecha inicial</label>
                            <input id="agenda-date" type="date" name="start_date" value="{{ $initialDate }}">
                        </div>
                        <div>
                            <label for="agenda-end-date">Fecha final</label>
                            <input id="agenda-end-date" type="date" name="end_date" value="{{ $editEvent?->end_date->toDateString() ?? $initialDate }}">
                        </div>
                        <div>
                            <label for="agenda-time">Hora</label>
                            <input id="agenda-time" type="time" name="time" value="{{ $initialTime }}">
                        </div>
                    </div>
                    <div>
                        <label for="agenda-type">Tipo</label>
                        <select id="agenda-type" name="type">
                            @foreach ($typeLabels as $type => $label)
                                <option value="{{ $type }}" @selected($type === ($editEvent->event_type ?? 'meeting'))>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Visibilidad de la cita</label>
                        <div class="visibility-options">
                            <label class="visibility-item">
                                <input type="radio" name="visibility" value="publico" @checked(($editEvent->visibility ?? 'publico') === 'publico')>
                                <span class="visibility-ico" style="color:#22c55e;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"></path></svg>
                                </span>
                                <span class="visibility-info"><b>Todos</b><small>Cualquier usuario puede verla</small></span>
                            </label>
                            <label class="visibility-item">
                                <input type="radio" name="visibility" value="participantes" @checked(($editEvent->visibility ?? '') === 'participantes')>
                                <span class="visibility-ico" style="color:#f59e0b;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                </span>
                                <span class="visibility-info"><b>Solo participantes</b><small>Los seleccionados y el creador</small></span>
                            </label>
                            <label class="visibility-item">
                                <input type="radio" name="visibility" value="privado" @checked(($editEvent->visibility ?? '') === 'privado')>
                                <span class="visibility-ico" style="color:#a855f7;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>
                                </span>
                                <span class="visibility-info"><b>Solo yo</b><small>Exclusivo para quien la crea</small></span>
                            </label>
                        </div>
                    </div>
                    <div id="participantsSection" hidden>
                        <label for="agenda-participant-search">Participantes</label>
                        <div class="participants-search">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.35-4.35"></path></svg>
                            <input id="agenda-participant-search" type="text" placeholder="Buscar usuario por nombre o correo...">
                            <span class="participants-count" id="participantsCount"></span>
                        </div>
                        <div class="participants-list" id="agendaParticipants">
                            @forelse ($usersList as $user)
                                <label class="participant-item">
                                    <input type="checkbox" name="participants[]" value="{{ $user->id }}" data-participant-name="{{ $user->name }}" @checked(in_array((int) $user->id, array_map('intval', $selectedParticipants)))>
                                    <span class="participant-avatar">{{ mb_substr($user->name, 0, 1) }}</span>
                                    <span class="participant-info">
                                        <b>{{ $user->name }}</b>
                                        <small>{{ $user->email }}</small>
                                    </span>
                                </label>
                            @empty
                                <p class="participants-empty">Aún no hay usuarios registrados.</p>
                            @endforelse
                            <p class="participants-empty" id="participantsNoResults" hidden>Sin resultados para esa búsqueda.</p>
                        </div>
                    </div>
                </div>

                <div class="wizard-card">
                    <h4><span class="wizard-card-num">3</span> Motivo y observaciones</h4>
                    <div>
                        <label for="agenda-reason">Motivo</label>
                        <input id="agenda-reason" type="text" name="reason" placeholder="Motivo" value="{{ old('reason', $editEvent?->reason) }}">
                    </div>
                    <div>
                        <label for="agenda-notes">Observaciones</label>
                        <textarea id="agenda-notes" name="notes" placeholder="Observaciones o notas de la cita">{{ old('notes', $editEvent?->notes) }}</textarea>
                    </div>
                </div>

                <div class="wizard-card">
                    <h4><span class="wizard-card-num">4</span> Confirmación de la cita</h4>
                    <dl class="summary-list" id="summaryList"></dl>
                </div>
            </div>

            <div class="wizard-card">
                <h4><span class="wizard-card-num">2</span> Selección de fecha y hora</h4>
                <div class="mini-cal">
                    <div class="mini-cal-nav">
                        <span class="mini-cal-nav-group">
                            <button type="button" data-cal-year="-1" aria-label="Año anterior">‹</button>
                            <span id="calYearLabel">{{ $initial->year }}</span>
                            <button type="button" data-cal-year="1" aria-label="Año siguiente">›</button>
                        </span>
                        <span class="mini-cal-nav-group">
                            <button type="button" data-cal-month="-1" aria-label="Mes anterior">‹</button>
                            <span id="calMonthLabel">{{ ucfirst($initial->locale('es')->translatedFormat('F')) }}</span>
                            <button type="button" data-cal-month="1" aria-label="Mes siguiente">›</button>
                        </span>
                    </div>
                    <div class="mini-cal-grid" id="miniCalGrid"></div>
                </div>

                <div class="time-col">
                    <p>Horarios disponibles — <span id="availDateLabel">--/--/----</span></p>
                    <div class="avail-bar" id="availBar" aria-hidden="true"></div>

                    <div class="time-inputs">
                        <div>
                            <label for="agenda-hour">Hora</label>
                            <input class="time-box" id="agenda-hour" type="number" min="0" max="23" value="{{ substr($initialTime, 0, 2) }}">
                        </div>
                        <span class="time-sep">:</span>
                        <div>
                            <label for="agenda-min">Min</label>
                            <input class="time-box" id="agenda-min" type="number" min="0" max="59" value="{{ substr($initialTime, 3, 2) }}">
                        </div>
                    </div>

                    <div id="durationSection">
                        <span class="duration-label">Duración</span>
                        <div class="duration-row">
                            <button class="duration-btn" type="button" data-duration="-15" aria-label="Menos duración">−</button>
                            <span class="duration-value" id="agendaDuration">{{ $initialDuration }}</span>
                            <span class="duration-unit">min</span>
                            <button class="duration-btn" type="button" data-duration="15" aria-label="Más duración">+</button>
                        </div>
                    </div>

                    <div class="time-summary">
                        <p id="endTimeRow">Termina a las <b id="endTimeLabel">10:00</b></p>
                        <span class="avail-badge is-free" id="availBadge">✓ ¡Horario disponible!</span>
                    </div>

                    <div class="avail-legend">
                        <span><i class="legend-dot" style="background:#22c55e;"></i> Libre</span>
                        <span><i class="legend-dot" style="background:#ef4444;"></i> Ocupado</span>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="duration_minutes" id="agendaDurationInput" value="{{ $initialDuration }}">

        <div class="create-footer">
            <a class="agenda-btn agenda-btn--ghost" href="{{ route('admin.agenda.index') }}">Cancelar</a>
            <button class="agenda-btn agenda-btn--primary" type="button" id="agendaSaveButton">{{ $editEvent ? 'Guardar cambios' : 'Confirmar cita' }}</button>
        </div>
    </form>

    <div class="conflict-modal" id="conflictModal" aria-hidden="true">
        <div class="conflict-dialog" role="alertdialog" aria-modal="true" aria-labelledby="conflictTitle">
            <div class="conflict-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01"></path><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"></path></svg>
            </div>
            <h3 id="conflictTitle">Horario en conflicto</h3>
            <p>Ya existe una cita que coincide con esa fecha y hora:</p>
            <ul class="conflict-list" id="conflictList"></ul>
            <p class="conflict-question">¿Deseas registrar la cita de todos modos?</p>
            <div class="conflict-actions">
                <button class="agenda-btn agenda-btn--ghost" type="button" data-conflict-close>Cancelar</button>
                <button class="agenda-btn agenda-btn--primary" type="button" id="conflictConfirm">Registrar de todos modos</button>
            </div>
        </div>
    </div>

    <script>
        const agendaTitleInput = document.getElementById('agenda-title');
        const agendaDateInput = document.getElementById('agenda-date');
        const agendaEndDateInput = document.getElementById('agenda-end-date');
        const agendaTimeInput = document.getElementById('agenda-time');
        const agendaTypeInput = document.getElementById('agenda-type');
        const agendaParticipantsBox = document.getElementById('agendaParticipants');
        const agendaParticipantSearch = document.getElementById('agenda-participant-search');
        const participantsCount = document.getElementById('participantsCount');
        const participantsNoResults = document.getElementById('participantsNoResults');
        const agendaReasonInput = document.getElementById('agenda-reason');
        const agendaNotesInput = document.getElementById('agenda-notes');
        const agendaHourInput = document.getElementById('agenda-hour');
        const agendaMinInput = document.getElementById('agenda-min');
        const agendaDurationLabel = document.getElementById('agendaDuration');
        const endTimeLabel = document.getElementById('endTimeLabel');
        const availBadge = document.getElementById('availBadge');
        const availBar = document.getElementById('availBar');
        const availDateLabel = document.getElementById('availDateLabel');
        const summaryList = document.getElementById('summaryList');
        const miniCalGrid = document.getElementById('miniCalGrid');
        const calYearLabel = document.getElementById('calYearLabel');
        const calMonthLabel = document.getElementById('calMonthLabel');
        const agendaSaveButton = document.getElementById('agendaSaveButton');
        const agendaDurationInput = document.getElementById('agendaDurationInput');
        const agendaForm = document.getElementById('agendaForm');
        const agendaReservedEvents = @json($eventList);
        const agendaTypeLabels = @json($typeLabels);
        const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        const dowNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

        let calYear = {{ $initial->year }};
        let calMonth = {{ $initial->month - 1 }};
        let selectedDate = '{{ $initialDate }}';
        let agendaDuration = {{ $initialDuration }};
        const editingEventId = {{ $editEvent?->id ?? 'null' }};

        function pad2(value) {
            return String(value).padStart(2, '0');
        }

        function toDateString(year, month, day) {
            return year + '-' + pad2(month + 1) + '-' + pad2(day);
        }

        function formatDateEs(dateStr) {
            const parts = dateStr.split('-');
            return parts[2] + '/' + parts[1] + '/' + parts[0];
        }

        function toMinutes(timeValue) {
            const parts = (timeValue || '00:00').split(':');
            return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
        }

        function findConflictingEvents() {
            const isRange = agendaEndDateInput.value !== agendaDateInput.value;
            const newStart = toMinutes(agendaTimeInput.value);
            const newEnd = newStart + agendaDuration;

            return agendaReservedEvents.filter((item) => {
                if (editingEventId && item.id === editingEventId) {
                    return false;
                }

                const datesOverlap = agendaDateInput.value <= item.end_date && agendaEndDateInput.value >= item.start_date;

                if (! datesOverlap) {
                    return false;
                }

                if (isRange) {
                    return true;
                }

                const itemStart = toMinutes(item.time_value);
                const itemEnd = itemStart + (item.duration || 60);

                return newStart < itemEnd && newEnd > itemStart;
            });
        }

        function renderMiniCalendar() {
            calYearLabel.textContent = calYear;
            calMonthLabel.textContent = monthNames[calMonth];

            const first = new Date(calYear, calMonth, 1);
            const startOffset = (first.getDay() + 6) % 7;
            const gridStart = new Date(calYear, calMonth, 1 - startOffset);
            const todayStr = '{{ $today }}';
            let html = '';

            dowNames.forEach((dow) => {
                html += '<div class="mini-cal-dow">' + dow + '</div>';
            });

            for (let i = 0; i < 42; i++) {
                const day = new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + i);
                const dateStr = toDateString(day.getFullYear(), day.getMonth(), day.getDate());
                const classes = ['mini-cal-day'];

                if (day.getMonth() !== calMonth) {
                    classes.push('is-muted');
                }

                if (dateStr === todayStr) {
                    classes.push('is-today');
                }

                if (dateStr === selectedDate) {
                    classes.push('is-selected');
                }

                html += '<button type="button" class="' + classes.join(' ') + '" data-cal-date="' + dateStr + '">' + day.getDate() + '</button>';
            }

            miniCalGrid.innerHTML = html;

            miniCalGrid.querySelectorAll('[data-cal-date]').forEach((button) => {
                button.addEventListener('click', () => {
                    selectedDate = button.dataset.calDate;
                    agendaDateInput.value = selectedDate;
                    if (agendaEndDateInput.value < selectedDate) {
                        agendaEndDateInput.value = selectedDate;
                    }
                    renderMiniCalendar();
                    updateAvailability();
                });
            });
        }

        function updateAvailability() {
            const hour = Math.max(0, Math.min(23, parseInt(agendaHourInput.value, 10) || 0));
            const minute = Math.max(0, Math.min(59, parseInt(agendaMinInput.value, 10) || 0));
            const endMinutes = hour * 60 + minute + agendaDuration;
            const endHour = Math.floor(endMinutes / 60) % 24;
            const endMin = endMinutes % 60;

            agendaHourInput.value = pad2(hour);
            agendaMinInput.value = pad2(minute);
            agendaTimeInput.value = pad2(hour) + ':' + pad2(minute);
            agendaDateInput.value = selectedDate;

            const isRange = agendaEndDateInput.value && agendaEndDateInput.value !== agendaDateInput.value;
            document.getElementById('durationSection').hidden = isRange;
            document.getElementById('endTimeRow').hidden = isRange;

            endTimeLabel.textContent = pad2(endHour) + ':' + pad2(endMin);
            availDateLabel.textContent = formatDateEs(selectedDate);
            agendaDurationLabel.textContent = agendaDuration;
            agendaDurationInput.value = agendaDuration;

            const dayEvents = agendaReservedEvents.filter((item) => selectedDate >= item.start_date && selectedDate <= item.end_date);
            const isBusy = dayEvents.length > 0;

            let barHtml = '';
            for (let i = 0; i < 28; i++) {
                const segStart = 8 * 60 + i * 30;
                const segEnd = segStart + 30;
                const segBusy = dayEvents.some((item) => {
                    const itemStart = item.time_value ? parseInt(item.time_value.split(':')[0], 10) * 60 + parseInt(item.time_value.split(':')[1], 10) : 0;
                    return segStart < itemStart + 60 && segEnd > itemStart;
                });
                barHtml += '<span class="avail-seg' + (segBusy ? ' is-busy' : '') + '"></span>';
            }
            availBar.innerHTML = barHtml;

            if (isBusy) {
                availBadge.className = 'avail-badge is-busy';
                availBadge.textContent = 'Horario ocupado';
            } else {
                availBadge.className = 'avail-badge is-free';
                availBadge.textContent = '✓ ¡Horario disponible!';
            }

            fillSummary();
        }

        function selectedParticipantNames() {
            const section = document.getElementById('participantsSection');
            if (section && section.hidden) {
                return '';
            }

            return Array.from(agendaParticipantsBox.querySelectorAll('input[type="checkbox"]:checked'))
                .map((input) => input.dataset.participantName)
                .join(', ');
        }

        function fillSummary() {
            const typeLabel = agendaTypeLabels[agendaTypeInput.value] || agendaTypeInput.value;
            const visibilityLabels = { publico: 'Todos', participantes: 'Solo participantes', privado: 'Solo yo' };
            const visibilityValue = (agendaForm.querySelector('input[name="visibility"]:checked') || {}).value || 'publico';
            const rows = [
                ['Título', agendaTitleInput.value || '—'],
                ['Tipo', typeLabel],
                ['Fecha', formatDateEs(selectedDate) + (agendaEndDateInput.value !== selectedDate ? ' al ' + formatDateEs(agendaEndDateInput.value) : '')],
                ['Hora', agendaEndDateInput.value !== selectedDate
                    ? agendaTimeInput.value + ' (cita de varios días)'
                    : agendaTimeInput.value + ' (duración ' + agendaDuration + ' min, termina ' + endTimeLabel.textContent + ')'],
                ['Participantes', selectedParticipantNames() || '—'],
                ['Visibilidad', visibilityLabels[visibilityValue] || visibilityValue],
                ['Motivo', agendaReasonInput.value || '—'],
                ['Observaciones', agendaNotesInput.value || '—'],
            ];

            summaryList.innerHTML = rows.map((row) => {
                return '<div class="summary-row"><dt>' + row[0] + '</dt><dd>' + row[1] + '</dd></div>';
            }).join('');
        }

        function saveAgendaEvent() {
            if (agendaEndDateInput.value < agendaDateInput.value) {
                if (window.showToast) {
                    window.showToast('La fecha final no puede ser menor que la fecha inicial.');
                }

                return;
            }

            if (! agendaTitleInput.value.trim()) {
                if (window.showToast) {
                    window.showToast('El título de la cita es obligatorio.');
                }

                agendaTitleInput.focus();
                return;
            }

            const conflicts = findConflictingEvents();

            if (conflicts.length > 0) {
                openConflictModal(conflicts);
                return;
            }

            agendaForm.submit();
        }

        const conflictModal = document.getElementById('conflictModal');
        const conflictList = document.getElementById('conflictList');
        const conflictConfirm = document.getElementById('conflictConfirm');

        function openConflictModal(conflicts) {
            conflictList.innerHTML = conflicts.map((item) => {
                return '<li><b>' + item.title + '</b> · ' + formatDateEs(item.start_date) +
                    (item.start_date !== item.end_date ? ' al ' + formatDateEs(item.end_date) : '') +
                    ' · ' + (item.time_value || '') + '</li>';
            }).join('');

            conflictModal.classList.add('is-open');
            conflictModal.setAttribute('aria-hidden', 'false');
        }

        function closeConflictModal() {
            conflictModal.classList.remove('is-open');
            conflictModal.setAttribute('aria-hidden', 'true');
        }

        conflictConfirm.addEventListener('click', () => {
            closeConflictModal();
            agendaForm.submit();
        });

        document.querySelectorAll('[data-conflict-close]').forEach((button) => {
            button.addEventListener('click', closeConflictModal);
        });

        conflictModal.addEventListener('click', (event) => {
            if (event.target === conflictModal) {
                closeConflictModal();
            }
        });

        agendaSaveButton.addEventListener('click', saveAgendaEvent);

        [
            agendaTitleInput, agendaEndDateInput, agendaTypeInput,
            agendaReasonInput, agendaNotesInput,
        ].forEach((input) => {
            input.addEventListener('input', fillSummary);
            input.addEventListener('change', fillSummary);
        });

        agendaParticipantsBox.addEventListener('change', fillSummary);

        const participantsSection = document.getElementById('participantsSection');

        function updateVisibilityFields() {
            const visibilityValue = (agendaForm.querySelector('input[name="visibility"]:checked') || {}).value || 'publico';
            const showParticipants = visibilityValue === 'participantes';

            participantsSection.hidden = ! showParticipants;

            agendaParticipantsBox.querySelectorAll('input[type="checkbox"]').forEach((input) => {
                input.disabled = ! showParticipants;
            });
        }

        agendaForm.querySelectorAll('input[name="visibility"]').forEach((radio) => {
            radio.addEventListener('change', () => {
                updateVisibilityFields();
                fillSummary();
            });
        });

        updateVisibilityFields();

        function updateParticipantsCount() {
            const total = agendaParticipantsBox.querySelectorAll('input[type="checkbox"]').length;
            const checked = agendaParticipantsBox.querySelectorAll('input[type="checkbox"]:checked').length;
            participantsCount.textContent = checked + '/' + total + ' seleccionados';
        }

        agendaParticipantSearch.addEventListener('input', () => {
            const query = agendaParticipantSearch.value.trim().toLowerCase();
            let visible = 0;

            agendaParticipantsBox.querySelectorAll('.participant-item').forEach((item) => {
                const text = item.textContent.toLowerCase();
                const matches = query === '' || text.includes(query) || item.querySelector('input').checked;
                item.hidden = ! matches;
                if (matches) {
                    visible += 1;
                }
            });

            if (participantsNoResults) {
                participantsNoResults.hidden = visible > 0;
            }
        });

        agendaParticipantsBox.addEventListener('change', updateParticipantsCount);
        updateParticipantsCount();

        document.querySelectorAll('[data-cal-year]').forEach((button) => {
            button.addEventListener('click', () => {
                calYear += parseInt(button.dataset.calYear, 10);
                renderMiniCalendar();
            });
        });

        document.querySelectorAll('[data-cal-month]').forEach((button) => {
            button.addEventListener('click', () => {
                calMonth += parseInt(button.dataset.calMonth, 10);
                if (calMonth < 0) {
                    calMonth = 11;
                    calYear -= 1;
                }
                if (calMonth > 11) {
                    calMonth = 0;
                    calYear += 1;
                }
                renderMiniCalendar();
            });
        });

        document.querySelectorAll('[data-duration]').forEach((button) => {
            button.addEventListener('click', () => {
                agendaDuration = Math.max(15, Math.min(540, agendaDuration + parseInt(button.dataset.duration, 10)));
                updateAvailability();
            });
        });

        [agendaHourInput, agendaMinInput].forEach((input) => {
            input.addEventListener('change', updateAvailability);
        });

        agendaEndDateInput.addEventListener('change', updateAvailability);

        agendaDateInput.addEventListener('change', () => {
            if (agendaDateInput.value) {
                selectedDate = agendaDateInput.value;
                const parts = selectedDate.split('-');
                calYear = parseInt(parts[0], 10);
                calMonth = parseInt(parts[1], 10) - 1;
                renderMiniCalendar();
                updateAvailability();
            }
        });

        agendaTimeInput.addEventListener('change', () => {
            const parts = (agendaTimeInput.value || '09:00').split(':');
            agendaHourInput.value = parts[0] || '09';
            agendaMinInput.value = parts[1] || '00';
            updateAvailability();
        });

        renderMiniCalendar();
        updateAvailability();
    </script>
@endsection

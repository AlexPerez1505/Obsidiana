@extends('layouts.dashboard')

@section('title', 'Tareas · Marketing')
@section('page-title', 'Tareas')
@section('page-sub', 'Operación del equipo de marketing')

@php
    $avatarColors = ['green', 'orange', 'purple', 'blue'];

    $statusColors = [
        'pendiente' => 'amber',
        'en_proceso' => 'blue',
        'revision' => 'violet',
        'completada' => 'emerald',
    ];

    $priorityColors = [
        'baja' => 'emerald',
        'media' => 'amber',
        'alta' => 'rose',
    ];

    $priorityLabels = [
        'baja' => 'Baja',
        'media' => 'Media',
        'alta' => 'Alta',
    ];

    $statusLabels = [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En proceso',
        'revision' => 'Revisión',
        'completada' => 'Completada',
    ];
@endphp

@section('content')
    <style>
        :root {
            --mk-bg: #070c17;
            --mk-surface: #0f1a30;
            --mk-surface-2: #111c36;
            --mk-surface-3: #162444;
            --mk-border: rgba(90, 140, 230, 0.18);
            --mk-text: #e8eef8;
            --mk-muted: #93a4bd;
            --mk-primary: #0a84ff;
            --mk-amber: #f59e0b;
            --mk-blue: #3b82f6;
            --mk-violet: #8b5cf6;
            --mk-emerald: #10b981;
            --mk-rose: #f43f5e;
            --mk-cyan: #06b6d4;
        }

        [data-theme="light"] .kanban-erp {
            --mk-bg: #f6f7f9;
            --mk-surface: #ffffff;
            --mk-surface-2: #f7f8fa;
            --mk-surface-3: #eef2f7;
            --mk-border: #e2e8f0;
            --mk-text: #1e293b;
            --mk-muted: #64748b;
        }

        .kanban-erp {
            color: var(--mk-text);
            font-size: 14px;
        }

        /* Header */
        .kanban-header {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 28px;
        }

        .kanban-title-block {
            max-width: 680px;
        }

        .kanban-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            background: rgba(16, 185, 129, 0.12);
            color: var(--mk-emerald);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .kanban-pill::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--mk-emerald);
        }

        .kanban-title {
            font-size: 32px;
            font-weight: 800;
            margin: 0 0 8px;
            letter-spacing: -0.02em;
        }

        .kanban-subtitle {
            color: var(--mk-muted);
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
        }

        .kanban-new-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0a84ff, #7c3aed);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            box-shadow: 0 8px 22px rgba(10, 132, 255, 0.35);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            white-space: nowrap;
        }

        .kanban-new-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(10, 132, 255, 0.45);
        }

        /* Stats */
        .kanban-stats {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        @media (max-width: 1100px) {
            .kanban-stats { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 640px) {
            .kanban-stats { grid-template-columns: repeat(2, 1fr); gap: 12px; }
        }

        .kanban-stat {
            background: linear-gradient(145deg, rgba(15, 26, 48, 0.95), rgba(17, 28, 54, 0.90));
            border: 1px solid var(--mk-border);
            border-radius: 16px;
            padding: 18px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.20);
        }

        .kanban-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.30);
        }

        .kanban-stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--stat-color, var(--mk-primary)), rgba(255, 255, 255, 0.25));
            box-shadow: 0 0 18px var(--stat-color, var(--mk-primary));
        }

        .kanban-stat[data-color="amber"] { --stat-color: var(--mk-amber); }
        .kanban-stat[data-color="blue"] { --stat-color: var(--mk-blue); }
        .kanban-stat[data-color="violet"] { --stat-color: var(--mk-violet); }
        .kanban-stat[data-color="emerald"] { --stat-color: var(--mk-emerald); }
        .kanban-stat[data-color="rose"] { --stat-color: var(--mk-rose); }

        .kanban-stat-value {
            font-size: 28px;
            font-weight: 800;
            margin: 0;
        }

        .kanban-stat-label {
            font-size: 12px;
            color: var(--mk-muted);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
        }

        /* Filters */
        .kanban-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            background: linear-gradient(145deg, rgba(15, 26, 48, 0.95), rgba(17, 28, 54, 0.90));
            border: 1px solid var(--mk-border);
            border-radius: 16px;
            padding: 14px 16px;
            margin-bottom: 24px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.18);
        }

        .kanban-search {
            position: relative;
            flex: 1;
            min-width: 220px;
        }

        .kanban-search svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--mk-muted);
            pointer-events: none;
        }

        .kanban-search input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border-radius: 12px;
            border: 1px solid var(--mk-border);
            background: var(--mk-surface-2);
            color: var(--mk-text);
            font-size: 14px;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .kanban-search input::placeholder { color: var(--mk-muted); }

        .kanban-search input:focus {
            border-color: var(--mk-primary);
            box-shadow: 0 0 0 3px rgba(10, 132, 255, 0.15);
        }

        .kanban-select {
            padding: 11px 34px 11px 14px;
            border-radius: 12px;
            border: 1px solid var(--mk-border);
            background: var(--mk-surface-2);
            color: var(--mk-text);
            font-size: 14px;
            outline: none;
            min-width: 150px;
            appearance: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2393a4bd' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
        }

        .kanban-select:focus {
            border-color: var(--mk-primary);
            box-shadow: 0 0 0 3px rgba(10, 132, 255, 0.15);
        }

        .kanban-filter-actions {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }

        .kanban-btn {
            padding: 11px 16px;
            border-radius: 12px;
            border: 1px solid var(--mk-border);
            background: var(--mk-surface-2);
            color: var(--mk-text);
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s ease, transform 0.15s ease;
        }

        .kanban-btn:hover {
            background: var(--mk-surface-3);
            transform: translateY(-1px);
        }

        .kanban-btn-primary {
            background: linear-gradient(135deg, #0a84ff, #7c3aed);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 6px 18px rgba(10, 132, 255, 0.30);
        }

        .kanban-btn-primary:hover {
            background: linear-gradient(135deg, #007aff, #6d28d9);
            box-shadow: 0 8px 22px rgba(10, 132, 255, 0.40);
        }

        /* Board */
        .kanban-board {
            display: grid;
            grid-template-columns: repeat(4, minmax(260px, 1fr));
            gap: 18px;
            align-items: start;
            width: 100%;
        }

        @media (max-width: 1100px) {
            .kanban-board { grid-template-columns: repeat(2, minmax(260px, 1fr)); }
        }

        @media (max-width: 640px) {
            .kanban-board {
                grid-template-columns: 1fr;
                gap: 14px;
            }
        }

        .kanban-column {
            background: linear-gradient(180deg, rgba(15, 26, 48, 0.95), rgba(15, 26, 48, 0.85));
            border: 1px solid var(--mk-border);
            border-radius: 18px;
            min-height: 180px;
            overflow: hidden;
            box-shadow: 0 8px 26px rgba(0, 0, 0, 0.25);
        }

        .kanban-column-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 18px;
            border-bottom: 1px solid var(--mk-border);
            background: linear-gradient(135deg, var(--mk-surface-2), var(--mk-surface-3));
        }

        .kanban-column-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--column-color, var(--mk-primary));
            box-shadow: 0 0 10px var(--column-color, var(--mk-primary));
        }

        .kanban-column[data-color="amber"] { --column-color: var(--mk-amber); }
        .kanban-column[data-color="blue"] { --column-color: var(--mk-blue); }
        .kanban-column[data-color="violet"] { --column-color: var(--mk-violet); }
        .kanban-column[data-color="emerald"] { --column-color: var(--mk-emerald); }

        .kanban-column-title {
            font-size: 15px;
            font-weight: 700;
            flex: 1;
        }

        .kanban-column-count {
            min-width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--mk-surface-3);
            color: var(--mk-text);
            font-size: 12px;
            font-weight: 800;
        }

        .kanban-column-body {
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        /* Card */
        .kanban-card {
            background: linear-gradient(145deg, rgba(17, 28, 54, 0.92), rgba(22, 36, 68, 0.92));
            border: 1px solid var(--mk-border);
            border-radius: 16px;
            padding: 18px;
            position: relative;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            overflow: hidden;
            box-shadow:
                0 4px 20px rgba(0, 0, 0, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(10px);
        }

        .kanban-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background: var(--card-accent, var(--mk-primary));
            box-shadow: 0 0 14px var(--card-accent, var(--mk-primary));
        }

        .kanban-card[data-priority="baja"] { --card-accent: var(--mk-emerald); border-color: rgba(16, 185, 129, 0.35); }
        .kanban-card[data-priority="media"] { --card-accent: var(--mk-amber); border-color: rgba(245, 158, 11, 0.35); }
        .kanban-card[data-priority="alta"] { --card-accent: var(--mk-rose); border-color: rgba(244, 63, 94, 0.35); }

        .kanban-card:hover {
            transform: translateY(-4px);
            background: linear-gradient(145deg, rgba(22, 36, 68, 0.96), rgba(28, 46, 86, 0.96));
            box-shadow:
                0 12px 36px rgba(0, 0, 0, 0.40),
                0 0 0 1px rgba(255, 255, 255, 0.04),
                inset 0 1px 0 rgba(255, 255, 255, 0.06);
        }

        .kanban-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .kanban-card-title {
            font-size: 15px;
            font-weight: 700;
            line-height: 1.35;
            margin: 0;
        }

        .kanban-priority {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .kanban-priority[data-priority="baja"] { background: rgba(16, 185, 129, 0.12); color: var(--mk-emerald); }
        .kanban-priority[data-priority="media"] { background: rgba(245, 158, 11, 0.12); color: var(--mk-amber); }
        .kanban-priority[data-priority="alta"] { background: rgba(244, 63, 94, 0.12); color: var(--mk-rose); }

        .kanban-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 14px;
        }

        .kanban-tag {
            padding: 4px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            background: rgba(10, 132, 255, 0.10);
            color: var(--mk-primary);
        }

        .kanban-card-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
        }

        .kanban-due {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--mk-muted);
        }

        .kanban-due.overdue { color: var(--mk-rose); font-weight: 700; }

        .kanban-progress {
            margin-bottom: 14px;
        }

        .kanban-progress-header {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--mk-muted);
            margin-bottom: 6px;
        }

        .kanban-progress-bar {
            height: 6px;
            border-radius: 999px;
            background: var(--mk-surface-3);
            overflow: hidden;
        }

        .kanban-progress-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--mk-primary), #7c3aed);
            transition: width 0.4s ease;
        }

        .kanban-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .kanban-assignee {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .kanban-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            color: #fff;
            flex: 0 0 auto;
        }

        .kanban-avatar[data-avatar="green"] { background: linear-gradient(135deg, #16a34a, #22c55e); }
        .kanban-avatar[data-avatar="orange"] { background: linear-gradient(135deg, #ea580c, #f97316); }
        .kanban-avatar[data-avatar="purple"] { background: linear-gradient(135deg, #9333ea, #a855f7); }
        .kanban-avatar[data-avatar="blue"] { background: linear-gradient(135deg, #2563eb, #3b82f6); }

        .kanban-assignee-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .kanban-assignee-name {
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .kanban-assignee-role {
            font-size: 11px;
            color: var(--mk-muted);
        }

        .kanban-empty {
            color: var(--mk-muted);
            font-size: 13px;
            text-align: center;
            padding: 28px 10px;
        }
    </style>

    <div class="kanban-erp">
        <div class="kanban-header">
            <div class="kanban-title-block">
                <span class="kanban-pill">Operación del equipo</span>
                <h1 class="kanban-title">Tareas</h1>
                <p class="kanban-subtitle">
                    Asigna responsable, prioridad y fecha límite; vincula cada tarea a una pieza del calendario.
                </p>
            </div>
            <a href="{{ route('marketing.tareas.create') }}" class="kanban-new-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nueva tarea
            </a>
        </div>

        <div class="kanban-stats">
            <div class="kanban-stat" data-color="blue">
                <div class="kanban-stat-value">{{ $stats['total'] }}</div>
                <div class="kanban-stat-label">Total tareas</div>
            </div>
            <div class="kanban-stat" data-color="amber">
                <div class="kanban-stat-value">{{ $stats['pendiente'] }}</div>
                <div class="kanban-stat-label">Pendientes</div>
            </div>
            <div class="kanban-stat" data-color="blue">
                <div class="kanban-stat-value">{{ $stats['en_proceso'] }}</div>
                <div class="kanban-stat-label">En proceso</div>
            </div>
            <div class="kanban-stat" data-color="violet">
                <div class="kanban-stat-value">{{ $stats['revision'] }}</div>
                <div class="kanban-stat-label">En revisión</div>
            </div>
            <div class="kanban-stat" data-color="emerald">
                <div class="kanban-stat-value">{{ $stats['completada'] }}</div>
                <div class="kanban-stat-label">Completadas</div>
            </div>
            <div class="kanban-stat" data-color="rose">
                <div class="kanban-stat-value">{{ $stats['overdue'] }}</div>
                <div class="kanban-stat-label">Vencidas</div>
            </div>
        </div>

        <form class="kanban-filters" method="GET" action="{{ route('marketing.tareas.index') }}">
            <div class="kanban-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="q" placeholder="Buscar tareas, etiquetas..." value="{{ $filters['q'] ?? '' }}">
            </div>

            <select name="priority" class="kanban-select">
                <option value="">Todas las prioridades</option>
                <option value="alta" {{ ($filters['priority'] ?? '') === 'alta' ? 'selected' : '' }}>Alta</option>
                <option value="media" {{ ($filters['priority'] ?? '') === 'media' ? 'selected' : '' }}>Media</option>
                <option value="baja" {{ ($filters['priority'] ?? '') === 'baja' ? 'selected' : '' }}>Baja</option>
            </select>

            <select name="user_id" class="kanban-select">
                <option value="">Todos los responsables</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>

            <select name="due" class="kanban-select">
                <option value="">Cualquier fecha</option>
                <option value="today" {{ ($filters['due'] ?? '') === 'today' ? 'selected' : '' }}>Vence hoy</option>
                <option value="week" {{ ($filters['due'] ?? '') === 'week' ? 'selected' : '' }}>Esta semana</option>
                <option value="overdue" {{ ($filters['due'] ?? '') === 'overdue' ? 'selected' : '' }}>Vencidas</option>
            </select>

            <div class="kanban-filter-actions">
                <a href="{{ route('marketing.tareas.index') }}" class="kanban-btn">Limpiar</a>
                <button type="submit" class="kanban-btn kanban-btn-primary">Filtrar</button>
            </div>
        </form>

        <div class="kanban-board">
            @foreach ($columns as $column)
                <div class="kanban-column" data-color="{{ $column['color'] }}">
                    <div class="kanban-column-header">
                        <div class="kanban-column-dot"></div>
                        <div class="kanban-column-title">{{ $column['title'] }}</div>
                        <span class="kanban-column-count">{{ $column['tasks']->count() }}</span>
                    </div>

                    <div class="kanban-column-body">
                        @forelse ($column['tasks'] as $task)
                            @php
                                $user = $task->user;
                                $initials = $user ? collect(explode(' ', $user->name))->filter()->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('') : '?';
                                $avatarColor = $user ? $avatarColors[$user->id % count($avatarColors)] : 'blue';
                                $role = $user ? ($user->isAdmin() ? 'Administrador' : 'Marketing') : 'Sin asignar';
                                $overdue = $task->due_date && $task->due_date->isPast() && $task->status !== 'completada';
                            @endphp

                            <div
                                class="kanban-card"
                                data-priority="{{ $task->priority }}"
                                data-task-id="{{ $task->id }}"
                                data-title="{{ $task->title }}"
                                data-description="{{ $task->description ?? '' }}"
                                data-status="{{ $task->status }}"
                                data-due="{{ $task->due_date?->format('Y-m-d') }}"
                                data-due-display="{{ $task->due_date?->format('d/m/Y') }}"
                                data-progress="{{ $task->progress }}"
                                data-user-id="{{ $task->user_id }}"
                                data-assignee="{{ $user?->name ?? 'Sin asignar' }}"
                                data-creator="{{ $task->creator?->name ?? '—' }}"
                                data-tags="{{ implode(', ', $task->tags ?? []) }}"
                                role="button"
                                tabindex="0"
                            >
                                <div class="kanban-card-top">
                                    <h3 class="kanban-card-title">{{ $task->title }}</h3>
                                    <span class="kanban-priority" data-priority="{{ $task->priority }}">
                                        {{ $priorityLabels[$task->priority] }}
                                    </span>
                                </div>

                                @if ($task->tags)
                                    <div class="kanban-tags">
                                        @foreach ($task->tags as $tag)
                                            <span class="kanban-tag">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="kanban-card-meta">
                                    <div class="kanban-due {{ $overdue ? 'overdue' : '' }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        {{ $task->due_date?->format('d M') }}
                                    </div>
                                    <div class="kanban-due">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                        {{ $statusLabels[$task->status] }}
                                    </div>
                                </div>

                                <div class="kanban-progress">
                                    <div class="kanban-progress-header">
                                        <span>Avance</span>
                                        <span>{{ $task->progress }}%</span>
                                    </div>
                                    <div class="kanban-progress-bar">
                                        <div class="kanban-progress-fill" style="width: {{ $task->progress }}%;"></div>
                                    </div>
                                </div>

                                <div class="kanban-card-footer">
                                    <div class="kanban-assignee">
                                        <div class="kanban-avatar" data-avatar="{{ $avatarColor }}">
                                            {{ Str::upper($initials) }}
                                        </div>
                                        <div class="kanban-assignee-info">
                                            <div class="kanban-assignee-name">{{ $user?->name ?? 'Sin asignar' }}</div>
                                            <div class="kanban-assignee-role">{{ $role }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="kanban-empty">No hay tareas en esta columna.</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <style>
        .kanban-card {
            cursor: pointer;
        }

        .task-modal {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(4px);
        }

        .task-modal.active {
            display: flex;
        }

        .task-modal__dialog {
            background: linear-gradient(145deg, rgba(15, 26, 48, 0.98), rgba(17, 28, 54, 0.95));
            border: 1px solid var(--mk-border);
            border-radius: 20px;
            width: 100%;
            max-width: 720px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
        }

        .task-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 28px;
            border-bottom: 1px solid var(--mk-border);
        }

        .task-modal__title {
            font-size: 22px;
            font-weight: 800;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .task-modal__dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--mk-primary);
            box-shadow: 0 0 10px currentColor;
        }

        .task-modal__dialog[data-phase="pendiente"] .task-modal__dot { background: var(--mk-amber); }
        .task-modal__dialog[data-phase="en_proceso"] .task-modal__dot { background: var(--mk-blue); }
        .task-modal__dialog[data-phase="revision"] .task-modal__dot { background: var(--mk-violet); }
        .task-modal__dialog[data-phase="completada"] .task-modal__dot { background: var(--mk-emerald); }

        .task-modal__close {
            background: transparent;
            border: none;
            color: var(--mk-muted);
            cursor: pointer;
            padding: 6px;
            border-radius: 8px;
            transition: color 0.15s ease, background 0.15s ease;
        }

        .task-modal__close:hover {
            color: var(--mk-text);
            background: var(--mk-surface-2);
        }

        .task-modal__read-only {
            background: rgba(245, 158, 11, 0.10);
            border: 1px solid rgba(245, 158, 11, 0.20);
            color: #f59e0b;
            font-size: 12px;
            font-weight: 700;
            padding: 12px 16px;
            margin: 20px 28px 0;
            border-radius: 12px;
        }

        .phase-panels {
            margin: 20px 28px 0;
        }

        .phase-panel {
            display: none;
            background: var(--mk-surface-2);
            border: 1px solid var(--mk-border);
            border-radius: 14px;
            padding: 16px;
            font-size: 14px;
            line-height: 1.5;
        }

        .phase-panel.active {
            display: block;
        }

        .phase-panel strong {
            color: var(--mk-text);
        }

        .task-modal__form {
            padding: 24px 28px 28px;
        }

        .tm-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 640px) {
            .tm-grid { grid-template-columns: 1fr; }
            .task-modal__dialog { border-radius: 16px; }
            .task-modal__header,
            .task-modal__form,
            .phase-panels { padding-left: 18px; padding-right: 18px; }
        }

        .tm-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .tm-group.full {
            grid-column: 1 / -1;
        }

        .tm-label {
            font-size: 12px;
            font-weight: 800;
            color: var(--mk-text);
            letter-spacing: 0.02em;
        }

        .tm-input,
        .tm-select,
        .tm-textarea {
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid var(--mk-border);
            background: var(--mk-surface-2);
            color: var(--mk-text);
            font-size: 14px;
            font-weight: 600;
            outline: none;
            width: 100%;
        }

        .tm-input:focus,
        .tm-select:focus,
        .tm-textarea:focus {
            border-color: var(--mk-primary);
            background: var(--mk-surface-3);
            box-shadow: 0 0 0 3px rgba(10, 132, 255, 0.12);
        }

        .tm-select {
            appearance: none;
            padding-right: 40px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2393a4bd' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            cursor: pointer;
        }

        .tm-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .tm-progress-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .tm-progress-input {
            flex: 1;
            -webkit-appearance: none;
            appearance: none;
            height: 8px;
            border-radius: 999px;
            background: var(--mk-surface-3);
            outline: none;
        }

        .tm-progress-input::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0a84ff, #7c3aed);
            border: 2px solid var(--mk-surface);
            cursor: pointer;
        }

        .tm-progress-value {
            min-width: 46px;
            text-align: center;
            font-weight: 800;
            padding: 6px 10px;
            border-radius: 10px;
            background: var(--mk-surface-2);
            border: 1px solid var(--mk-border);
            color: var(--mk-primary);
        }

        .tm-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--mk-border);
        }

        .tm-btn {
            padding: 11px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid var(--mk-border);
            background: var(--mk-surface-2);
            color: var(--mk-text);
            transition: background 0.2s ease;
        }

        .tm-btn:hover { background: var(--mk-surface-3); }

        .tm-btn-primary {
            background: linear-gradient(135deg, #0a84ff, #7c3aed);
            border-color: transparent;
            color: #fff;
        }
    </style>

    <div id="taskModal" class="task-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="tmDialogTitle">
        <div class="task-modal__dialog" id="taskModalDialog" data-phase="">
            <div class="task-modal__header">
                <h2 class="task-modal__title" id="tmDialogTitle">
                    <span class="task-modal__dot"></span>
                    Editar tarea
                </h2>
                <button type="button" class="task-modal__close" onclick="closeTaskModal()" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="task-modal__read-only" id="tmReadOnly" style="display: none;">
                Solo lectura: Esta tarea está asignada a <strong id="tmAssigneeName"></strong>; solo esa persona o un administrador puede editarla.
            </div>

            <div class="phase-panels">
                <div class="phase-panel" data-phase="pendiente">
                    <p>En cola. <strong>Responsable:</strong> <span class="js-p-assignee"></span> · <strong>Prioridad:</strong> <span class="js-p-priority"></span>.</p>
                </div>
                <div class="phase-panel" data-phase="en_proceso">
                    <p><strong>Avance:</strong> <span class="js-p-progress"></span>% · Fecha límite: <span class="js-p-due"></span>.</p>
                </div>
                <div class="phase-panel" data-phase="revision">
                    <p>En revisión. <strong>Responsable:</strong> <span class="js-p-assignee"></span> · Creador: <span class="js-p-creator"></span>.</p>
                </div>
                <div class="phase-panel" data-phase="completada">
                    <p>Tarea finalizada. <strong>Progreso:</strong> <span class="js-p-progress"></span>% · Fecha límite: <span class="js-p-due"></span>.</p>
                </div>
            </div>

            <form id="taskModalForm" method="POST" action="" class="task-modal__form">
                @csrf
                @method('PUT')

                <div class="tm-grid">
                    <div class="tm-group full">
                        <label class="tm-label" for="tmTitle">Título de la tarea *</label>
                        <input type="text" id="tmTitle" name="title" class="tm-input" required>
                    </div>

                    <div class="tm-group full">
                        <label class="tm-label" for="tmDescription">Descripción</label>
                        <textarea id="tmDescription" name="description" class="tm-textarea" rows="4"></textarea>
                    </div>

                    <div class="tm-group">
                        <label class="tm-label" for="tmStatus">Estado *</label>
                        <select id="tmStatus" name="status" class="tm-select" required onchange="setPhase(this.value)">
                            <option value="pendiente">Pendiente</option>
                            <option value="en_proceso">En proceso</option>
                            <option value="revision">Revisión</option>
                            <option value="completada">Completada</option>
                        </select>
                    </div>

                    <div class="tm-group">
                        <label class="tm-label" for="tmPriority">Prioridad *</label>
                        <select id="tmPriority" name="priority" class="tm-select" required>
                            <option value="baja">Baja</option>
                            <option value="media">Media</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>

                    <div class="tm-group">
                        <label class="tm-label" for="tmDueDate">Fecha límite</label>
                        <input type="date" id="tmDueDate" name="due_date" class="tm-input">
                    </div>

                    <div class="tm-group">
                        <label class="tm-label" for="tmUserId">Responsable *</label>
                        <select id="tmUserId" name="user_id" class="tm-select" required>
                            <option value="" disabled>Selecciona un responsable</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="tm-group">
                        <label class="tm-label" for="tmProgress">Avance (%)</label>
                        <div class="tm-progress-wrap">
                            <input type="range" id="tmProgress" name="progress" class="tm-progress-input" min="0" max="100" oninput="document.getElementById('tmProgressValue').textContent = this.value + '%'">
                            <span class="tm-progress-value" id="tmProgressValue">0%</span>
                        </div>
                    </div>

                    <div class="tm-group">
                        <label class="tm-label" for="tmTags">Etiquetas</label>
                        <input type="text" id="tmTags" name="tags" class="tm-input" placeholder="Flyer, Educación, Carrusel">
                    </div>
                </div>

                <div class="tm-form-actions">
                    <button type="button" class="tm-btn" onclick="closeTaskModal()">Cerrar</button>
                    <button type="submit" class="tm-btn tm-btn-primary" id="tmSubmit">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const updateUrlTemplate = '{{ route('marketing.tareas.update', ['task' => '__TASK_ID__']) }}';
        const currentUserId = {{ auth()->id() }};
        const currentIsAdmin = {{ auth()->user()?->isAdmin() ? 'true' : 'false' }};

        const priorityLabels = {
            baja: 'Baja',
            media: 'Media',
            alta: 'Alta'
        };

        let activeCreator = '';

        function formatDate(dateString) {
            if (!dateString) return 'Sin fecha';
            const [year, month, day] = dateString.split('-');
            return `${day}/${month}/${year}`;
        }

        function openTaskModal(card) {
            const modal = document.getElementById('taskModal');
            const dialog = document.getElementById('taskModalDialog');
            const form = document.getElementById('taskModalForm');

            form.action = updateUrlTemplate.replace('__TASK_ID__', card.dataset.taskId);

            document.getElementById('tmTitle').value = card.dataset.title;
            document.getElementById('tmDescription').value = card.dataset.description;
            document.getElementById('tmStatus').value = card.dataset.status;
            document.getElementById('tmPriority').value = card.dataset.priority;
            document.getElementById('tmDueDate').value = card.dataset.due;
            document.getElementById('tmProgress').value = card.dataset.progress;
            document.getElementById('tmProgressValue').textContent = card.dataset.progress + '%';
            document.getElementById('tmUserId').value = card.dataset.userId;
            document.getElementById('tmTags').value = card.dataset.tags;

            document.getElementById('tmAssigneeName').textContent = card.dataset.assignee;
            activeCreator = card.dataset.creator;

            setPhase(card.dataset.status);

            const isAssignee = parseInt(card.dataset.userId) === currentUserId;
            const readonly = !isAssignee && !currentIsAdmin;

            form.querySelectorAll('input, select, textarea').forEach(el => el.disabled = readonly);
            document.getElementById('tmSubmit').disabled = readonly;
            document.getElementById('tmReadOnly').style.display = readonly ? 'block' : 'none';

            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeTaskModal() {
            const modal = document.getElementById('taskModal');
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
        }

        function updatePhaseInfo() {
            const assigneeText = document.getElementById('tmUserId').selectedOptions[0]?.text || 'Sin asignar';

            document.querySelectorAll('.js-p-assignee').forEach(el => el.textContent = assigneeText);
            document.querySelectorAll('.js-p-creator').forEach(el => el.textContent = activeCreator || '—');
            document.querySelectorAll('.js-p-priority').forEach(el => el.textContent = priorityLabels[document.getElementById('tmPriority').value] || '');
            document.querySelectorAll('.js-p-due').forEach(el => el.textContent = formatDate(document.getElementById('tmDueDate').value));
            document.querySelectorAll('.js-p-progress').forEach(el => el.textContent = document.getElementById('tmProgress').value);
        }

        function setPhase(status) {
            const dialog = document.getElementById('taskModalDialog');
            dialog.dataset.phase = status;

            document.querySelectorAll('.phase-panel').forEach(panel => {
                panel.classList.toggle('active', panel.dataset.phase === status);
            });

            updatePhaseInfo();
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.kanban-card').forEach(card => {
                card.addEventListener('click', () => openTaskModal(card));
            });

            document.getElementById('taskModal').addEventListener('click', (e) => {
                if (e.target.id === 'taskModal') closeTaskModal();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && document.getElementById('taskModal').classList.contains('active')) {
                    closeTaskModal();
                }
            });

            ['tmPriority', 'tmDueDate', 'tmProgress', 'tmUserId'].forEach(id => {
                document.getElementById(id)?.addEventListener('change', updatePhaseInfo);
            });
        });
    </script>
@endsection

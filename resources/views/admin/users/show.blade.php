@extends('layouts.dashboard')
@section('title', $user->name)
@section('page-title', $user->name)
@section('page-sub', $user->email)

@php
    $avatarColors = [
        'A' => ['#e6ffe6', '#15803d'], 'B' => ['#e6f0ff', '#007aff'],
        'C' => ['#fff3e8', '#f97316'], 'D' => ['#f3e8ff', '#9333ea'],
        'E' => ['#ffebeb', '#ff4a4a'], 'F' => ['#e6fff7', '#0d9488'],
        'G' => ['#fff3e8', '#fb923c'], 'H' => ['#e6f0ff', '#4da3ff'],
        'I' => ['#fef9c3', '#ca8a04'], 'J' => ['#e6ffe6', '#22c55e'],
        'K' => ['#fce7f3', '#db2777'], 'L' => ['#e0f2fe', '#0284c7'],
        'M' => ['#f5f3ff', '#7c3aed'], 'N' => ['#ecfdf5', '#059669'],
        'O' => ['#fff7ed', '#ea580c'], 'P' => ['#eff6ff', '#2563eb'],
        'Q' => ['#fdf4ff', '#c026d3'], 'R' => ['#f0fdf4', '#16a34a'],
        'S' => ['#fefce8', '#a16207'], 'T' => ['#f0f9ff', '#0891b2'],
        'U' => ['#fdf2f8', '#db2777'], 'V' => ['#f0fdfa', '#0d9488'],
        'W' => ['#fefcfa', '#d946ef'], 'X' => ['#f8fafc', '#64748b'],
        'Y' => ['#fffbeb', '#d97706'], 'Z' => ['#faf5ff', '#9333ea'],
    ];

    $initials = function ($name) {
        $parts = explode(' ', trim($name));
        $i = '';
        foreach ($parts as $p) {
            if ($p !== '') $i .= strtoupper($p[0]);
            if (strlen($i) >= 2) break;
        }
        return $i ?: '?';
    };

    $firstLetter = strtoupper($user->name[0] ?? 'A');
    $colors = $avatarColors[$firstLetter] ?? ['#e6f0ff', '#007aff'];
    $init = $initials($user->name);

    $statusInfo = function ($u) {
        if ($u->isBanned()) return ['dot' => 'red', 'label' => 'Baneado'];
        if ($u->isPending()) return ['dot' => 'yellow', 'label' => 'Pendiente'];
        return ['dot' => 'green', 'label' => 'Activo'];
    };
    $si = $statusInfo($user);

    $fileIcon = function ($name) {
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        if (in_array($ext, ['pdf'])) return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>';
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';
        if (in_array($ext, ['doc','docx'])) return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>';
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>';
    };

    $fileTypeColor = function ($name) {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext === 'pdf') return '#ef4444';
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) return '#0d9488';
        if (in_array($ext, ['doc','docx'])) return '#2563eb';
        return '#64748b';
    };

    // Build calendar for current month
    $now = now();
    $now->locale('es');
    $calendarTitle = $now->translatedFormat('F Y');
    $daysInMonth = $now->daysInMonth;
    $startDay = $now->copy()->startOfMonth()->dayOfWeekIso; // 1=Mon, 7=Sun
    $shiftMap = [];
    foreach ($shiftDates as $s) {
        $day = $s->shift_date->day;
        $shiftMap[$day] = $s->status;
    }

    $dayLabels = ['L','M','M','J','V','S','D'];
@endphp

@push('head')
<style>
    .emp-layout { display: flex; gap: 20px; align-items: flex-start; }
    .emp-sidebar {
        flex: 0 0 280px; position: sticky; top: 20px;
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 16px; padding: 24px 20px; text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
    }
    .emp-sidebar-avatar-wrap { position: relative; display: inline-block; margin-bottom: 14px; }
    .emp-sidebar-avatar {
        width: 96px; height: 96px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 32px; overflow: hidden;
        border: 3px solid #94a3b8;
    }
    .emp-sidebar-status-dot {
        position: absolute; bottom: 4px; right: 4px;
        width: 18px; height: 18px; border-radius: 50%;
        border: 3px solid var(--surface);
    }
    .emp-sidebar-status-dot.green { background: #22c55e; }
    .emp-sidebar-status-dot.yellow { background: #f59e0b; }
    .emp-sidebar-status-dot.red { background: #ef4444; }
    .emp-sidebar-name { font-size: 17px; font-weight: 800; margin: 0 0 4px; }
    .emp-sidebar-title {
        font-size: 11px; font-weight: 700; letter-spacing: .06em;
        text-transform: uppercase; color: var(--primary); margin: 0 0 10px;
    }
    .emp-sidebar-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 12px; font-weight: 600; padding: 4px 10px;
        border-radius: 20px; margin-bottom: 16px;
    }
    .emp-sidebar-badge.green { background: #e6ffe6; color: #15803d; }
    .emp-sidebar-badge.yellow { background: #fef9c3; color: #a16207; }
    .emp-sidebar-badge.red { background: #ffebeb; color: #ff4a4a; }
    .emp-sidebar-badge .dot { width: 7px; height: 7px; border-radius: 50%; }
    .emp-sidebar-badge.green .dot { background: #22c55e; }
    .emp-sidebar-badge.yellow .dot { background: #f59e0b; }
    .emp-sidebar-badge.red .dot { background: #ef4444; }

    .emp-sidebar-info { text-align: left; border-top: 1px solid #94a3b8; padding-top: 16px; }
    .emp-sidebar-info-item { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .emp-sidebar-info-item:last-child { margin-bottom: 0; }
    .emp-sidebar-info-icon {
        width: 34px; height: 34px; border-radius: 9px;
        background: var(--primary-soft); color: var(--primary);
        display: flex; align-items: center; justify-content: center; flex: 0 0 auto;
        border: 1.5px solid #94a3b8;
    }
    .emp-sidebar-info-icon svg { width: 16px; height: 16px; }
    .emp-sidebar-info-text { min-width: 0; }
    .emp-sidebar-info-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin: 0; }
    .emp-sidebar-info-value { font-size: 13.5px; font-weight: 600; margin: 1px 0 0; word-break: break-all; }

    .emp-sidebar-actions { margin-top: 16px; padding-top: 16px; border-top: 1px solid #94a3b8; display: flex; flex-direction: column; gap: 8px; }
    .emp-sidebar-actions a, .emp-sidebar-actions form button {
        display: flex; align-items: center; justify-content: center; gap: 7px;
        padding: 9px 14px; border-radius: 9px; font-size: 13px; font-weight: 700;
        text-decoration: none; cursor: pointer; font-family: inherit; border: 1.5px solid #94a3b8;
        transition: background .15s, border-color .15s, box-shadow .15s;
    }
    .emp-sidebar-actions .btn-primary { background: var(--primary); color: #fff; border-color: rgba(255,255,255,.35); box-shadow: 0 2px 0 rgba(0,0,0,.12); }
    .emp-sidebar-actions .btn-primary:hover { background: var(--primary-strong); }
    .emp-sidebar-actions .btn-ghost { background: var(--surface-2); color: var(--text); border: 1px solid #94a3b8; }
    .emp-sidebar-actions .btn-ghost:hover { background: var(--surface); }
    .emp-sidebar-actions .btn-danger { background: #fee2e2; color: #dc2626; }
    .emp-sidebar-actions .btn-danger:hover { background: #fecaca; }

    .emp-main { flex: 1; min-width: 0; }
    .emp-tabs {
        display: flex; gap: 2px; border-bottom: 2px solid #94a3b8;
        margin-bottom: 20px; overflow-x: auto;
    }
    .emp-tab {
        padding: 12px 18px; font-size: 14px; font-weight: 700;
        color: var(--muted); cursor: pointer; border: 1.5px solid transparent; border-radius: 10px 10px 0 0; background: none;
        font-family: inherit; border-bottom: 3px solid transparent;
        transition: color .15s, border .15s, background .15s; white-space: nowrap;
        margin-bottom: -2px; display: flex; align-items: center; gap: 7px;
    }
    .emp-tab:hover { background: var(--surface-2); border-color: #94a3b8; }
    .emp-tab:hover { color: var(--text); }
    .emp-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
    .emp-tab svg { width: 16px; height: 16px; }

    .emp-tab-content { display: none; animation: empFade .25s ease; }
    .emp-tab-content.active { display: block; }
    @keyframes empFade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

    /* Expediente Digital */
    .doc-upload-btn {
        display: flex; align-items: center; gap: 8px;
        padding: 12px 20px; border-radius: 12px;
        background: var(--primary); color: #fff;
        font-size: 14px; font-weight: 700; border: 1.5px solid rgba(255,255,255,.35);
        cursor: pointer; font-family: inherit; margin-bottom: 16px;
        transition: background .15s; box-shadow: 0 2px 0 rgba(0,0,0,.12);
    }
    .doc-upload-btn:hover { background: var(--primary-strong); }
    .doc-upload-btn svg { width: 18px; height: 18px; }
    .doc-list { display: flex; flex-direction: column; gap: 10px; }
    .doc-item {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 16px; border: 1.5px solid #94a3b8;
        border-radius: 12px; background: var(--surface);
        transition: border .15s, box-shadow .15s;
        box-shadow: 0 1px 4px rgba(0,0,0,.03);
    }
    .doc-item:hover { border-color: var(--primary); box-shadow: 0 2px 12px rgba(0,122,255,.06); }
    .doc-icon {
        width: 42px; height: 42px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; flex: 0 0 auto;
        border: 1.5px solid currentColor;
    }
    .doc-icon svg { width: 22px; height: 22px; }
    .doc-info { flex: 1; min-width: 0; }
    .doc-name { font-size: 14px; font-weight: 700; margin: 0; }
    .doc-meta { font-size: 12px; color: var(--muted); margin: 2px 0 0; }
    .doc-actions { display: flex; gap: 8px; flex: 0 0 auto; }
    .doc-btn {
        display: flex; align-items: center; gap: 5px;
        padding: 7px 13px; border-radius: 8px;
        font-size: 12.5px; font-weight: 600; cursor: pointer;
        font-family: inherit; border: 1.5px solid #94a3b8;
        background: var(--surface); color: var(--text);
        text-decoration: none; transition: all .15s;
    }
    .doc-btn:hover { background: var(--surface-2); }
    .doc-btn svg { width: 14px; height: 14px; }
    .doc-empty {
        text-align: center; padding: 40px 20px; color: var(--muted);
        border: 2px dashed #94a3b8; border-radius: 14px;
    }
    .doc-empty svg { width: 40px; height: 40px; margin-bottom: 10px; opacity: .4; }

    /* Asistencia y Turnos */
    .shift-layout { display: grid; grid-template-columns: 280px 1fr; gap: 20px; }
    .shift-calendar {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 14px; padding: 16px;
        box-shadow: 0 1px 4px rgba(0,0,0,.03);
    }
    .shift-cal-title { font-size: 14px; font-weight: 700; margin: 0 0 12px; text-align: center; }
    .shift-cal-grid {
        display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px;
    }
    .shift-cal-day-label {
        font-size: 10px; font-weight: 700; text-align: center;
        color: var(--muted); padding: 4px 0;
    }
    .shift-cal-cell {
        aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 600; border-radius: 7px;
        background: var(--surface-2); color: var(--text);
        border: 1px solid #94a3b8;
    }
    .shift-cal-cell.empty { border: none; }
    .shift-cal-cell.empty { background: transparent; }
    .shift-cal-cell.present { background: #e6ffe6; color: #15803d; font-weight: 700; }
    .shift-cal-cell.absent { background: #fee2e2; color: #dc2626; font-weight: 700; }
    .shift-cal-cell.today { border: 2px solid var(--primary); }
    .shift-cal-legend { display: flex; gap: 14px; margin-top: 12px; justify-content: center; }
    .shift-cal-legend-item { display: flex; align-items: center; gap: 5px; font-size: 11px; color: var(--muted); }
    .shift-cal-legend-dot { width: 10px; height: 10px; border-radius: 3px; }
    .shift-cal-legend-dot.present { background: #e6ffe6; border: 1px solid #22c55e; }
    .shift-cal-legend-dot.absent { background: #fee2e2; border: 1px solid #ef4444; }

    .shift-table-wrap {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.03);
    }
    .shift-table-title { padding: 14px 16px; font-size: 14px; font-weight: 700; border-bottom: 1px solid #94a3b8; margin: 0; }
    .shift-table { width: 100%; border-collapse: collapse; }
    .shift-table th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); padding: 10px 16px; text-align: left; border-bottom: 1px solid #94a3b8; }
    .shift-table td { font-size: 13px; padding: 10px 16px; border-bottom: 1px solid #94a3b8; }
    .shift-table tr:last-child td { border-bottom: none; }
    .shift-status { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; padding: 3px 8px; border-radius: 6px; }
    .shift-status.present { background: #e6ffe6; color: #15803d; }
    .shift-status.absent { background: #fee2e2; color: #dc2626; }
    .shift-status.late { background: #fef9c3; color: #a16207; }
    .shift-empty { text-align: center; padding: 30px; color: var(--muted); font-size: 13px; }

    /* Proyectos */
    .proj-empty { text-align: center; padding: 50px 20px; color: var(--muted); border: 2px dashed #94a3b8; border-radius: 14px; }
    .proj-empty svg { width: 40px; height: 40px; margin-bottom: 10px; opacity: .4; }

    /* Auditoría */
    .audit-list { display: flex; flex-direction: column; gap: 8px; }
    .audit-item {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 16px; border: 1.5px solid #94a3b8;
        border-radius: 10px; background: var(--surface);
        box-shadow: 0 1px 4px rgba(0,0,0,.03);
    }
    .audit-icon {
        width: 34px; height: 34px; border-radius: 9px;
        background: var(--surface-2); color: var(--muted);
        display: flex; align-items: center; justify-content: center; flex: 0 0 auto;
        border: 1.5px solid #94a3b8;
    }
    .audit-icon svg { width: 16px; height: 16px; }
    .audit-info { flex: 1; }
    .audit-action { font-size: 13.5px; font-weight: 600; margin: 0; }
    .audit-time { font-size: 12px; color: var(--muted); margin: 1px 0 0; }

    @media (max-width: 900px) {
        .emp-layout { flex-direction: column; }
        .emp-sidebar { position: static; flex: none; }
        .shift-layout { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
    <div style="margin-bottom:16px;">
        <a class="link" href="{{ route('admin.users.index') }}">← Volver a usuarios</a>
    </div>

    <div class="emp-layout">
        {{-- Sidebar --}}
        <aside class="emp-sidebar">
            <div class="emp-sidebar-avatar-wrap">
                <div class="emp-sidebar-avatar">
                    @if($user->avatar)
                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" style="width:100%;height:100%;object-fit:cover;">
                    @else
                        <span style="background:{{ $colors[0] }};color:{{ $colors[1] }};width:100%;height:100%;display:flex;align-items:center;justify-content:center;">{{ $init }}</span>
                    @endif
                </div>
                <span class="emp-sidebar-status-dot {{ $si['dot'] }}"></span>
            </div>
            <h2 class="emp-sidebar-name">{{ $user->name }}</h2>
            <p class="emp-sidebar-title">{{ $user->position ?: ($user->is_admin ? 'Administrador' : 'Usuario') }}</p>
            <span class="emp-sidebar-badge {{ $si['dot'] }}">
                <span class="dot"></span>
                {{ $si['label'] }}
            </span>

            <div class="emp-sidebar-info">
                <div class="emp-sidebar-info-item">
                    <div class="emp-sidebar-info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <div class="emp-sidebar-info-text">
                        <p class="emp-sidebar-info-label">Teléfono</p>
                        <p class="emp-sidebar-info-value">{{ $user->phone ?: 'No registrado' }}</p>
                    </div>
                </div>
                <div class="emp-sidebar-info-item">
                    <div class="emp-sidebar-info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    </div>
                    <div class="emp-sidebar-info-text">
                        <p class="emp-sidebar-info-label">Nómina</p>
                        <p class="emp-sidebar-info-value">{{ $user->payroll_number ?: 'No asignado' }}</p>
                    </div>
                </div>
                <div class="emp-sidebar-info-item">
                    <div class="emp-sidebar-info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>
                    </div>
                    <div class="emp-sidebar-info-text">
                        <p class="emp-sidebar-info-label">Correo</p>
                        <p class="emp-sidebar-info-value">{{ $user->email }}</p>
                    </div>
                </div>
            </div>

            <div class="emp-sidebar-actions">
                @if ($user->isPending())
                    <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                        @csrf
                        <button type="submit" class="btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M20 6L9 17l-5-5"/></svg>
                            Aprobar acceso
                        </button>
                    </form>
                @endif

                @if ($user->isBanned())
                    <form method="POST" action="{{ route('admin.users.unban', $user) }}">
                        @csrf
                        <button type="submit" class="btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                            Reactivar cuenta
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.users.ban', $user) }}">
                        @csrf
                        <button type="submit" class="btn-danger" onclick="return confirm('¿Banear a este usuario?')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/><path d="M4.93 4.93l14.14 14.14"/></svg>
                            Banear
                        </button>
                    </form>
                @endif

                <a href="{{ route('admin.users.permissions', $user) }}" class="btn-ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    Administrar permisos
                </a>

                <form method="POST" action="{{ route('admin.users.toggleAdmin', $user) }}">
                    @csrf
                    <button type="submit" class="btn-ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 1l3 6 6 .9-4.5 4.4 1 6.7L12 16l-5.5 3 1-6.7L3 7.9 9 7z"/></svg>
                        {{ $user->is_admin ? 'Quitar admin' : 'Hacer admin' }}
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main area with tabs --}}
        <div class="emp-main">
            <div class="emp-tabs">
                <button class="emp-tab active" data-tab="expediente">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h2M8 17h2M14 13h2M14 17h2"/></svg>
                    Expediente Digital
                </button>
                <button class="emp-tab" data-tab="asistencia">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    Asistencia y Turnos
                </button>
                <button class="emp-tab" data-tab="auditoria">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Historial de Auditoría
                </button>
            </div>

            {{-- Tab: Expediente Digital --}}
            <div class="emp-tab-content active" id="tab-expediente">
                <button class="doc-upload-btn" onclick="alert('Funcionalidad de subida de documentos próximamente')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Subir Documento
                </button>

                @if($documents->isEmpty())
                    <div class="doc-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                        <p style="margin:0;font-weight:600;">No hay documentos en el expediente</p>
                        <p style="margin:4px 0 0;font-size:13px;">Sube el primer documento usando el botón superior.</p>
                    </div>
                @else
                    <div class="doc-list">
                        @foreach($documents as $doc)
                            <div class="doc-item">
                                <div class="doc-icon" style="background:{{ $fileTypeColor($doc->name) }}15;color:{{ $fileTypeColor($doc->name) }};">
                                    {!! $fileIcon($doc->name) !!}
                                </div>
                                <div class="doc-info">
                                    <p class="doc-name">{{ $doc->name }}</p>
                                    <p class="doc-meta">
                                        @if($doc->file_size)
                                            {{ number_format($doc->file_size / 1024, 1) }} KB
                                        @endif
                                        @if($doc->created_at)
                                            · Subido el {{ $doc->created_at->format('d/m/Y') }}
                                        @endif
                                    </p>
                                </div>
                                <div class="doc-actions">
                                    <a href="#" class="doc-btn" onclick="event.preventDefault();alert('Vista previa no disponible aún')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Ver
                                    </a>
                                    <a href="#" class="doc-btn" onclick="event.preventDefault();alert('Descarga no disponible aún')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                                        Descargar
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Tab: Asistencia y Turnos --}}
            <div class="emp-tab-content" id="tab-asistencia">
                <div class="shift-layout">
                    {{-- Mini calendar --}}
                    <div class="shift-calendar">
                        <p class="shift-cal-title">{{ ucfirst($calendarTitle) }}</p>
                        <div class="shift-cal-grid">
                            @foreach($dayLabels as $dl)
                                <div class="shift-cal-day-label">{{ $dl }}</div>
                            @endforeach
                            @for($i = 1; $i < $startDay; $i++)
                                <div class="shift-cal-cell empty"></div>
                            @endfor
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php
                                    $status = $shiftMap[$d] ?? null;
                                    $isToday = ($d === (int)$now->format('d'));
                                    $cls = 'shift-cal-cell';
                                    if ($status === 'present') $cls .= ' present';
                                    elseif ($status === 'absent') $cls .= ' absent';
                                    if ($isToday) $cls .= ' today';
                                @endphp
                                <div class="{{ $cls }}">{{ $d }}</div>
                            @endfor
                        </div>
                        <div class="shift-cal-legend">
                            <div class="shift-cal-legend-item">
                                <span class="shift-cal-legend-dot present"></span> Asistencia
                            </div>
                            <div class="shift-cal-legend-item">
                                <span class="shift-cal-legend-dot absent"></span> Falta
                            </div>
                        </div>
                    </div>

                    {{-- Recent shifts table --}}
                    <div class="shift-table-wrap">
                        <p class="shift-table-title">Registro de Turnos Recientes</p>
                        @if($shifts->isEmpty())
                            <div class="shift-empty">No hay registros de turnos para este usuario.</div>
                        @else
                            <table class="shift-table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Notas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($shifts as $shift)
                                        <tr>
                                            <td>{{ $shift->shift_date->format('d/m/Y') }}</td>
                                            <td>{{ $shift->time_in ? \Carbon\Carbon::parse($shift->time_in)->format('H:i') : '—' }}</td>
                                            <td>{{ $shift->time_out ? \Carbon\Carbon::parse($shift->time_out)->format('H:i') : '—' }}</td>
                                            <td>
                                                @if($shift->note)
                                                    <span class="shift-status {{ $shift->status }}">{{ $shift->note }}</span>
                                                @else
                                                    <span style="color:var(--muted);">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tab: Historial de Auditoría --}}
            <div class="emp-tab-content" id="tab-auditoria">
                @if($logs->isEmpty())
                    <div class="proj-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <p style="margin:0;font-weight:600;">No hay registros de auditoría</p>
                        <p style="margin:4px 0 0;font-size:13px;">No se han registrado actividades para este usuario.</p>
                    </div>
                @else
                    <div class="audit-list">
                        @foreach($logs as $log)
                            <div class="audit-item">
                                <div class="audit-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M21 3l-7 7"/><path d="M9 21H3v-6M3 21l7-7"/></svg>
                                </div>
                                <div class="audit-info">
                                    <p class="audit-action">Inicio de sesión desde {{ $log->ip_address ?: 'IP desconocida' }}</p>
                                    <p class="audit-time">{{ $log->created_at?->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(session('status'))
        <div style="position:fixed;bottom:20px;right:20px;background:var(--primary);color:#fff;padding:12px 20px;border-radius:10px;font-weight:600;font-size:14px;box-shadow:0 4px 20px rgba(0,0,0,.15);z-index:100;">
            {{ session('status') }}
        </div>
    @endif

    <script>
        document.querySelectorAll('.emp-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.emp-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.emp-tab-content').forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
            });
        });
    </script>
@endsection

@extends('layouts.dashboard')
@section('title', 'Control de Usuarios')
@section('page-title', 'Control de Usuarios')
@section('page-sub', 'Administra las cuentas del sistema')

@php
    $pending = $users->where('status', \App\Models\User::STATUS_PENDING);
    $approved = $users->where('status', \App\Models\User::STATUS_APPROVED);
    $banned = $users->where('status', \App\Models\User::STATUS_BANNED);

    $avatarColors = [
        'A' => ['#e6ffe6', '#15803d'],
        'B' => ['#e6f0ff', '#007aff'],
        'C' => ['#fff3e8', '#f97316'],
        'D' => ['#f3e8ff', '#9333ea'],
        'E' => ['#ffebeb', '#ff4a4a'],
        'F' => ['#e6fff7', '#0d9488'],
        'G' => ['#fff3e8', '#fb923c'],
        'H' => ['#e6f0ff', '#4da3ff'],
        'I' => ['#fef9c3', '#ca8a04'],
        'J' => ['#e6ffe6', '#22c55e'],
        'K' => ['#fce7f3', '#db2777'],
        'L' => ['#e0f2fe', '#0284c7'],
        'M' => ['#f5f3ff', '#7c3aed'],
        'N' => ['#ecfdf5', '#059669'],
        'O' => ['#fff7ed', '#ea580c'],
        'P' => ['#eff6ff', '#2563eb'],
        'Q' => ['#fdf4ff', '#c026d3'],
        'R' => ['#f0fdf4', '#16a34a'],
        'S' => ['#fefce8', '#a16207'],
        'T' => ['#f0f9ff', '#0891b2'],
        'U' => ['#fdf2f8', '#db2777'],
        'V' => ['#f0fdfa', '#0d9488'],
        'W' => ['#fefcfa', '#d946ef'],
        'X' => ['#f8fafc', '#64748b'],
        'Y' => ['#fffbeb', '#d97706'],
        'Z' => ['#faf5ff', '#9333ea'],
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

    $statusInfo = function ($user) {
        if ($user->isBanned()) return ['dot' => 'red', 'label' => 'Baneado'];
        if ($user->isPending()) return ['dot' => 'yellow', 'label' => 'Pendiente'];
        return ['dot' => 'green', 'label' => 'Activo'];
    };
@endphp

@push('head')
<style>
    /* ===== Toolbar de Control de Usuarios ===== */
    .uc-toolbar {
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .uc-search {
        position: relative; flex: 1; min-width: 240px; max-width: 420px;
    }
    .uc-toolbar .uc-search input[type="text"] {
        width: 100%; padding: 12px 14px 12px 80px !important;
        border: 3px solid #64748b; border-radius: 10px;
        font-size: 14.5px; font-family: inherit;
        background: var(--surface); color: var(--text);
        outline: none; transition: border .15s, box-shadow .15s;
    }
    .uc-toolbar .uc-search input[type="text"]:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(0,122,255,.16);
    }
    .uc-search svg {
        position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
        width: 20px; height: 20px; color: var(--muted); pointer-events: none;
        display: block;
    }

    /* ===== Stat cards ===== */
    .stat-row .card {
        border: 1.5px solid #94a3b8;
        box-shadow: 0 2px 8px rgba(0,0,0,.05);
        transition: border-color .15s, box-shadow .15s;
    }
    .stat-row .card:hover { border-color: var(--primary); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
    .stat-row .stat-ico {
        border: 1.5px solid rgba(0,0,0,.08);
    }
    .uc-filter {
        position: relative;
    }
    .uc-filter select {
        appearance: none; -webkit-appearance: none;
        padding: 11px 36px 11px 14px;
        border: 1px solid #94a3b8; border-radius: 10px;
        font-size: 14.5px; font-family: inherit; font-weight: 600;
        background: var(--surface); color: var(--text);
        cursor: pointer; outline: none; transition: border .15s;
    }
    .uc-filter select:focus { border-color: var(--primary); }
    .uc-filter::after {
        content: ''; position: absolute; right: 14px; top: 50%;
        transform: translateY(-50%) rotate(45deg);
        width: 7px; height: 7px;
        border-right: 2px solid var(--muted); border-bottom: 2px solid var(--muted);
        pointer-events: none;
    }
    .uc-spacer { flex: 1; }
    .uc-btn-add {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 10px 18px; border: 1.5px solid rgba(255,255,255,.35); border-radius: 10px;
        background: var(--primary); color: #fff;
        font-size: 14.5px; font-weight: 700; cursor: pointer;
        text-decoration: none; transition: background .15s;
        box-shadow: 0 2px 0 rgba(0,0,0,.12);
    }
    .uc-btn-add:hover { background: var(--primary-strong); }
    .uc-btn-add svg { width: 18px; height: 18px; }
    .uc-view-toggle {
        display: inline-flex; border: 1px solid #94a3b8; border-radius: 10px;
        overflow: hidden; flex: 0 0 auto;
    }
    .uc-view-toggle button {
        padding: 10px 12px; border-right: 1px solid #94a3b8; border-left: none; border-top: none; border-bottom: none; background: var(--surface);
        color: var(--muted); cursor: pointer; transition: background .15s, color .15s;
        display: flex; align-items: center; justify-content: center;
    }
    .uc-view-toggle button:last-child { border-right: none; }
    .uc-view-toggle button.active {
        background: var(--primary-soft); color: var(--primary);
    }
    .uc-view-toggle button svg { width: 18px; height: 18px; }

    /* ===== Grid de tarjetas ===== */
    .uc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 18px;
    }
    .uc-grid.uc-list-view {
        grid-template-columns: 1fr;
    }

    /* ===== Tarjeta de usuario ===== */
    .uc-card {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 16px; padding: 22px; box-shadow: 0 2px 8px rgba(0,0,0,.04);
        position: relative; transition: box-shadow .2s, transform .2s, border-color .15s;
        display: flex; flex-direction: column; gap: 14px;
    }
    .uc-card:hover {
        border-color: var(--primary);
        box-shadow: 0 8px 30px rgba(17,24,39,.10);
        transform: translateY(-2px);
    }
    .uc-card-top {
        display: flex; align-items: flex-start; gap: 14px;
    }

    /* ===== Avatar con status dot ===== */
    .uc-avatar-wrap {
        position: relative; flex: 0 0 auto;
    }
    .uc-avatar {
        width: 64px; height: 64px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 22px; overflow: hidden;
        border: 2px solid #94a3b8;
    }
    .uc-avatar img {
        width: 100%; height: 100%; object-fit: cover; display: block;
    }
    .uc-status-dot {
        position: absolute; bottom: 2px; right: 2px;
        width: 14px; height: 14px; border-radius: 50%;
        border: 3px solid var(--surface);
    }
    .uc-status-dot.green { background: #22c55e; }
    .uc-status-dot.yellow { background: #f59e0b; }
    .uc-status-dot.red { background: #ef4444; }

    /* ===== Info del usuario ===== */
    .uc-info { flex: 1; min-width: 0; }
    .uc-name {
        font-size: 16px; font-weight: 700; margin: 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .uc-role {
        font-size: 13px; color: var(--muted); margin: 3px 0 0;
    }
    .uc-status-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 12px; font-weight: 600; margin-top: 6px;
    }
    .uc-status-badge .dot {
        width: 7px; height: 7px; border-radius: 50%;
    }
    .uc-status-badge .dot.green { background: #22c55e; }
    .uc-status-badge .dot.yellow { background: #f59e0b; }
    .uc-status-badge .dot.red { background: #ef4444; }
    .uc-status-badge.active { color: #22c55e; }
    .uc-status-badge.leave { color: #f59e0b; }
    .uc-status-badge.banned { color: #ef4444; }

    /* ===== Three-dots menu ===== */
    .uc-dots {
        position: absolute; bottom: 18px; right: 18px;
        width: 32px; height: 32px; border-radius: 8px;
        border: 1.5px solid #94a3b8; background: var(--surface-2); color: var(--muted);
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: background .15s, color .15s, border-color .15s;
    }
    .uc-dots:hover { border-color: var(--primary); }
    .uc-dots:hover { background: var(--primary-soft); color: var(--primary); }
    .uc-dots svg { width: 18px; height: 18px; }
    .uc-dots-menu {
        position: absolute; bottom: 54px; right: 18px;
        background: var(--surface); border: 1px solid #94a3b8;
        border-radius: 12px; box-shadow: 0 10px 30px rgba(17,24,39,.14);
        padding: 6px; min-width: 180px; z-index: 20;
        opacity: 0; visibility: hidden; transform: translateY(6px) scale(.97);
        transform-origin: bottom right; pointer-events: none;
        transition: opacity .16s, transform .18s, visibility .16s;
    }
    .uc-dots-menu.open {
        opacity: 1; visibility: visible; transform: translateY(0) scale(1);
        pointer-events: auto;
    }
    .uc-dots-menu a, .uc-dots-menu button {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 12px; border-radius: 8px;
        font-size: 13.5px; font-weight: 600; color: var(--text);
        text-decoration: none; border: none; background: none;
        cursor: pointer; width: 100%; text-align: left; font-family: inherit;
        transition: background .12s;
    }
    .uc-dots-menu a:hover, .uc-dots-menu button:hover {
        background: var(--surface-2);
    }
    .uc-dots-menu .danger { color: var(--danger); }
    .uc-dots-menu .ok { color: var(--green); }
    .uc-dots-menu svg { width: 16px; height: 16px; flex: 0 0 auto; }

    /* ===== Contact details ===== */
    .uc-contact {
        display: flex; flex-direction: column; gap: 5px;
        font-size: 13px; color: var(--muted);
    }
    .uc-contact-row {
        display: flex; align-items: center; gap: 8px;
    }
    .uc-contact-row svg { width: 15px; height: 15px; flex: 0 0 auto; opacity: .7; }
    .uc-contact-row span {
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* ===== List view ===== */
    .uc-list-view .uc-card {
        flex-direction: row; align-items: center; gap: 18px;
        padding: 16px 22px;
    }
    .uc-list-view .uc-card-top { flex: 1; }
    .uc-list-view .uc-contact {
        flex-direction: row; gap: 20px; align-items: center;
    }
    .uc-list-view .uc-dots { position: relative; bottom: auto; right: auto; }
    .uc-list-view .uc-dots-menu {
        position: absolute; bottom: auto; top: 100%; right: 0;
        transform-origin: top right; transform: translateY(-6px) scale(.97);
    }
    .uc-list-view .uc-dots-menu.open { transform: translateY(0) scale(1); }

    .uc-empty {
        text-align: center; padding: 60px 20px; color: var(--muted);
    }
    .uc-empty svg { width: 48px; height: 48px; margin: 0 auto 14px; opacity: .4; }
    .uc-empty p { font-size: 15px; font-weight: 600; margin: 0; }

    @media (max-width: 640px) {
        .uc-grid { grid-template-columns: 1fr; }
        .uc-list-view .uc-card { flex-direction: column; align-items: flex-start; }
        .uc-list-view .uc-contact { flex-direction: column; gap: 5px; }
    }
</style>
@endpush

@section('content')
    <div class="grid stat-row" style="margin-bottom:18px;">
        <x-ui.stat-card
            :value="$users->count()"
            label="Usuarios registrados"
            color="blue"
        >
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M17 5a3 3 0 0 1 0 6"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card
            :value="$pending->count()"
            label="Pendientes de aprobar"
            color="orange"
        >
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card
            :value="$approved->count()"
            label="Usuarios activos"
            color="green"
        >
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><path d="M20 6L9 17l-5-5"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>
    </div>

    {{-- Toolbar: búsqueda, filtro, agregar, toggle vista --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="uc-toolbar">
        <div class="uc-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar usuario, nómina...">
        </div>
        <div class="uc-filter">
            <select name="status">
                <option value="">Estado: Todos</option>
                <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Activos</option>
                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pendientes</option>
                <option value="banned" {{ ($filters['status'] ?? '') === 'banned' ? 'selected' : '' }}>Baneados</option>
            </select>
        </div>
        @if($positions->isNotEmpty())
        <div class="uc-filter">
            <select name="position">
                <option value="">Puesto: Todos</option>
                @foreach($positions as $pos)
                    <option value="{{ $pos }}" {{ ($filters['position'] ?? '') === $pos ? 'selected' : '' }}>{{ $pos }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" style="display:none;">Filtrar</button>
    </form>

    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
        <div class="uc-spacer"></div>
        <a href="#" class="uc-btn-add">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Agregar usuario
        </a>
        <div class="uc-view-toggle">
            <button type="button" class="uc-view-btn active" data-view="grid" title="Vista de cuadrícula">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            </button>
            <button type="button" class="uc-view-btn" data-view="list" title="Vista de lista">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
            </button>
        </div>
    </div>

    @if($users->isEmpty())
        <x-ui.card>
            <div class="uc-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/></svg>
                <p>No se encontraron usuarios con los filtros aplicados.</p>
            </div>
        </x-ui.card>
    @else
    <div class="uc-grid" id="ucGrid">
        @foreach($users as $u)
            @php
                $si = $statusInfo($u);
                $init = $initials($u->name);
                $firstLetter = strtoupper($u->name[0] ?? 'A');
                $colors = $avatarColors[$firstLetter] ?? ['#e6f0ff', '#007aff'];
            @endphp
            <div class="uc-card">
                <div class="uc-card-top">
                    <div class="uc-avatar-wrap">
                        <div class="uc-avatar">
                            @if($u->avatar)
                                <img src="{{ $u->avatar }}" alt="{{ $u->name }}">
                            @else
                                <span style="background:{{ $colors[0] }}; color:{{ $colors[1] }}; width:100%; height:100%; display:flex; align-items:center; justify-content:center;">{{ $init }}</span>
                            @endif
                        </div>
                        <span class="uc-status-dot {{ $si['dot'] }}"></span>
                    </div>
                    <div class="uc-info">
                        <h3 class="uc-name"><a href="{{ route('admin.users.show', $u) }}" class="link" style="text-decoration:none;color:var(--text);">{{ $u->name }}</a></h3>
                        <p class="uc-role">{{ $u->position ?: ($u->is_admin ? 'Administrador' : 'Usuario') }}</p>
                        <span class="uc-status-badge {{ $si['dot'] === 'green' ? 'active' : ($si['dot'] === 'yellow' ? 'leave' : 'banned') }}">
                            <span class="dot {{ $si['dot'] }}"></span>
                            {{ $si['label'] }}
                        </span>
                    </div>
                </div>

                <div class="uc-contact">
                    @if($u->payroll_number)
                    <div class="uc-contact-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                        <span>Nómina: {{ $u->payroll_number }}</span>
                    </div>
                    @endif
                    <div class="uc-contact-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <span>{{ $u->phone ?: $u->email }}</span>
                    </div>
                </div>

                <button class="uc-dots" onclick="event.stopPropagation();toggleDotsMenu(this)" title="Acciones rápidas">
                    <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
                </button>
                <div class="uc-dots-menu">
                    <a href="{{ route('admin.users.show', $u) }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Ver detalle
                    </a>
                    <a href="{{ route('admin.users.permissions', $u) }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Permisos
                    </a>
                    @if($u->isPending())
                        <form method="POST" action="{{ route('admin.users.approve', $u) }}">
                            @csrf
                            <button type="submit" class="ok">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                Aprobar acceso
                            </button>
                        </form>
                    @endif
                    @if($u->isBanned())
                        <form method="POST" action="{{ route('admin.users.unban', $u) }}">
                            @csrf
                            <button type="submit" class="ok">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                Reactivar
                            </button>
                        </form>
                    @elseif(! $u->is_admin)
                        <form method="POST" action="{{ route('admin.users.ban', $u) }}">
                            @csrf
                            <button type="submit" class="danger">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M4.93 4.93l14.14 14.14"/></svg>
                                Banear
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <script>
        // Toggle dots menu
        function toggleDotsMenu(btn) {
            const menu = btn.nextElementSibling;
            const isOpen = menu.classList.contains('open');
            document.querySelectorAll('.uc-dots-menu.open').forEach(m => m.classList.remove('open'));
            if (!isOpen) menu.classList.add('open');
        }
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.uc-dots') && !e.target.closest('.uc-dots-menu')) {
                document.querySelectorAll('.uc-dots-menu.open').forEach(m => m.classList.remove('open'));
            }
        });

        // Toggle grid/list view
        document.querySelectorAll('.uc-view-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.uc-view-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const grid = document.getElementById('ucGrid');
                if (this.dataset.view === 'list') {
                    grid.classList.add('uc-list-view');
                } else {
                    grid.classList.remove('uc-list-view');
                }
                localStorage.setItem('uc-view', this.dataset.view);
            });
        });
        // Restore view preference
        (function() {
            const saved = localStorage.getItem('uc-view');
            if (saved === 'list') {
                document.querySelector('.uc-view-btn[data-view="list"]')?.click();
            }
        })();
    </script>
@endsection

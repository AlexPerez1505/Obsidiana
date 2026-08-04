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
    @include('admin.users.partials._styles')
@endpush

@section('content')
<div class="uc-wrap">
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

    {{-- Toolbar unificada: búsqueda, filtros, agregar y toggle de vista --}}
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
    </form>

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
</div>
@endsection

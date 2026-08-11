@extends('layouts.dashboard')
@section('title', 'Permisos de '.$user->name)
@section('page-title', 'Gestión de Permisos')
@section('page-sub', $user->name)

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

    $moduleIcons = [
        'nomina' => '<path d="M2 5h20v14H2z"/><path d="M2 10h20"/><path d="M6 15h4"/>',
        'inventario' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96 12 12.01l8.73-5.05"/><path d="M12 22V12"/>',
        'reportes' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h2"/><path d="M8 17h2"/><path d="M14 13h2"/><path d="M14 17h2"/>',
        'rrhh' => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M17 5a3 3 0 0 1 0 6"/>',
        'configuracion' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
    ];

    $levelLabels = [
        'enabled'  => 'Habilitado',
        'read_only' => 'Solo Lectura',
        'edit'     => 'Editar',
        'admin'    => 'Admin',
    ];
@endphp

@push('head')
<style>
    .perm-overlay {
        position: fixed; inset: 0; background: rgba(2,6,23,.45);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
        z-index: 90; display: flex; align-items: center; justify-content: center;
        padding: 20px; overflow-y: auto;
    }
    .perm-modal {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 20px; box-shadow: 0 24px 60px rgba(17,24,39,.22);
        width: 100%; max-width: 760px; max-height: 90vh; overflow-y: auto;
        animation: permSlideIn .3s cubic-bezier(.22,1,.36,1);
    }
    @keyframes permSlideIn {
        from { opacity: 0; transform: translateY(20px) scale(.97); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .perm-header {
        padding: 24px 28px 20px; border-bottom: 1px solid #94a3b8;
    }
    .perm-title {
        font-size: 19px; font-weight: 800; margin: 0 0 4px;
        letter-spacing: -.01em;
    }
    .perm-subtitle {
        font-size: 13.5px; color: var(--muted); margin: 0;
    }
    .perm-close {
        position: absolute; top: 20px; right: 20px;
        width: 36px; height: 36px; border-radius: 10px;
        border: 1.5px solid #94a3b8; background: var(--surface);
        color: var(--muted); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background .15s, color .15s, border-color .15s; text-decoration: none;
    }
    .perm-close:hover { border-color: var(--primary); }
    .perm-close:hover { background: var(--surface-2); color: var(--text); }
    .perm-close svg { width: 18px; height: 18px; }

    .perm-body { padding: 24px 28px; }

    .perm-profile {
        display: flex; align-items: center; gap: 14px; margin-bottom: 24px;
        padding: 16px; background: var(--surface-2); border-radius: 14px;
        border: 1.5px solid #94a3b8;
    }
    .perm-avatar-wrap { position: relative; flex: 0 0 auto; }
    .perm-avatar {
        width: 52px; height: 52px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 18px; overflow: hidden;
        border: 2.5px solid #94a3b8;
    }
    .perm-status-dot {
        position: absolute; bottom: 1px; right: 1px;
        width: 12px; height: 12px; border-radius: 50%;
        border: 2.5px solid var(--surface-2);
    }
    .perm-status-dot.green { background: #22c55e; }
    .perm-status-dot.yellow { background: #f59e0b; }
    .perm-status-dot.red { background: #ef4444; }
    .perm-profile-info { flex: 1; min-width: 0; }
    .perm-profile-name { font-size: 16px; font-weight: 700; margin: 0; }
    .perm-profile-role { font-size: 13px; color: var(--muted); margin: 2px 0 0; }
    .perm-profile-status {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 12px; font-weight: 600; margin-top: 4px;
    }
    .perm-profile-status .dot {
        width: 7px; height: 7px; border-radius: 50%;
    }
    .perm-profile-status .dot.green { background: #22c55e; }
    .perm-profile-status .dot.yellow { background: #f59e0b; }
    .perm-profile-status .dot.red { background: #ef4444; }
    .perm-profile-status.active { color: #22c55e; }
    .perm-profile-status.leave { color: #f59e0b; }
    .perm-profile-status.banned { color: #ef4444; }

    .perm-role-selector { margin-bottom: 24px; }
    .perm-role-selector label {
        font-size: 13px; font-weight: 700; margin: 0 0 8px;
        text-transform: uppercase; letter-spacing: .04em; color: var(--muted);
    }
    .perm-role-select {
        position: relative;
    }
    .perm-role-select select {
        appearance: none; -webkit-appearance: none;
        width: 100%; padding: 13px 40px 13px 16px;
        border: 2.5px solid var(--primary); border-radius: 12px;
        font-size: 15px; font-weight: 700; font-family: inherit;
        background: var(--primary-soft); color: var(--primary);
        cursor: pointer; outline: none; transition: border .15s;
    }
    .perm-role-select::after {
        content: ''; position: absolute; right: 16px; top: 50%;
        transform: translateY(-50%) rotate(45deg);
        width: 8px; height: 8px;
        border-right: 2px solid var(--primary); border-bottom: 2px solid var(--primary);
        pointer-events: none;
    }

    .perm-matrix {
        display: flex; flex-direction: column; gap: 10px;
    }
    .perm-row {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 16px; border: 1.5px solid #94a3b8;
        border-radius: 12px; background: var(--surface);
        transition: border .15s, box-shadow .15s;
        box-shadow: 0 1px 4px rgba(0,0,0,.03);
    }
    .perm-row:hover { border-color: var(--primary); box-shadow: 0 2px 12px rgba(0,122,255,.08); }
    .perm-row-icon {
        width: 40px; height: 40px; border-radius: 10px;
        background: var(--primary-soft); color: var(--primary);
        display: flex; align-items: center; justify-content: center;
        flex: 0 0 auto;
        border: 1.5px solid #94a3b8;
    }
    .perm-row-icon svg { width: 20px; height: 20px; }
    .perm-row-info { flex: 1; min-width: 0; }
    .perm-row-label { font-size: 14.5px; font-weight: 700; margin: 0; }
    .perm-row-desc { font-size: 12.5px; color: var(--muted); margin: 2px 0 0; }
    .perm-row-toggles {
        display: flex; gap: 6px; flex: 0 0 auto; flex-wrap: wrap;
    }
    .perm-toggle {
        position: relative; display: inline-flex; align-items: center;
        cursor: pointer;
    }
    .perm-toggle input {
        position: absolute; opacity: 0; width: 0; height: 0;
    }
    .perm-toggle span {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 6px 12px; border-radius: 8px;
        font-size: 12px; font-weight: 600;
        border: 1.5px solid #94a3b8; background: var(--surface);
        color: var(--muted); transition: all .15s;
        white-space: nowrap;
    }
    .perm-toggle input:checked + span {
        background: var(--primary); color: #fff;
        border-color: var(--primary);
    }
    .perm-toggle input:checked + span.read-only {
        background: #f59e0b; border-color: #f59e0b; color: #fff;
    }
    .perm-toggle input:checked + span.edit {
        background: #0d9488; border-color: #0d9488; color: #fff;
    }
    .perm-toggle input:checked + span.admin {
        background: #7c3aed; border-color: #7c3aed; color: #fff;
    }
    .perm-toggle span svg { width: 13px; height: 13px; }

    .perm-footer {
        padding: 18px 28px; border-top: 1px solid #94a3b8;
        display: flex; align-items: center; gap: 12px; justify-content: flex-end;
        position: sticky; bottom: 0; background: var(--surface);
        border-radius: 0 0 20px 20px;
    }
    .perm-btn {
        padding: 11px 24px; border: 1.5px solid #94a3b8; border-radius: 10px;
        font-size: 14.5px; font-weight: 700; cursor: pointer;
        font-family: inherit; transition: all .15s;
        text-decoration: none; display: inline-flex; align-items: center; gap: 7px;
    }
    .perm-btn-save {
        background: var(--primary); color: #fff;
        border-color: rgba(255,255,255,.35);
        box-shadow: 0 2px 0 rgba(0,0,0,.12);
    }
    .perm-btn-save:hover { background: var(--primary-strong); }
    .perm-btn-cancel {
        background: var(--surface-2); color: var(--text);
        border: 1.5px solid #94a3b8;
    }
    .perm-btn-cancel:hover { background: var(--surface); }

    .perm-status-msg {
        flex: 1; font-size: 13px; font-weight: 600; color: var(--green);
    }

    @media (max-width: 640px) {
        .perm-row { flex-direction: column; align-items: flex-start; gap: 10px; }
        .perm-row-toggles { width: 100%; }
        .perm-footer { flex-direction: column-reverse; }
        .perm-footer .perm-btn { width: 100%; justify-content: center; }
    }
</style>
@endpush

@section('content')
    <div class="perm-overlay">
        <div class="perm-modal" style="position:relative;">
            <a href="{{ route('admin.users.index') }}" class="perm-close" title="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </a>

            <div class="perm-header">
                <h2 class="perm-title">Gestion de Permisos: {{ $user->name }}</h2>
                <p class="perm-subtitle">Configura los permisos por modulo.</p>
            </div>

            <div class="perm-body">
                {{-- Profile summary --}}
                <div class="perm-profile">
                    <div class="perm-avatar-wrap">
                        <div class="perm-avatar">
                            @if($user->avatar)
                                <img src="{{ $user->avatar }}" alt="{{ $user->name }}" style="width:100%;height:100%;object-fit:cover;">
                            @else
                                <span style="background:{{ $colors[0] }};color:{{ $colors[1] }};width:100%;height:100%;display:flex;align-items:center;justify-content:center;">{{ $init }}</span>
                            @endif
                        </div>
                        <span class="perm-status-dot {{ $si['dot'] }}"></span>
                    </div>
                    <div class="perm-profile-info">
                        <h3 class="perm-profile-name">{{ $user->name }}</h3>
                        <p class="perm-profile-role">{{ $user->position ?: ($user->is_admin ? 'Administrador' : 'Usuario') }}</p>
                        <span class="perm-profile-status {{ $si['dot'] === 'green' ? 'active' : ($si['dot'] === 'yellow' ? 'leave' : 'banned') }}">
                            <span class="dot {{ $si['dot'] }}"></span>
                            {{ $si['label'] }}
                        </span>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.users.permissions.update', $user) }}">
                    @csrf

                    {{-- Permissions matrix --}}
                    <div class="perm-matrix">
                        @foreach($permissions as $p)
                            @php
                                $iconKey = strtolower(str_replace(' ', '_', $p->name));
                                $iconPath = $moduleIcons[$iconKey] ?? $moduleIcons['configuracion'];
                                $currentLevel = $userPermissions[$p->id] ?? null;
                            @endphp
                            <div class="perm-row">
                                <div class="perm-row-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $iconPath !!}</svg>
                                </div>
                                <div class="perm-row-info">
                                    <p class="perm-row-label">{{ $p->label }}</p>
                                    @if($p->description)
                                        <p class="perm-row-desc">{{ $p->description }}</p>
                                    @endif
                                </div>
                                <div class="perm-row-toggles">
                                    @foreach(['enabled', 'read_only', 'edit', 'admin'] as $level)
                                        <label class="perm-toggle">
                                            <input type="radio" name="permissions[{{ $p->id }}][level]" value="{{ $level }}"
                                                {{ $currentLevel === $level ? 'checked' : '' }}
                                                {{ $currentLevel === null ? 'disabled' : '' }}>
                                            <span class="{{ $level === 'read_only' ? 'read-only' : ($level === 'edit' ? 'edit' : ($level === 'admin' ? 'admin' : '')) }}">
                                                @if($level === 'enabled')
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                                @elseif($level === 'read_only')
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                @elseif($level === 'edit')
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                @else
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                                @endif
                                                {{ $levelLabels[$level] }}
                                            </span>
                                        </label>
                                    @endforeach
                                    <input type="hidden" name="permissions[{{ $p->id }}][id]" value="{{ $p->id }}">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="perm-footer">
                        @if(session('status'))
                            <span class="perm-status-msg">{{ session('status') }}</span>
                        @endif
                        <a href="{{ route('admin.users.index') }}" class="perm-btn perm-btn-cancel">Cancelar</a>
                        <button type="submit" class="perm-btn perm-btn-save">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.dashboard')
@section('title', 'Control de Vehículos')
@section('page-title', 'Control de Vehículos')
@section('page-sub', 'Administra la flota de vehículos del sistema')

@push('head')
<style>
    /* ===== Toolbar ===== */
    .vh-toolbar {
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .vh-search {
        position: relative; flex: 1; min-width: 240px; max-width: 420px;
    }
    .vh-toolbar .vh-search input[type="text"] {
        width: 100%; padding: 12px 14px 12px 80px !important;
        border: 3px solid #64748b; border-radius: 10px;
        font-size: 14.5px; font-family: inherit;
        background: var(--surface); color: var(--text);
        outline: none; transition: border .15s, box-shadow .15s;
    }
    .vh-toolbar .vh-search input[type="text"]:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(0,122,255,.16);
    }
    .vh-search svg {
        position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
        width: 20px; height: 20px; color: var(--muted); pointer-events: none;
        display: block;
    }
    .vh-filter select {
        appearance: none; -webkit-appearance: none;
        padding: 11px 36px 11px 14px;
        border: 2px solid #94a3b8; border-radius: 10px;
        font-size: 14.5px; font-family: inherit; font-weight: 600;
        background: var(--surface); color: var(--text);
        cursor: pointer; outline: none; transition: border .15s;
    }
    .vh-filter select:focus { border-color: var(--primary); }
    .vh-filter::after {
        content: ''; position: absolute; right: 14px; top: 50%;
        transform: translateY(-50%) rotate(45deg);
        width: 7px; height: 7px;
        border-right: 2px solid var(--muted); border-bottom: 2px solid var(--muted);
        pointer-events: none;
    }
    .vh-filter { position: relative; }
    .vh-btn-add {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 10px 18px; border: 1.5px solid rgba(255,255,255,.35); border-radius: 10px;
        background: var(--primary); color: #fff;
        font-size: 14.5px; font-weight: 700; cursor: pointer;
        text-decoration: none; transition: background .15s;
        box-shadow: 0 2px 0 rgba(0,0,0,.12);
    }
    .vh-btn-add:hover { background: var(--primary-strong); }
    .vh-btn-add svg { width: 18px; height: 18px; }

    /* ===== Stat cards ===== */
    .stat-row .card {
        border: 1.5px solid #94a3b8;
        box-shadow: 0 2px 8px rgba(0,0,0,.05);
        transition: border-color .15s, box-shadow .15s;
    }
    .stat-row .card:hover { border-color: var(--primary); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
    .stat-row .stat-ico { border: 1.5px solid rgba(0,0,0,.08); }

    /* ===== Vehicle grid ===== */
    .vh-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 18px;
    }
    .vh-card {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 16px; padding: 22px; box-shadow: 0 2px 8px rgba(0,0,0,.04);
        position: relative; transition: box-shadow .2s, transform .2s, border-color .15s;
        display: flex; flex-direction: column; gap: 14px;
    }
    .vh-card:hover {
        border-color: var(--primary);
        box-shadow: 0 8px 30px rgba(17,24,39,.10);
        transform: translateY(-2px);
    }
    .vh-card-top { display: flex; align-items: flex-start; gap: 14px; }
    .vh-card-icon {
        width: 56px; height: 56px; border-radius: 14px;
        background: var(--primary-soft); color: var(--primary);
        display: flex; align-items: center; justify-content: center; flex: 0 0 auto;
        border: 1.5px solid #94a3b8;
    }
    .vh-card-icon svg { width: 28px; height: 28px; }
    .vh-card-info { flex: 1; min-width: 0; }
    .vh-card-plate { font-size: 18px; font-weight: 800; margin: 0; }
    .vh-card-brand { font-size: 13px; color: var(--muted); margin: 2px 0 0; }
    .vh-status-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 12px; font-weight: 600; padding: 3px 10px;
        border-radius: 20px; margin-top: 6px;
        border: 1.5px solid transparent;
    }
    .vh-status-badge .dot { width: 7px; height: 7px; border-radius: 50%; }
    .vh-status-badge.active { background: #e6ffe6; color: #15803d; border-color: #22c55e; }
    .vh-status-badge.active .dot { background: #22c55e; }
    .vh-status-badge.maintenance { background: #fef9c3; color: #a16207; border-color: #f59e0b; }
    .vh-status-badge.maintenance .dot { background: #f59e0b; }
    .vh-status-badge.inactive { background: #ffebeb; color: #ff4a4a; border-color: #ef4444; }
    .vh-status-badge.inactive .dot { background: #ef4444; }

    .vh-card-details { display: flex; flex-direction: column; gap: 8px; }
    .vh-detail-row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text); }
    .vh-detail-row svg { width: 16px; height: 16px; color: var(--muted); flex: 0 0 auto; }
    .vh-detail-label { color: var(--muted); font-weight: 600; }
    .vh-detail-value { font-weight: 600; }

    .vh-empty {
        text-align: center; padding: 50px 20px; color: var(--muted);
        border: 2px dashed #94a3b8; border-radius: 14px;
    }
    .vh-empty svg { width: 40px; height: 40px; margin-bottom: 10px; opacity: .4; }

    /* ===== Modal ===== */
    .vh-overlay {
        display: none; position: fixed; inset: 0; background: rgba(2,6,23,.45);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
        z-index: 90; align-items: center; justify-content: center;
        padding: 20px; overflow-y: auto;
    }
    .vh-overlay.open { display: flex; }
    .vh-modal {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 20px; box-shadow: 0 24px 60px rgba(17,24,39,.22);
        width: 100%; max-width: 860px; max-height: 92vh; overflow-y: auto;
        animation: vhSlideIn .3s cubic-bezier(.22,1,.36,1);
        position: relative;
    }
    @keyframes vhSlideIn {
        from { opacity: 0; transform: translateY(20px) scale(.97); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .vh-modal-header {
        padding: 24px 28px 20px; border-bottom: 1.5px solid #94a3b8;
        position: sticky; top: 0; background: var(--surface); z-index: 10;
        border-radius: 20px 20px 0 0;
    }
    .vh-modal-title { font-size: 19px; font-weight: 800; margin: 0 0 4px; }
    .vh-modal-subtitle { font-size: 13.5px; color: var(--muted); margin: 0; }
    .vh-modal-close {
        position: absolute; top: 20px; right: 20px;
        width: 36px; height: 36px; border-radius: 10px;
        border: 1.5px solid #94a3b8; background: var(--surface);
        color: var(--muted); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background .15s, color .15s, border-color .15s;
    }
    .vh-modal-close:hover { background: var(--surface-2); color: var(--text); border-color: var(--primary); }
    .vh-modal-close svg { width: 18px; height: 18px; }

    .vh-modal-body { padding: 24px 28px; }

    .vh-section-title {
        font-size: 14px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .04em; color: var(--primary); margin: 0 0 14px;
        padding-bottom: 8px; border-bottom: 1.5px solid #94a3b8;
        display: flex; align-items: center; gap: 8px;
    }
    .vh-section-title svg { width: 18px; height: 18px; }
    .vh-form-section { margin-bottom: 28px; }

    .vh-form-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;
    }
    .vh-field { display: flex; flex-direction: column; }
    .vh-field label {
        font-size: 12.5px; font-weight: 700; margin: 0 0 5px;
        text-transform: uppercase; letter-spacing: .03em; color: var(--muted);
    }
    .vh-input-wrap {
        position: relative; display: flex; align-items: center;
    }
    .vh-input-wrap svg {
        position: absolute; left: 12px; width: 17px; height: 17px;
        color: var(--muted); pointer-events: none; flex: 0 0 auto;
    }
    .vh-input-wrap input, .vh-input-wrap select {
        width: 100%; padding: 10px 12px 10px 40px;
        border: 2px solid #94a3b8; border-radius: 10px;
        font-size: 14px; font-family: inherit;
        background: var(--surface); color: var(--text);
        outline: none; transition: border .15s, box-shadow .15s;
    }
    .vh-input-wrap input:focus, .vh-input-wrap select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(0,122,255,.12);
    }

    /* Drag-and-drop zone */
    .vh-drop-zone {
        border: 2.5px dashed #94a3b8; border-radius: 14px;
        padding: 30px 20px; text-align: center; cursor: pointer;
        transition: border-color .15s, background .15s;
        background: var(--surface-2);
    }
    .vh-drop-zone:hover { border-color: var(--primary); background: var(--primary-soft); }
    .vh-drop-zone svg { width: 36px; height: 36px; color: var(--muted); margin-bottom: 8px; }
    .vh-drop-zone p { margin: 0; font-size: 14px; font-weight: 600; color: var(--text); }
    .vh-drop-zone span { font-size: 12.5px; color: var(--muted); }
    .vh-photo-previews {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 14px;
    }
    .vh-photo-thumb {
        aspect-ratio: 4/3; border-radius: 10px; overflow: hidden;
        border: 2px solid #94a3b8; position: relative;
        background: var(--surface-2);
    }
    .vh-photo-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .vh-photo-badge {
        position: absolute; top: 6px; left: 6px;
        background: var(--primary); color: #fff;
        font-size: 9px; font-weight: 800; padding: 2px 7px;
        border-radius: 6px; text-transform: uppercase;
    }

    /* Document upload rows */
    .vh-doc-row {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 16px; border: 1.5px solid #94a3b8;
        border-radius: 10px; background: var(--surface);
        margin-bottom: 8px;
    }
    .vh-doc-icon {
        width: 38px; height: 38px; border-radius: 9px;
        background: var(--primary-soft); color: var(--primary);
        display: flex; align-items: center; justify-content: center; flex: 0 0 auto;
        border: 1.5px solid #94a3b8;
    }
    .vh-doc-icon svg { width: 18px; height: 18px; }
    .vh-doc-info { flex: 1; }
    .vh-doc-name { font-size: 13.5px; font-weight: 700; margin: 0; }
    .vh-doc-status { font-size: 12px; color: var(--muted); margin: 1px 0 0; }
    .vh-doc-btn {
        padding: 7px 16px; border: 1.5px solid #94a3b8; border-radius: 8px;
        background: var(--surface); color: var(--text);
        font-size: 12.5px; font-weight: 700; cursor: pointer;
        font-family: inherit; transition: all .15s;
    }
    .vh-doc-btn:hover { background: var(--primary-soft); color: var(--primary); border-color: var(--primary); }

    .vh-modal-footer {
        padding: 18px 28px; border-top: 1.5px solid #94a3b8;
        display: flex; align-items: center; gap: 12px; justify-content: flex-end;
        position: sticky; bottom: 0; background: var(--surface);
        border-radius: 0 0 20px 20px;
    }
    .vh-btn {
        padding: 11px 24px; border: 1.5px solid #94a3b8; border-radius: 10px;
        font-size: 14.5px; font-weight: 700; cursor: pointer;
        font-family: inherit; transition: all .15s;
        text-decoration: none; display: inline-flex; align-items: center; gap: 7px;
    }
    .vh-btn-save {
        background: var(--primary); color: #fff;
        border-color: rgba(255,255,255,.35);
        box-shadow: 0 2px 0 rgba(0,0,0,.12);
    }
    .vh-btn-save:hover { background: var(--primary-strong); }
    .vh-btn-cancel {
        background: var(--surface-2); color: var(--text);
        border: 1.5px solid #94a3b8;
    }
    .vh-btn-cancel:hover { background: var(--surface); }

    @media (max-width: 768px) {
        .vh-form-grid { grid-template-columns: 1fr; }
        .vh-photo-previews { grid-template-columns: repeat(2, 1fr); }
    }
</style>
@endpush

@section('content')
    {{-- Stat cards --}}
    <div class="grid stat-row" style="margin-bottom:18px;">
        <x-ui.stat-card
            :value="$vehicles->count()"
            label="Vehículos registrados"
            color="blue"
        >
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><path d="M5 17h14M3 17l1.5-5.5A2 2 0 0 1 6.4 10h11.2a2 2 0 0 1 1.9 1.5L21 17M5 17v2M19 17v2"/><circle cx="7.5" cy="17" r="1.5"/><circle cx="16.5" cy="17" r="1.5"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card
            :value="$vehicles->where('status', 'maintenance')->count()"
            label="En mantenimiento"
            color="orange"
        >
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card
            :value="$vehicles->where('status', 'active')->count()"
            label="Vehículos activos"
            color="green"
        >
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><path d="M20 6L9 17l-5-5"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>
    </div>

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('admin.vehicles.index') }}" class="vh-toolbar">
        <div class="vh-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar placa, marca, VIN...">
        </div>
        <div class="vh-filter">
            <select name="status">
                <option value="">Estado: Todos</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Activos</option>
                <option value="maintenance" {{ ($filters['status'] ?? '') === 'maintenance' ? 'selected' : '' }}>En mantenimiento</option>
                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
            </select>
        </div>
        <button type="submit" style="display:none;">Filtrar</button>
    </form>

    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
        <div style="flex:1;"></div>
        <button type="button" class="vh-btn-add" onclick="document.getElementById('vhModal').classList.add('open')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Agregar vehículo
        </button>
    </div>

    {{-- Vehicle grid --}}
    @if($vehicles->isEmpty())
        <div class="vh-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 17h14M3 17l1.5-5.5A2 2 0 0 1 6.4 10h11.2a2 2 0 0 1 1.9 1.5L21 17M5 17v2M19 17v2"/><circle cx="7.5" cy="17" r="1.5"/><circle cx="16.5" cy="17" r="1.5"/></svg>
            <p style="margin:0;font-weight:600;">No se encontraron vehículos</p>
            <p style="margin:4px 0 0;font-size:13px;">Agrega un nuevo vehículo para comenzar.</p>
        </div>
    @else
    <div class="vh-grid">
        @foreach($vehicles as $v)
            @php
                $statusInfo = match($v->status) {
                    'maintenance' => ['class' => 'maintenance', 'label' => 'En mantenimiento'],
                    'inactive'    => ['class' => 'inactive', 'label' => 'Inactivo'],
                    default       => ['class' => 'active', 'label' => 'Activo'],
                };
            @endphp
            <div class="vh-card" onclick="window.location.href='{{ route('admin.vehicles.show', $v) }}'" style="cursor:pointer;">
                <div class="vh-card-top">
                    <div class="vh-card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17h14M3 17l1.5-5.5A2 2 0 0 1 6.4 10h11.2a2 2 0 0 1 1.9 1.5L21 17M5 17v2M19 17v2"/><circle cx="7.5" cy="17" r="1.5"/><circle cx="16.5" cy="17" r="1.5"/></svg>
                    </div>
                    <div class="vh-card-info">
                        <h3 class="vh-card-plate">{{ $v->plate_number }}</h3>
                        <p class="vh-card-brand">{{ $v->brand }} {{ $v->model }} · {{ $v->year }}</p>
                        <span class="vh-status-badge {{ $statusInfo['class'] }}">
                            <span class="dot"></span>
                            {{ $statusInfo['label'] }}
                        </span>
                    </div>
                </div>
                <div class="vh-card-details">
                    <div class="vh-detail-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        <span class="vh-detail-label">Kilometraje:</span>
                        <span class="vh-detail-value">{{ number_format($v->mileage ?? 0) }} km</span>
                    </div>
                    <div class="vh-detail-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v6c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V2M3 2h18M9 14l3 3L22 7"/></svg>
                        <span class="vh-detail-label">Combustible:</span>
                        <span class="vh-detail-value">{{ $v->fuel_type ?? 'N/A' }}</span>
                    </div>
                    <div class="vh-detail-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        <span class="vh-detail-label">Próx. mantenimiento:</span>
                        <span class="vh-detail-value">{{ $v->next_maintenance?->format('d/m/Y') ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- ===== Modal: Agregar Nuevo Vehículo ===== --}}
    <div class="vh-overlay" id="vhModal">
        <div class="vh-modal">
            <button type="button" class="vh-modal-close" onclick="document.getElementById('vhModal').classList.remove('open')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>

            <div class="vh-modal-header">
                <h2 class="vh-modal-title">Agregar Nuevo Vehículo</h2>
                <p class="vh-modal-subtitle">Registra un nuevo vehículo en la flota con su información completa.</p>
            </div>

            <form method="POST" action="{{ route('admin.vehicles.store') }}">
                @csrf
                <div class="vh-modal-body">

                    {{-- Section 1: Identificación y Especificaciones --}}
                    <div class="vh-form-section">
                        <h3 class="vh-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                            Identificación y Especificaciones
                        </h3>
                        <div class="vh-form-grid">
                            <div class="vh-field">
                                <label>Número de Placa</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 12h10"/></svg>
                                    <input type="text" name="plate_number" required placeholder="ABC-123">
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Número de Serie (VIN)</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                    <input type="text" name="vin" placeholder="1HGCM82633A123456">
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Marca</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <input type="text" name="brand" placeholder="Toyota, Ford, Nissan...">
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Modelo</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <input type="text" name="model" placeholder="Hilux, Focus, Sentra...">
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Año</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                    <input type="number" name="year" placeholder="2024" min="1900" max="{{ date('Y') + 1 }}">
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Color</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
                                    <input type="text" name="color" placeholder="Blanco, Negro, Rojo...">
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Tipo de Motor</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 7H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-1M9 7V4M14 7V4M5 7V4"/></svg>
                                    <input type="text" name="engine_type" placeholder="V6, 4 cilindros...">
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Combustible</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18M3 22h12M15 8h2a2 2 0 0 1 2 2v8a2 2 0 0 0 2 2 2 2 0 0 0 2-2V9.5L19 5"/></svg>
                                    <select name="fuel_type">
                                        <option value="">Seleccionar...</option>
                                        <option value="Gasolina">Gasolina</option>
                                        <option value="Diésel">Diésel</option>
                                        <option value="Híbrido">Híbrido</option>
                                        <option value="Eléctrico">Eléctrico</option>
                                        <option value="Gas LP">Gas LP</option>
                                    </select>
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Capacidad de Carga (kg)</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                                    <input type="number" name="load_capacity" placeholder="1500" step="0.01">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Control de Fechas y Rendimiento --}}
                    <div class="vh-form-section">
                        <h3 class="vh-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M9 16l2 2 4-4"/></svg>
                            Control de Fechas y Rendimiento
                        </h3>
                        <div class="vh-form-grid">
                            <div class="vh-field">
                                <label>Kilometraje</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                    <input type="number" name="mileage" placeholder="0" min="0">
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Rendimiento (Km/L)</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h4l3-9 4 18 3-9h4"/></svg>
                                    <input type="number" name="fuel_efficiency" placeholder="12.5" step="0.01">
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Costo Llenado ($)</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                    <input type="number" name="tank_cost" placeholder="800.00" step="0.01">
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Fecha de Adquisición</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                    <input type="date" name="acquisition_date">
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Último Mantenimiento</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                    <input type="date" name="last_maintenance">
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Próximo Mantenimiento</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                    <input type="date" name="next_maintenance">
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Última Verificación</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                    <input type="date" name="last_verification">
                                </div>
                            </div>
                            <div class="vh-field">
                                <label>Próxima Verificación</label>
                                <div class="vh-input-wrap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                    <input type="date" name="next_verification">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Galería de Fotos --}}
                    <div class="vh-form-section">
                        <h3 class="vh-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            Galería de Fotos del Vehículo
                        </h3>
                        <div class="vh-drop-zone" onclick="alert('Funcionalidad de subida de fotos próximamente')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <p>Arrastra y suelta las fotos aquí</p>
                            <span>o haz clic para explorar (frente, lateral, trasera, interior)</span>
                        </div>
                        <div class="vh-photo-previews">
                            <div class="vh-photo-thumb" style="background:#f1f5f9; display:flex; align-items:center; justify-content:center;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" width="28" height="28" style="color:#cbd5e1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                <span class="vh-photo-badge">Principal</span>
                            </div>
                            <div class="vh-photo-thumb" style="background:#f1f5f9; display:flex; align-items:center; justify-content:center;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" width="28" height="28" style="color:#cbd5e1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            </div>
                            <div class="vh-photo-thumb" style="background:#f1f5f9; display:flex; align-items:center; justify-content:center;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" width="28" height="28" style="color:#cbd5e1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            </div>
                            <div class="vh-photo-thumb" style="background:#f1f5f9; display:flex; align-items:center; justify-content:center;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" width="28" height="28" style="color:#cbd5e1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            </div>
                        </div>
                    </div>

                    {{-- Section 4: Documentación Obligatoria --}}
                    <div class="vh-form-section">
                        <h3 class="vh-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15l2 2 4-4"/></svg>
                            Documentación Obligatoria
                        </h3>
                        <div class="vh-doc-row">
                            <div class="vh-doc-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 12h10"/></svg>
                            </div>
                            <div class="vh-doc-info">
                                <p class="vh-doc-name">Tarjeta de Circulación</p>
                                <p class="vh-doc-status">Sin archivo adjunto</p>
                            </div>
                            <button type="button" class="vh-doc-btn" onclick="alert('Explorar archivos próximamente')">Explorar</button>
                        </div>
                        <div class="vh-doc-row">
                            <div class="vh-doc-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                            </div>
                            <div class="vh-doc-info">
                                <p class="vh-doc-name">Verificación Vehicular</p>
                                <p class="vh-doc-status">Sin archivo adjunto</p>
                            </div>
                            <button type="button" class="vh-doc-btn" onclick="alert('Explorar archivos próximamente')">Explorar</button>
                        </div>
                        <div class="vh-doc-row">
                            <div class="vh-doc-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </div>
                            <div class="vh-doc-info">
                                <p class="vh-doc-name">Pago de Tenencia</p>
                                <p class="vh-doc-status">Sin archivo adjunto</p>
                            </div>
                            <button type="button" class="vh-doc-btn" onclick="alert('Explorar archivos próximamente')">Explorar</button>
                        </div>
                        <div class="vh-doc-row">
                            <div class="vh-doc-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </div>
                            <div class="vh-doc-info">
                                <p class="vh-doc-name">Póliza de Seguro</p>
                                <p class="vh-doc-status">Sin archivo adjunto</p>
                            </div>
                            <button type="button" class="vh-doc-btn" onclick="alert('Explorar archivos próximamente')">Explorar</button>
                        </div>
                    </div>

                    {{-- Hidden status field --}}
                    <input type="hidden" name="status" value="active">
                </div>

                <div class="vh-modal-footer">
                    <button type="button" class="vh-btn vh-btn-cancel" onclick="document.getElementById('vhModal').classList.remove('open')">Cancelar</button>
                    <button type="submit" class="vh-btn vh-btn-save">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Guardar vehículo
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(session('status'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.appToast) window.appToast.show({{ json_encode(session('status')) }});
            });
        </script>
    @endif
@endsection

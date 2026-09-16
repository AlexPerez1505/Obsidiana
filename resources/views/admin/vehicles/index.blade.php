@extends('layouts.dashboard')
@section('title', 'Control de Vehículos')
@section('page-title', 'Control de Vehículos')
@section('page-sub', 'Administra la flota de vehículos del sistema')

@push('head')
<style>
    /* ===== Toolbar ===== */
    .vh-toolbar {
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
        margin-bottom: 18px;
    }
    .vh-search {
        position: relative; flex: 1; min-width: 220px; max-width: 380px;
    }
    .vh-toolbar .vh-search input[type="text"] {
        width: 100%; padding: 10px 12px 10px 40px;
        border: 1px solid var(--border); border-radius: 8px;
        font-size: 14px; font-family: inherit;
        background: var(--surface); color: var(--text);
        outline: none; transition: border .15s, box-shadow .15s;
    }
    .vh-toolbar .vh-search input[type="text"]:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-soft);
    }
    .vh-search svg {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 17px; height: 17px; color: var(--muted); pointer-events: none;
    }
    .vh-toolbar select {
        padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;
        font-size: 14px; font-family: inherit; background: var(--surface);
        color: var(--text); cursor: pointer; outline: none;
    }

    /* ===== Vehicle grid ===== */
    .vh-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 16px;
    }
    .vh-card {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 10px; padding: 18px; box-shadow: var(--shadow);
        position: relative; transition: box-shadow .15s, border-color .15s;
        display: flex; flex-direction: column; gap: 14px; cursor: pointer;
    }
    .vh-card:hover { border-color: var(--primary); }
    .vh-card-top { display: flex; align-items: flex-start; gap: 14px; }
    .vh-card-icon {
        width: 48px; height: 48px; border-radius: 10px;
        background: var(--primary-soft); color: var(--primary);
        display: flex; align-items: center; justify-content: center; flex: 0 0 auto;
    }
    .vh-card-icon svg { width: 24px; height: 24px; }
    .vh-card-info { flex: 1; min-width: 0; }
    .vh-card-plate { font-size: 17px; font-weight: 700; margin: 0; }
    .vh-card-brand { font-size: 13px; color: var(--muted); margin: 2px 0 6px; }

    .vh-empty {
        text-align: center; padding: 50px 20px; color: var(--muted);
        border: 2px dashed var(--border); border-radius: 14px;
    }
    .vh-empty svg { width: 40px; height: 40px; margin-bottom: 10px; opacity: .4; }
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
                <x-gravityui-car width="26" height="26" />
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
        <select name="status" onchange="this.form.submit()">
            <option value="">Estado: Todos</option>
            <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Activos</option>
            <option value="maintenance" {{ ($filters['status'] ?? '') === 'maintenance' ? 'selected' : '' }}>En mantenimiento</option>
            <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
        </select>
        <button type="submit" style="display:none;">Filtrar</button>
    </form>

    <div class="content-actions">
        <a href="{{ route('admin.vehicles.create') }}" class="btn">
            <x-gravityui-plus width="15" height="15" />
            Agregar vehículo
        </a>
    </div>

    {{-- Vehicle grid --}}
    @if($vehicles->isEmpty())
        <div class="vh-empty">
            <x-gravityui-car />
            <p style="margin:0;font-weight:600;">No se encontraron vehículos</p>
            <p style="margin:4px 0 0;font-size:13px;">Agrega un nuevo vehículo para comenzar.</p>
        </div>
    @else
    <div class="vh-grid">
        @foreach($vehicles as $v)
            @php
                $statusInfo = match($v->status) {
                    'maintenance' => ['variant' => 'warn', 'label' => 'En mantenimiento'],
                    'inactive'    => ['variant' => 'danger', 'label' => 'Inactivo'],
                    default       => ['variant' => 'ok', 'label' => 'Activo'],
                };
            @endphp
            <div class="vh-card" onclick="window.location.href='{{ route('admin.vehicles.show', $v) }}'">
                <div class="vh-card-top">
                    <div class="vh-card-icon">
                        <x-gravityui-car />
                    </div>
                    <div class="vh-card-info">
                        <h3 class="vh-card-plate">{{ $v->plate_number }}</h3>
                        <p class="vh-card-brand">{{ $v->brand }} {{ $v->model }} · {{ $v->year }}</p>
                        <x-ui.badge :variant="$statusInfo['variant']">{{ $statusInfo['label'] }}</x-ui.badge>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    @if(session('status'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.appToast) window.appToast.show({{ json_encode(session('status')) }});
            });
        </script>
    @endif
@endsection

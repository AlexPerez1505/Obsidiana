@extends('layouts.dashboard')
@section('title', 'Mis Viáticos')
@section('page-title', 'Mis Viáticos')
@section('page-sub', 'Historial de gastos de viaje')

@push('head')
<style>
    .vl-page {
        max-width: 100%; margin: 0; padding: 0;
    }

    /* Toolbar: mismo lenguaje que el resto de listados (buscar + filtro) */
    .vl-toolbar {
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
        margin-bottom: 18px;
    }
    .vl-search {
        position: relative; flex: 1; min-width: 220px; max-width: 380px;
    }
    .vl-toolbar .vl-search input[type="text"] {
        width: 100%; padding: 10px 12px 10px 40px;
        border: 1px solid var(--border); border-radius: 8px;
        font-size: 14px; font-family: inherit;
        background: var(--surface); color: var(--text);
        outline: none; transition: border .15s, box-shadow .15s;
    }
    .vl-toolbar .vl-search input[type="text"]:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-soft);
    }
    .vl-search svg {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 17px; height: 17px; color: var(--muted); pointer-events: none;
    }
    .vl-toolbar select {
        padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;
        font-size: 14px; font-family: inherit; background: var(--surface);
        color: var(--text); cursor: pointer; outline: none;
    }

    /* Stats */
    .vl-stats { margin-bottom: 18px; }

    /* List */
    .vl-list { display: flex; flex-direction: column; gap: 10px; }
    .vl-card {
        display: flex; align-items: center; gap: 12px;
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 16px; padding: 14px 16px;
        transition: border .15s, box-shadow .15s, transform .15s;
        text-decoration: none; color: inherit;
    }
    .vl-card:hover {
        border-color: var(--primary);
        box-shadow: 0 6px 18px rgba(0,0,0,.07);
        transform: translateY(-1px);
    }

    /* Receipt thumbnail */
    .vl-thumb {
        width: 48px; height: 48px; border-radius: 12px;
        background: var(--surface-2); border: 1.5px solid #94a3b8;
        display: flex; align-items: center; justify-content: center;
        flex: 0 0 auto; overflow: hidden; position: relative;
    }
    .vl-thumb svg { width: 22px; height: 22px; color: #94a3b8; }
    .vl-thumb img { width: 100%; height: 100%; object-fit: cover; }

    .vl-card-info { flex: 1; min-width: 0; }
    .vl-card-top-row {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .vl-card-place {
        font-size: 15px; font-weight: 800; margin: 0;
        color: var(--text); white-space: nowrap; overflow: hidden;
        text-overflow: ellipsis; max-width: 180px;
    }
    .vl-vehicle-tag {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 10px; border-radius: 999px;
        background: var(--primary-soft); color: var(--primary);
        font-size: 11px; font-weight: 700; flex: 0 0 auto;
        border: 1px solid rgba(0,122,255,.2);
    }
    .vl-vehicle-tag svg { width: 12px; height: 12px; }
    .vl-card-sub {
        font-size: 12px; color: var(--muted); margin: 3px 0 0;
        display: flex; align-items: center; gap: 5px;
    }
    .vl-card-sub svg { width: 13px; height: 13px; }

    .vl-card-right {
        display: flex; flex-direction: column; align-items: flex-end; gap: 4px;
        flex: 0 0 auto;
    }
    .vl-card-total {
        font-size: 17px; font-weight: 800; color: var(--primary);
        line-height: 1;
    }

    /* Card actions */
    .vl-card-actions {
        display: flex; gap: 6px; flex: 0 0 auto; align-items: center;
    }
    .vl-card-btn {
        width: 34px; height: 34px; border-radius: 10px;
        border: 1.5px solid #94a3b8; background: var(--surface);
        color: var(--muted); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: all .15s; text-decoration: none;
    }
    .vl-card-btn:hover { background: var(--primary-soft); color: var(--primary); border-color: var(--primary); }
    .vl-card-btn.vl-btn-danger:hover { background: #fee2e2; color: #ef4444; border-color: #ef4444; }
    .vl-card-btn svg { width: 16px; height: 16px; }

    /* Empty state */
    .vl-empty {
        text-align: center; padding: 50px 20px; color: var(--muted);
        border: 2px dashed #94a3b8; border-radius: 16px;
    }
    .vl-empty svg { width: 40px; height: 40px; margin-bottom: 10px; opacity: .4; }
    .vl-empty p { margin: 0; font-weight: 600; }
    .vl-empty span { font-size: 13px; display: block; margin-top: 4px; }

    /* Desktop responsive */
    @media (min-width: 768px) {
        .vl-page { max-width: 100%; padding-bottom: 30px; }
        .vl-stats { gap: 16px; }
        .vl-stat { padding: 18px 14px; }
        .vl-stat-num { font-size: 28px; }
        .vl-stat-lbl { font-size: 11px; }
        .vl-list {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;
        }
        .vl-card { padding: 16px 20px; }
        .vl-card-place { max-width: none; font-size: 16px; }
        .vl-card-total { font-size: 19px; }
        .vl-thumb { width: 56px; height: 56px; }
        .vl-thumb svg { width: 26px; height: 26px; }
    }
    @media (min-width: 1200px) {
        .vl-list { grid-template-columns: repeat(3, 1fr); }
    }
</style>
@endpush

@section('content')
<div class="vl-page">

    <x-ui.page-header title="Mis Viáticos" :back="route('dashboard')">
        <a href="{{ route('admin.viatics.create') }}" class="btn">
            <x-gravityui-plus width="15" height="15" />
            Nuevo viático
        </a>
    </x-ui.page-header>

    {{-- Buscar y filtrar, igual que en los demás listados --}}
    <form method="GET" action="{{ route('admin.viatics.index') }}" class="vl-toolbar">
        <div class="vl-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar por lugar, vehículo o usuario...">
        </div>
        <select name="status" onchange="this.form.submit()">
            <option value="">Estado: Todos</option>
            <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pendientes</option>
            <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Aprobados</option>
            <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rechazados</option>
        </select>
        <button type="submit" style="display:none;">Filtrar</button>
    </form>

    {{-- Stats --}}
    <div class="grid stat-row vl-stats">
        <x-ui.stat-card :value="$viatics->count()" label="Registros" color="blue">
            <x-slot:icon><x-gravityui-file-text width="22" height="22" /></x-slot:icon>
        </x-ui.stat-card>
        <x-ui.stat-card :value="$viatics->where('status', 'pending')->count()" label="Pendientes" color="orange">
            <x-slot:icon><x-gravityui-clock width="22" height="22" /></x-slot:icon>
        </x-ui.stat-card>
        <x-ui.stat-card :value="'$'.number_format($viatics->sum(fn($v) => (float) $v->total_computed), 2)" label="Total" color="green">
            <x-slot:icon><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></x-slot:icon>
        </x-ui.stat-card>
    </div>

    {{-- List --}}
    @if($viatics->isNotEmpty())
        <div class="vl-list">
            @foreach($viatics as $vt)
                @php
                    $badgeInfo = match($vt->status) {
                        'approved' => ['variant' => 'ok', 'label' => 'Aprobado'],
                        'rejected' => ['variant' => 'danger', 'label' => 'Rechazado'],
                        default    => ['variant' => 'warn', 'label' => 'Pendiente'],
                    };
                    $timeLabel = $vt->created_at->isToday() ? 'Hoy, ' . $vt->created_at->format('g:i A')
                        : ($vt->created_at->isYesterday() ? 'Ayer'
                        : $vt->created_at->diffInDays(now()) . ' días atrás');
                @endphp
                <div class="vl-card" onclick="window.location='{{ route('admin.viatics.show', $vt) }}'" style="cursor:pointer">
                    <div class="vl-thumb">
                        @if(! empty($vt->ticket_photos))
                            <img src="{{ asset('storage/' . $vt->ticket_photos[0]) }}" alt="Ticket">
                        @elseif($vt->ticket_photo)
                            <img src="{{ asset('storage/' . $vt->ticket_photo) }}" alt="Ticket">
                        @else
                            <x-gravityui-file-text />
                        @endif
                    </div>
                    <div class="vl-card-info">
                        <div class="vl-card-top-row">
                            <p class="vl-card-place">{{ $vt->place ?: 'Sin lugar' }}</p>
                            @if($vt->vehicle_name)
                                <span class="vl-vehicle-tag">
                                    <x-gravityui-car />
                                    {{ $vt->vehicle_name }}
                                </span>
                            @endif
                        </div>
                        <p class="vl-card-sub">
                            <x-gravityui-clock />
                            {{ $timeLabel }}
                        </p>
                    </div>
                    <div class="vl-card-right">
                        <span class="vl-card-total">${{ $vt->total }}</span>
                        <x-ui.badge :variant="$badgeInfo['variant']">{{ $badgeInfo['label'] }}</x-ui.badge>
                    </div>
                    <div class="vl-card-actions" onclick="event.stopPropagation()">
                        <a href="{{ route('admin.viatics.edit', $vt) }}" class="vl-card-btn" aria-label="Editar">
                            <x-gravityui-pencil />
                        </a>
                        <form method="POST" action="{{ route('admin.viatics.destroy', $vt) }}" onsubmit="return confirm('¿Eliminar este viático?')" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="vl-card-btn vl-btn-danger" aria-label="Eliminar">
                                <x-gravityui-trash-bin />
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="vl-empty">
            <x-gravityui-map-pin />
            <p>No hay viáticos registrados</p>
            <span>Da clic en "Nuevo viático" para registrar el primero.</span>
        </div>
    @endif

</div>
@endsection

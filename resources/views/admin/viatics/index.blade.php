@extends('layouts.dashboard')
@section('title', 'Mis Viáticos')
@section('page-title', 'Mis Viáticos')
@section('page-sub', 'Historial de gastos de viaje')

@push('head')
<style>
    .vl-page {
        max-width: 100%; margin: 0; padding: 0 0 90px;
    }

    /* Header */
    .vl-header {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 20px;
    }
    .vl-back {
        display: inline-flex; align-items: center; justify-content: center;
        width: 42px; height: 42px; border-radius: 12px;
        border: 1.5px solid #94a3b8; background: var(--surface);
        color: var(--text); text-decoration: none; flex: 0 0 auto;
        transition: all .15s;
    }
    .vl-back:hover { background: var(--surface-2); border-color: var(--primary); color: var(--primary); }
    .vl-back svg { width: 20px; height: 20px; }
    .vl-header-title {
        font-size: 24px; font-weight: 800; color: var(--primary);
        margin: 0; line-height: 1.2;
    }

    /* Stats */
    .vl-stats {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;
        margin-bottom: 20px;
    }
    .vl-stat {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 14px; padding: 14px 10px; text-align: center;
        box-shadow: 0 2px 6px rgba(0,0,0,.03);
    }
    .vl-stat-num { font-size: 22px; font-weight: 800; color: var(--primary); line-height: 1; }
    .vl-stat-lbl { font-size: 10px; font-weight: 700; color: var(--muted); margin-top: 5px; text-transform: uppercase; letter-spacing: .03em; }

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
    .vl-card-badge {
        font-size: 9.5px; font-weight: 700; padding: 2px 8px;
        border-radius: 20px; border: 1.5px solid transparent;
    }
    .vl-card-badge.pending { background: #fef9c3; color: #a16207; border-color: #f59e0b; }
    .vl-card-badge.approved { background: #e6ffe6; color: #15803d; border-color: #22c55e; }
    .vl-card-badge.rejected { background: #ffebeb; color: #ff4a4a; border-color: #ef4444; }

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
        .vl-fab { display: none; }
    }
    @media (min-width: 1200px) {
        .vl-list { grid-template-columns: repeat(3, 1fr); }
    }

    /* FAB */
    .vl-fab {
        position: fixed; bottom: 28px; right: 28px;
        width: 60px; height: 60px; border-radius: 50%;
        background: var(--primary); color: #fff;
        border: none; cursor: pointer; z-index: 80;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 6px 24px rgba(0,122,255,.35);
        transition: transform .2s, box-shadow .2s;
        text-decoration: none;
    }
    .vl-fab:hover { transform: scale(1.08); box-shadow: 0 8px 30px rgba(0,122,255,.45); }
    .vl-fab:active { transform: scale(.95); }
    .vl-fab svg { width: 28px; height: 28px; }

    @media (max-width: 640px) {
        .vl-fab { bottom: 20px; right: 20px; }
    }

    /* Desktop: show add button inline instead of FAB */
    .vl-add-desktop { display: none; }
    @media (min-width: 768px) {
        .vl-add-desktop {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 20px; border-radius: 12px;
            background: var(--primary); color: #fff;
            font-size: 15px; font-weight: 700; text-decoration: none;
            border: 1.5px solid rgba(255,255,255,.35);
            box-shadow: 0 2px 0 rgba(0,0,0,.12);
            transition: background .15s;
        }
        .vl-add-desktop:hover { background: var(--primary-strong); }
        .vl-add-desktop svg { width: 18px; height: 18px; }
    }
</style>
@endendpush

@section('content')
<div class="vl-page">

    {{-- Header --}}
    <div class="vl-header">
        <a href="{{ route('dashboard') }}" class="vl-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <h1 class="vl-header-title">Mis Viáticos</h1>
        <div style="flex:1"></div>
        <a href="{{ route('admin.viatics.create') }}" class="vl-add-desktop">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo Viático
        </a>
    </div>

    {{-- Stats --}}
    <div class="vl-stats">
        <div class="vl-stat">
            <div class="vl-stat-num">{{ $viatics->count() }}</div>
            <div class="vl-stat-lbl">Registros</div>
        </div>
        <div class="vl-stat">
            <div class="vl-stat-num">{{ $viatics->where('status', 'pending')->count() }}</div>
            <div class="vl-stat-lbl">Pendientes</div>
        </div>
        <div class="vl-stat">
            <div class="vl-stat-num">${{ number_format($viatics->sum(fn($v) => (float) $v->total_computed), 2) }}</div>
            <div class="vl-stat-lbl">Total</div>
        </div>
    </div>

    {{-- List --}}
    @if($viatics->isNotEmpty())
        <div class="vl-list">
            @foreach($viatics as $vt)
                @php
                    $badgeInfo = match($vt->status) {
                        'approved' => ['class' => 'approved', 'label' => 'Aprobado'],
                        'rejected' => ['class' => 'rejected', 'label' => 'Rechazado'],
                        default    => ['class' => 'pending', 'label' => 'Pendiente'],
                    };
                    $timeLabel = $vt->created_at->isToday() ? 'Hoy, ' . $vt->created_at->format('g:i A')
                        : ($vt->created_at->isYesterday() ? 'Ayer'
                        : $vt->created_at->diffInDays(now()) . ' días atrás');
                @endphp
                <div class="vl-card" onclick="window.location='{{ route('admin.viatics.show', $vt) }}'" style="cursor:pointer">
                    <div class="vl-thumb">
                        @if($vt->ticket_photo)
                            <img src="{{ asset('storage/' . $vt->ticket_photo) }}" alt="Ticket">
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/></svg>
                        @endif
                    </div>
                    <div class="vl-card-info">
                        <div class="vl-card-top-row">
                            <p class="vl-card-place">{{ $vt->place ?: 'Sin lugar' }}</p>
                            @if($vt->vehicle_name)
                                <span class="vl-vehicle-tag">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17h14M3 17l1.5-5.5A2 2 0 0 1 6.4 10h11.2a2 2 0 0 1 1.9 1.5L21 17M5 17v2M19 17v2"/><circle cx="7.5" cy="17" r="1.5"/><circle cx="16.5" cy="17" r="1.5"/></svg>
                                    {{ $vt->vehicle_name }}
                                </span>
                            @endif
                        </div>
                        <p class="vl-card-sub">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            {{ $timeLabel }}
                        </p>
                    </div>
                    <div class="vl-card-right">
                        <span class="vl-card-total">${{ $vt->total }}</span>
                        <span class="vl-card-badge {{ $badgeInfo['class'] }}">{{ $badgeInfo['label'] }}</span>
                    </div>
                    <div class="vl-card-actions" onclick="event.stopPropagation()">
                        <a href="{{ route('admin.viatics.edit', $vt) }}" class="vl-card-btn" aria-label="Editar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <form method="POST" action="{{ route('admin.viatics.destroy', $vt) }}" onsubmit="return confirm('¿Eliminar este viático?')" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="vl-card-btn vl-btn-danger" aria-label="Eliminar">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="vl-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <p>No hay viáticos registrados</p>
            <span>Toca el botón + para registrar tu primer viático</span>
        </div>
    @endif

</div>

{{-- FAB --}}
<a href="{{ route('admin.viatics.create') }}" class="vl-fab" aria-label="Nuevo viático">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
</a>
@endsection

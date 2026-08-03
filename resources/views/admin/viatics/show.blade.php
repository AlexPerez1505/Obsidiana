@extends('layouts.dashboard')
@section('title', 'Detalle de Viático')
@section('page-title', 'Detalle de Viático')
@section('page-sub', 'Gastos del viático')

@push('head')
<style>
    .vp-page { max-width: 100%; margin: 0; padding: 0 4px; }

    .vp-header { display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
    .vp-back {
        display: inline-flex; align-items: center; justify-content: center;
        width: 42px; height: 42px; border-radius: 12px;
        border: 1.5px solid #94a3b8; background: var(--surface);
        color: var(--text); text-decoration: none; flex: 0 0 auto; transition: all .15s;
    }
    .vp-back:hover { background: var(--surface-2); border-color: var(--primary); color: var(--primary); }
    .vp-back svg { width: 20px; height: 20px; }
    .vp-title-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .vp-title { font-size: 24px; font-weight: 800; color: var(--primary); margin: 0; line-height: 1.2; }
    .vp-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 12px; border-radius: 999px;
        font-size: 11px; font-weight: 700; border: 1.5px solid transparent;
    }
    .vp-badge.pending { background: #fef9c3; color: #a16207; border-color: #f59e0b; }
    .vp-badge.approved { background: #e6ffe6; color: #15803d; border-color: #22c55e; }
    .vp-badge.rejected { background: #ffebeb; color: #ff4a4a; border-color: #ef4444; }
    .vp-subtitle { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--muted); margin: 0 0 20px; }
    .vp-subtitle svg { width: 15px; height: 15px; flex: 0 0 auto; }

    .vp-total-card {
        background: linear-gradient(135deg, var(--primary), #0098ff);
        border-radius: 20px; padding: 24px 20px; text-align: center;
        margin-bottom: 24px; box-shadow: 0 8px 28px rgba(0,122,255,.2);
    }
    .vp-total-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: rgba(255,255,255,.75); margin: 0 0 8px; }
    .vp-total-amount { font-size: 36px; font-weight: 900; color: #fff; margin: 0; line-height: 1; }
    .vp-total-meta { display: flex; align-items: center; justify-content: center; gap: 5px; font-size: 12px; color: rgba(255,255,255,.7); margin: 10px 0 0; }
    .vp-total-meta svg { width: 13px; height: 13px; }

    /* Summary by type */
    .vp-summary { margin-bottom: 20px; }
    .vp-summary-title { font-size: 15px; font-weight: 800; color: var(--text); margin: 0 0 10px; }
    .vp-summary-grid {
        display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;
    }
    .vp-summary-row {
        display: flex; align-items: center; justify-content: space-between;
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 12px; padding: 10px 14px;
    }
    .vp-summary-left { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--text); }
    .vp-summary-icon { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
    .vp-summary-icon.toll { background: #fef9c3; color: #a16207; }
    .vp-summary-icon.fuel { background: #dbeafe; color: #2563eb; }
    .vp-summary-icon.meal { background: #fce7f3; color: #db2777; }
    .vp-summary-icon.other { background: var(--primary-soft); color: var(--primary); }
    .vp-summary-icon svg { width: 16px; height: 16px; }
    .vp-summary-amount { font-size: 15px; font-weight: 800; color: var(--primary); }
    @media (min-width: 768px) {
        .vp-summary-grid { grid-template-columns: repeat(4, 1fr); }
    }

    .vp-list-title { display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 800; color: var(--text); margin: 0 0 14px; }
    .vp-list-title svg { width: 20px; height: 20px; color: var(--primary); }
    .vp-list-count { background: var(--primary-soft); color: var(--primary); font-size: 12px; font-weight: 700; padding: 2px 10px; border-radius: 999px; border: 1px solid rgba(0,122,255,.2); }
    .vp-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; }
    .vp-row {
        display: flex; align-items: center; gap: 12px;
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 14px; padding: 12px 14px; transition: border .15s, box-shadow .15s;
    }
    .vp-row:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(0,0,0,.05); }
    .vp-row-icon { width: 40px; height: 40px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex: 0 0 auto; border: 1.5px solid #94a3b8; }
    .vp-row-icon.toll { background: #fef9c3; color: #a16207; }
    .vp-row-icon.fuel { background: #dbeafe; color: #2563eb; }
    .vp-row-icon.meal { background: #fce7f3; color: #db2777; }
    .vp-row-icon.other { background: var(--primary-soft); color: var(--primary); }
    .vp-row-icon svg { width: 20px; height: 20px; }
    .vp-row-info { flex: 1; min-width: 0; }
    .vp-row-label { font-size: 14px; font-weight: 700; margin: 0; color: var(--text); }
    .vp-row-time { font-size: 11px; color: var(--muted); margin: 2px 0 0; display: flex; align-items: center; gap: 4px; }
    .vp-row-time svg { width: 12px; height: 12px; }
    .vp-row-right { display: flex; align-items: center; gap: 8px; flex: 0 0 auto; }
    .vp-row-amount { font-size: 15px; font-weight: 800; color: var(--primary); }
    .vp-row-edit {
        width: 32px; height: 32px; border-radius: 8px;
        border: 1.5px solid #94a3b8; background: var(--surface);
        color: var(--muted); cursor: pointer;
        display: flex; align-items: center; justify-content: center; transition: all .15s;
    }
    .vp-row-edit:hover { background: var(--primary-soft); color: var(--primary); border-color: var(--primary); }
    .vp-row-edit svg { width: 15px; height: 15px; }

    .vp-empty-list { text-align: center; padding: 30px 20px; color: var(--muted); border: 2px dashed #94a3b8; border-radius: 14px; margin-bottom: 24px; }
    .vp-empty-list svg { width: 32px; height: 32px; margin-bottom: 8px; opacity: .4; }
    .vp-empty-list p { margin: 0; font-size: 14px; font-weight: 600; }
    .vp-empty-list span { font-size: 12px; display: block; margin-top: 3px; }

    .vp-overlay { display: none; position: fixed; inset: 0; background: rgba(2,6,23,.45); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 90; align-items: flex-end; justify-content: center; }
    .vp-overlay.open { display: flex; }
    .vp-modal {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 20px 20px 0 0; width: 100%; max-width: 480px;
        padding: 24px 20px 20px; max-height: 85vh; overflow-y: auto;
    }
    @media (min-width: 768px) {
        .vp-overlay { align-items: center; }
        .vp-modal { max-width: 520px; border-radius: 20px; }
    }
    .vp-modal-handle { width: 40px; height: 4px; border-radius: 2px; background: #cbd5e1; margin: 0 auto 16px; }
    .vp-modal-title { font-size: 18px; font-weight: 800; margin: 0 0 20px; color: var(--primary); }

    .vp-type-row { display: flex; gap: 8px; margin-bottom: 18px; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 2px; }
    .vp-type-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 10px 16px; border-radius: 999px;
        border: 2px solid #94a3b8; background: var(--surface);
        cursor: pointer; transition: all .15s; font-family: inherit; flex: 0 0 auto; white-space: nowrap;
    }
    .vp-type-btn svg { width: 18px; height: 18px; color: var(--muted); }
    .vp-type-btn span { font-size: 13px; font-weight: 700; color: var(--text); }
    .vp-type-btn:hover { border-color: var(--primary); }
    .vp-type-btn.selected { background: var(--primary); border-color: var(--primary); box-shadow: 0 3px 12px rgba(0,122,255,.22); }
    .vp-type-btn.selected svg { color: #fff; }
    .vp-type-btn.selected span { color: #fff; }

    .vp-field { margin-bottom: 14px; }
    .vp-label { display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin: 0 0 6px; }
    .vp-input {
        width: 100%; padding: 14px; border: 2px solid #94a3b8;
        border-radius: 12px; font-size: 16px; font-family: inherit;
        background: var(--surface); color: var(--text);
        outline: none; transition: border .15s, box-shadow .15s; -webkit-appearance: none; appearance: none;
    }
    .vp-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,122,255,.12); }
    .vp-input::placeholder { color: #cbd5e1; }
    .vp-amount-wrap { position: relative; display: flex; align-items: center; }
    .vp-amount-wrap .vp-prefix { position: absolute; left: 16px; font-size: 22px; font-weight: 800; color: var(--muted); pointer-events: none; }
    .vp-amount-wrap input { padding-left: 42px; padding-top: 16px; padding-bottom: 16px; font-size: 22px; font-weight: 800; text-align: left; }

    .vp-save-btn, .vp-btn-save, .vp-add-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 10px 18px; border: none; border-radius: 12px;
        background: var(--primary); color: #fff;
        font-size: 14px; font-weight: 700; cursor: pointer;
        font-family: inherit; transition: background .15s;
        box-shadow: 0 3px 12px rgba(0,122,255,.22);
    }
    .vp-save-btn svg, .vp-btn-save svg, .vp-add-btn svg { width: 16px; height: 16px; }
    .vp-save-btn:hover, .vp-btn-save:hover, .vp-add-btn:hover { background: var(--primary-strong); }

    .vp-edit-actions { display: flex; gap: 10px; }
    .vp-btn-save { flex: 1; padding: 15px; font-size: 15px; }
    .vp-btn-save svg { width: 18px; height: 18px; }
    .vp-btn-delete {
        flex: 0 0 auto; padding: 15px 20px; border-radius: 14px;
        background: transparent; color: #ef4444; border: 2px solid #ef4444;
        font-size: 14px; font-weight: 700; cursor: pointer;
        font-family: inherit; transition: all .15s;
        display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .vp-btn-delete:hover { background: #fee2e2; }
    .vp-btn-delete svg { width: 17px; height: 17px; }

    .vp-bottom { display: flex; flex-direction: column; gap: 10px; margin-top: 8px; }
    .vp-back-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%; padding: 14px; border-radius: 14px;
        background: transparent; color: var(--text); border: 2px solid #94a3b8;
        font-size: 15px; font-weight: 700; cursor: pointer;
        font-family: inherit; transition: all .15s; text-decoration: none;
    }
    .vp-back-btn:hover { border-color: var(--primary); color: var(--primary); }
    .vp-back-btn svg { width: 18px; height: 18px; }

    @media (min-width: 768px) {
        .vp-page { max-width: 100%; padding: 0; }
        .vp-title { font-size: 26px; }
        .vp-total-card { padding: 32px 28px; }
        .vp-total-amount { font-size: 48px; }
        .vp-list { gap: 12px; }
        .vp-row { padding: 16px 20px; }
        .vp-add-btn { max-width: 300px; margin: 0 auto; }
        .vp-back-btn { max-width: 300px; margin: 0 auto; }
    }
</style>
@endendpush

@php
    $badgeInfo = match($viatic->status) {
        'approved' => ['class' => 'approved', 'label' => 'Aprobado'],
        'rejected' => ['class' => 'rejected', 'label' => 'Rechazado'],
        default    => ['class' => 'pending', 'label' => 'Pendiente'],
    };
    $initialGastos = $viatic->expenses->map(fn($e) => [
        'id' => $e->id,
        'type' => $e->type,
        'label' => $e->label,
        'amount' => (float) $e->amount,
        'time_label' => $e->created_at->isToday()
            ? 'Hoy, ' . $e->created_at->format('g:i A')
            : $e->created_at->format('d/m/Y, g:i A'),
    ])->values()->all();
    $summary = collect($initialGastos)->groupBy('type')->map(fn($g) => $g->sum('amount'));
    $summaryTotals = [
        'toll'  => (float) ($summary['toll'] ?? 0),
        'fuel'  => (float) ($summary['fuel'] ?? 0),
        'meal'  => (float) ($summary['meal'] ?? 0),
        'other' => (float) ($summary['other'] ?? 0),
    ];
@endphp

@section('content')
<div class="vp-page" id="vpPage">

    <div class="vp-header">
        <a href="{{ route('admin.viatics.index') }}" class="vp-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div class="vp-title-row">
            <h1 class="vp-title">{{ $viatic->place ?: 'Viático' }}</h1>
            <span class="vp-badge {{ $badgeInfo['class'] }}">{{ $badgeInfo['label'] }}</span>
        </div>
    </div>
    <p class="vp-subtitle">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17h14M3 17l1.5-5.5A2 2 0 0 1 6.4 10h11.2a2 2 0 0 1 1.9 1.5L21 17M5 17v2M19 17v2"/><circle cx="7.5" cy="17" r="1.5"/><circle cx="16.5" cy="17" r="1.5"/></svg>
        {{ $viatic->vehicle_name ?: 'Sin vehículo' }} · {{ $viatic->expense_date?->format('d/m/Y') ?: 'Sin fecha' }}
    </p>

    <div class="vp-total-card">
        <p class="vp-total-label">Total acumulado</p>
        <p class="vp-total-amount" id="vpTotalAmount">${{ number_format((float) $viatic->total_computed, 2) }}</p>
        <p class="vp-total-meta">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            <span id="vpCountText">{{ $viatic->expenses->count() }} gastos</span>
        </p>
    </div>

    <div class="vp-summary">
        <p class="vp-summary-title">Resumen por tipo</p>
        <div class="vp-summary-grid" id="vpSummaryGrid">
            <div class="vp-summary-row" data-type="toll">
                <div class="vp-summary-left">
                    <span class="vp-summary-icon toll"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20M4 12v6h16v-6M8 12V8a4 4 0 0 1 8 0v4"/></svg></span>
                    <span>Casetas</span>
                </div>
                <span class="vp-summary-amount" id="vpSummaryToll">${{ number_format($summaryTotals['toll'], 2) }}</span>
            </div>
            <div class="vp-summary-row" data-type="fuel">
                <div class="vp-summary-left">
                    <span class="vp-summary-icon fuel"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18M3 22h12M15 8h2a2 2 0 0 1 2 2v8a2 2 0 0 0 2 2 2 2 0 0 0 2-2V9.5L19 5"/></svg></span>
                    <span>Gasolina</span>
                </div>
                <span class="vp-summary-amount" id="vpSummaryFuel">${{ number_format($summaryTotals['fuel'], 2) }}</span>
            </div>
            <div class="vp-summary-row" data-type="meal">
                <div class="vp-summary-left">
                    <span class="vp-summary-icon meal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2M7 2v20M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3z"/></svg></span>
                    <span>Viáticos</span>
                </div>
                <span class="vp-summary-amount" id="vpSummaryMeal">${{ number_format($summaryTotals['meal'], 2) }}</span>
            </div>
            <div class="vp-summary-row" data-type="other">
                <div class="vp-summary-left">
                    <span class="vp-summary-icon other"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg></span>
                    <span>Adicional</span>
                </div>
                <span class="vp-summary-amount" id="vpSummaryOther">${{ number_format($summaryTotals['other'], 2) }}</span>
            </div>
        </div>
    </div>

    <div class="vp-list-section">
    <h3 class="vp-list-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/></svg>
        Gastos del viático
        <span class="vp-list-count" id="vpListCount">{{ $viatic->expenses->count() }}</span>
    </h3>

    <div id="vpList" class="vp-list" @if($viatic->expenses->isEmpty()) style="display:none" @endif>
        @forelse($initialGastos as $g)
        <div class="vp-row" data-id="{{ $g['id'] }}">
            <div class="vp-row-icon {{ $g['type'] }}">
                @if($g['type'] === 'toll')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20M4 12v6h16v-6M8 12V8a4 4 0 0 1 8 0v4"/></svg>
                @elseif($g['type'] === 'fuel')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18M3 22h12M15 8h2a2 2 0 0 1 2 2v8a2 2 0 0 0 2 2 2 2 0 0 0 2-2V9.5L19 5"/></svg>
                @elseif($g['type'] === 'meal')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2M7 2v20M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3z"/></svg>
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                @endif
            </div>
            <div class="vp-row-info">
                <p class="vp-row-label">{{ $g['label'] }}</p>
                <p class="vp-row-time">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <span>{{ $g['time_label'] }}</span>
                </p>
            </div>
            <div class="vp-row-right">
                <span class="vp-row-amount">${{ number_format($g['amount'], 2) }}</span>
                <button type="button" class="vp-row-edit" onclick="vpApp.openEdit({{ $g['id'] }})">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
            </div>
        </div>
        @empty
        @endforelse
    </div>
    <div id="vpEmpty" class="vp-empty-list" @if($viatic->expenses->isNotEmpty()) style="display:none" @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
        <p>No hay gastos registrados</p>
        <span>Agrega el primer gasto con el botón de abajo</span>
    </div>
    </div>

    <div class="vp-bottom">
        <button type="button" class="vp-add-btn" id="vpBtnAdd" onclick="vpApp.openAdd()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Agregar gasto
        </button>
        <a href="{{ route('admin.viatics.index') }}" class="vp-back-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Volver a viáticos
        </a>
    </div>

</div>

{{-- Add Expense Modal --}}
<div class="vp-overlay" id="vpAddOverlay" onclick="if(event.target===this) vpApp.closeAdd()">
    <div class="vp-modal">
        <div class="vp-modal-handle"></div>
        <h2 class="vp-modal-title">Agregar Gasto</h2>
        <form onsubmit="vpApp.submitAdd(event)">
            <div class="vp-type-row" id="vpAddTypeRow">
                <button type="button" class="vp-type-btn" data-type="toll" onclick="vpApp.selectType(this, 'toll', 'add')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20M4 12v6h16v-6M8 12V8a4 4 0 0 1 8 0v4"/></svg>
                    <span>Caseta</span>
                </button>
                <button type="button" class="vp-type-btn" data-type="fuel" onclick="vpApp.selectType(this, 'fuel', 'add')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18M3 22h12M15 8h2a2 2 0 0 1 2 2v8a2 2 0 0 0 2 2 2 2 0 0 0 2-2V9.5L19 5"/></svg>
                    <span>Gasolina</span>
                </button>
                <button type="button" class="vp-type-btn" data-type="meal" onclick="vpApp.selectType(this, 'meal', 'add')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2M7 2v20M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3z"/></svg>
                    <span>Viático</span>
                </button>
                <button type="button" class="vp-type-btn" data-type="other" onclick="vpApp.selectType(this, 'other', 'add')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    <span>Adicional</span>
                </button>
            </div>
            <input type="hidden" id="vpAddType" value="toll">
            <div class="vp-field">
                <label class="vp-label">Monto</label>
                <div class="vp-amount-wrap">
                    <span class="vp-prefix">$</span>
                    <input type="number" id="vpAddAmount" class="vp-input" placeholder="0.00" step="0.01" min="0" inputmode="decimal" required>
                </div>
            </div>
            <div class="vp-field">
                <label class="vp-label">Descripción (opcional)</label>
                <input type="text" id="vpAddLabel" class="vp-input" placeholder="Ej. Caseta - Guadalajara Norte">
            </div>
            <button type="submit" class="vp-save-btn" id="vpBtnSaveAdd">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span id="vpBtnSaveAddText">Guardar gasto</span>
            </button>
        </form>
    </div>
</div>

{{-- Edit Expense Modal --}}
<div class="vp-overlay" id="vpEditOverlay" onclick="if(event.target===this) vpApp.closeEdit()">
    <div class="vp-modal">
        <div class="vp-modal-handle"></div>
        <h2 class="vp-modal-title">Editar Gasto</h2>
        <form onsubmit="vpApp.submitEdit(event)">
            <input type="hidden" id="vpEditId">
            <div class="vp-type-row" id="vpEditTypeRow">
                <button type="button" class="vp-type-btn" data-type="toll" onclick="vpApp.selectType(this, 'toll', 'edit')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20M4 12v6h16v-6M8 12V8a4 4 0 0 1 8 0v4"/></svg>
                    <span>Caseta</span>
                </button>
                <button type="button" class="vp-type-btn" data-type="fuel" onclick="vpApp.selectType(this, 'fuel', 'edit')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18M3 22h12M15 8h2a2 2 0 0 1 2 2v8a2 2 0 0 0 2 2 2 2 0 0 0 2-2V9.5L19 5"/></svg>
                    <span>Gasolina</span>
                </button>
                <button type="button" class="vp-type-btn" data-type="meal" onclick="vpApp.selectType(this, 'meal', 'edit')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2M7 2v20M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3z"/></svg>
                    <span>Viático</span>
                </button>
                <button type="button" class="vp-type-btn" data-type="other" onclick="vpApp.selectType(this, 'other', 'edit')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    <span>Adicional</span>
                </button>
            </div>
            <input type="hidden" id="vpEditType" value="toll">
            <div class="vp-field">
                <label class="vp-label">Monto</label>
                <div class="vp-amount-wrap">
                    <span class="vp-prefix">$</span>
                    <input type="number" id="vpEditAmount" class="vp-input" placeholder="0.00" step="0.01" min="0" inputmode="decimal" required>
                </div>
            </div>
            <div class="vp-field">
                <label class="vp-label">Descripción (opcional)</label>
                <input type="text" id="vpEditLabel" class="vp-input" placeholder="Ej. Caseta - Guadalajara Norte">
            </div>
            <div class="vp-edit-actions">
                <button type="submit" class="vp-btn-save" id="vpBtnSaveEdit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <span id="vpBtnSaveEditText">Guardar cambios</span>
                </button>
                <button type="button" class="vp-btn-delete" onclick="vpApp.deleteExpense()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    Eliminar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.vpApp = (function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const gastos = @json($initialGastos, JSON_UNESCAPED_UNICODE);
        const urls = {
            store: '{{ route('admin.viatics.expense', $viatic) }}',
            update: '{{ route('admin.viatics.expense.update', ['viatic' => $viatic->id, 'expense' => '__ID__']) }}',
            destroy: '{{ route('admin.viatics.expense.destroy', ['viatic' => $viatic->id, 'expense' => '__ID__']) }}',
        };

        const icons = {
            toll: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20M4 12v6h16v-6M8 12V8a4 4 0 0 1 8 0v4"/></svg>`,
            fuel: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18M3 22h12M15 8h2a2 2 0 0 1 2 2v8a2 2 0 0 0 2 2 2 2 0 0 0 2-2V9.5L19 5"/></svg>`,
            meal: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2M7 2v20M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3z"/></svg>`,
            other: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>`,
        };

        function formatMoney(val) {
            return '$' + Number(val || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function updateSummary() {
            const byType = { toll: 0, fuel: 0, meal: 0, other: 0 };
            gastos.forEach(g => { if (byType[g.type] !== undefined) byType[g.type] += Number(g.amount); });
            document.getElementById('vpSummaryToll').textContent = formatMoney(byType.toll);
            document.getElementById('vpSummaryFuel').textContent = formatMoney(byType.fuel);
            document.getElementById('vpSummaryMeal').textContent = formatMoney(byType.meal);
            document.getElementById('vpSummaryOther').textContent = formatMoney(byType.other);
        }

        function updateTotal() {
            const total = gastos.reduce((sum, g) => sum + Number(g.amount), 0);
            document.getElementById('vpTotalAmount').textContent = formatMoney(total);
            document.getElementById('vpCountText').textContent = gastos.length + ' gastos';
            document.getElementById('vpListCount').textContent = gastos.length;
            updateSummary();
        }

        function renderList() {
            const list = document.getElementById('vpList');
            const empty = document.getElementById('vpEmpty');
            list.innerHTML = '';
            if (gastos.length === 0) {
                list.style.display = 'none';
                empty.style.display = 'block';
                updateTotal();
                return;
            }
            list.style.display = 'flex';
            empty.style.display = 'none';
            gastos.forEach(g => {
                const row = document.createElement('div');
                row.className = 'vp-row';
                row.innerHTML = `
                    <div class="vp-row-icon ${g.type}">${icons[g.type]}</div>
                    <div class="vp-row-info">
                        <p class="vp-row-label">${g.label}</p>
                        <p class="vp-row-time">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            <span>${g.time_label}</span>
                        </p>
                    </div>
                    <div class="vp-row-right">
                        <span class="vp-row-amount">${formatMoney(g.amount)}</span>
                        <button type="button" class="vp-row-edit" onclick="vpApp.openEdit(${g.id})">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                    </div>
                `;
                list.appendChild(row);
            });
            updateTotal();
        }

        function setType(scope, type) {
            document.getElementById(scope === 'add' ? 'vpAddType' : 'vpEditType').value = type;
            const row = document.getElementById(scope === 'add' ? 'vpAddTypeRow' : 'vpEditTypeRow');
            row.querySelectorAll('.vp-type-btn').forEach(btn => {
                btn.classList.toggle('selected', btn.dataset.type === type);
            });
        }

        function openOverlay(id) {
            document.getElementById(id).classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeOverlay(id) {
            document.getElementById(id).classList.remove('open');
            document.body.style.overflow = '';
        }

        return {
            openAdd: function() {
                document.getElementById('vpAddAmount').value = '';
                document.getElementById('vpAddLabel').value = '';
                setType('add', 'toll');
                openOverlay('vpAddOverlay');
            },
            closeAdd: function() { closeOverlay('vpAddOverlay'); },
            openEdit: function(id) {
                const g = gastos.find(x => x.id === id);
                if (!g) return;
                document.getElementById('vpEditId').value = g.id;
                document.getElementById('vpEditAmount').value = g.amount;
                document.getElementById('vpEditLabel').value = g.label;
                setType('edit', g.type);
                openOverlay('vpEditOverlay');
            },
            closeEdit: function() { closeOverlay('vpEditOverlay'); },
            selectType: function(btn, type, scope) {
                setType(scope, type);
            },
            submitAdd: async function(e) {
                e.preventDefault();
                const btn = document.getElementById('vpBtnSaveAdd');
                const btnText = document.getElementById('vpBtnSaveAddText');
                btn.disabled = true;
                btnText.textContent = 'Guardando...';
                try {
                    const res = await fetch(urls.store, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({
                            type: document.getElementById('vpAddType').value,
                            amount: document.getElementById('vpAddAmount').value,
                            label: document.getElementById('vpAddLabel').value,
                        }),
                    });
                    const result = await res.json();
                    if (!res.ok) throw new Error(result.message || 'Error al guardar');
                    gastos.push(result.gasto);
                    renderList();
                    closeOverlay('vpAddOverlay');
                    if (window.showToast) window.showToast('Gasto agregado: ' + formatMoney(result.gasto.amount), 'ok');
                } catch (err) {
                    if (window.showToast) window.showToast(err.message, 'warn');
                    else alert(err.message);
                } finally {
                    btn.disabled = false;
                    btnText.textContent = 'Guardar gasto';
                }
            },
            submitEdit: async function(e) {
                e.preventDefault();
                const id = Number(document.getElementById('vpEditId').value);
                const btn = document.getElementById('vpBtnSaveEdit');
                const btnText = document.getElementById('vpBtnSaveEditText');
                btn.disabled = true;
                btnText.textContent = 'Guardando...';
                try {
                    const res = await fetch(urls.update.replace('__ID__', id), {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({
                            type: document.getElementById('vpEditType').value,
                            amount: document.getElementById('vpEditAmount').value,
                            label: document.getElementById('vpEditLabel').value,
                        }),
                    });
                    const result = await res.json();
                    if (!res.ok) throw new Error(result.message || 'Error al actualizar');
                    const idx = gastos.findIndex(g => g.id === id);
                    if (idx > -1) gastos[idx] = result.gasto;
                    renderList();
                    closeOverlay('vpEditOverlay');
                    if (window.showToast) window.showToast('Gasto actualizado', 'ok');
                } catch (err) {
                    if (window.showToast) window.showToast(err.message, 'warn');
                    else alert(err.message);
                } finally {
                    btn.disabled = false;
                    btnText.textContent = 'Guardar cambios';
                }
            },
            deleteExpense: async function() {
                const id = Number(document.getElementById('vpEditId').value);
                if (!id || !confirm('¿Eliminar este gasto?')) return;
                try {
                    const res = await fetch(urls.destroy.replace('__ID__', id), {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    });
                    const result = await res.json();
                    if (!res.ok) throw new Error(result.message || 'Error al eliminar');
                    const idx = gastos.findIndex(g => g.id === id);
                    if (idx > -1) gastos.splice(idx, 1);
                    renderList();
                    closeOverlay('vpEditOverlay');
                    if (window.showToast) window.showToast('Gasto eliminado', 'ok');
                } catch (err) {
                    if (window.showToast) window.showToast(err.message, 'warn');
                    else alert(err.message);
                }
            },
        };
    })();
</script>
@endsection

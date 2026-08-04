@extends('layouts.dashboard')
@section('title', 'Viaje en Curso')
@section('page-title', 'Viaje en Curso')
@section('page-sub', 'Gastos del viaje en tiempo real')

@push('head')
<style>
    [x-cloak] { display: none !important; }
    .tp-page {
        max-width: 100%; margin: 0; padding: 0 4px;
    }

    /* Header */
    .tp-header {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 6px;
    }
    .tp-back {
        display: inline-flex; align-items: center; justify-content: center;
        width: 42px; height: 42px; border-radius: 12px;
        border: 1.5px solid #94a3b8; background: var(--surface);
        color: var(--text); text-decoration: none; flex: 0 0 auto;
        transition: all .15s;
    }
    .tp-back:hover { background: var(--surface-2); border-color: var(--primary); color: var(--primary); }
    .tp-back svg { width: 20px; height: 20px; }
    .tp-title-row {
        display: flex; align-items: center; gap: 10px; flex: 1;
    }
    .tp-title {
        font-size: 22px; font-weight: 800; color: var(--primary);
        margin: 0; line-height: 1.2;
    }
    .tp-live-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 12px; border-radius: 999px;
        background: #e6ffe6; color: #15803d;
        font-size: 11px; font-weight: 700;
        border: 1.5px solid #22c55e;
    }
    .tp-live-badge .pulse {
        width: 7px; height: 7px; border-radius: 50%; background: #22c55e;
        animation: tpPulse 1.5s ease-in-out infinite;
    }
    @keyframes tpPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: .5; transform: scale(1.3); }
    }

    .tp-subtitle {
        font-size: 14px; color: var(--muted); margin: 0 0 20px;
        display: flex; align-items: center; gap: 6px;
    }
    .tp-subtitle svg { width: 15px; height: 15px; }

    /* Total card */
    .tp-total-card {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 18px; padding: 24px 20px; text-align: center;
        box-shadow: 0 4px 16px rgba(0,0,0,.05);
        margin-bottom: 20px; position: relative; overflow: hidden;
    }
    .tp-total-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, var(--primary), #4da3ff);
    }
    .tp-total-label {
        font-size: 12px; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: .05em; margin: 0 0 8px;
    }
    .tp-total-amount {
        font-size: 38px; font-weight: 800; color: var(--primary);
        line-height: 1; margin: 0;
    }
    .tp-total-meta {
        font-size: 12px; color: var(--muted); margin: 10px 0 0;
        display: flex; align-items: center; justify-content: center; gap: 5px;
    }
    .tp-total-meta svg { width: 14px; height: 14px; }

    /* Expense list */
    .tp-list-title {
        font-size: 14px; font-weight: 800; margin: 0 0 12px;
        display: flex; align-items: center; gap: 8px;
    }
    .tp-list-title svg { width: 18px; height: 18px; color: var(--primary); }
    .tp-list-count {
        margin-left: auto; font-size: 12px; font-weight: 700;
        color: var(--muted); background: var(--surface-2);
        padding: 2px 10px; border-radius: 999px;
        border: 1px solid #94a3b8;
    }

    .tp-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 24px; }
    .tp-row {
        display: flex; align-items: center; gap: 12px;
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 14px; padding: 12px 14px;
        transition: border .15s, box-shadow .15s;
    }
    .tp-row:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(0,0,0,.05); }

    .tp-row-icon {
        width: 40px; height: 40px; border-radius: 11px;
        display: flex; align-items: center; justify-content: center;
        flex: 0 0 auto; border: 1.5px solid #94a3b8;
    }
    .tp-row-icon.toll { background: #fef9c3; color: #a16207; }
    .tp-row-icon.fuel { background: #dbeafe; color: #2563eb; }
    .tp-row-icon.meal { background: #fce7f3; color: #db2777; }
    .tp-row-icon.other { background: var(--primary-soft); color: var(--primary); }
    .tp-row-icon svg { width: 20px; height: 20px; }

    .tp-row-info { flex: 1; min-width: 0; }
    .tp-row-label { font-size: 14px; font-weight: 700; margin: 0; color: var(--text); }
    .tp-row-time {
        font-size: 11px; color: var(--muted); margin: 2px 0 0;
        display: flex; align-items: center; gap: 4px;
    }
    .tp-row-time svg { width: 12px; height: 12px; }

    .tp-row-right { display: flex; align-items: center; gap: 8px; flex: 0 0 auto; }
    .tp-row-amount { font-size: 15px; font-weight: 800; color: var(--primary); }
    .tp-row-edit {
        width: 32px; height: 32px; border-radius: 8px;
        border: 1.5px solid #94a3b8; background: var(--surface);
        color: var(--muted); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: all .15s;
    }
    .tp-row-edit:hover { background: var(--primary-soft); color: var(--primary); border-color: var(--primary); }
    .tp-row-edit svg { width: 15px; height: 15px; }

    /* Empty expenses */
    .tp-empty-list {
        text-align: center; padding: 30px 20px; color: var(--muted);
        border: 2px dashed #94a3b8; border-radius: 14px; margin-bottom: 24px;
    }
    .tp-empty-list svg { width: 32px; height: 32px; margin-bottom: 8px; opacity: .4; }
    .tp-empty-list p { margin: 0; font-size: 14px; font-weight: 600; }
    .tp-empty-list span { font-size: 12px; display: block; margin-top: 3px; }

    /* Add expense modal */
    .tp-overlay {
        display: none; position: fixed; inset: 0; background: rgba(2,6,23,.45);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
        z-index: 90; align-items: flex-end; justify-content: center;
    }
    .tp-overlay.open { display: flex; }
    .tp-modal {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 20px 20px 0 0; width: 100%; max-width: 480px;
        padding: 24px 20px 20px; animation: tpSlideUp .3s cubic-bezier(.22,1,.36,1);
        max-height: 85vh; overflow-y: auto;
    }
    @media (min-width: 768px) {
        .tp-overlay { align-items: center; }
        .tp-modal {
            max-width: 520px; border-radius: 20px;
            animation: tpFadeIn .25s ease;
        }
    }
    @keyframes tpFadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes tpSlideUp {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
    }
    .tp-modal-handle {
        width: 40px; height: 4px; border-radius: 2px; background: #cbd5e1;
        margin: 0 auto 16px;
    }
    .tp-modal-title {
        font-size: 18px; font-weight: 800; margin: 0 0 20px;
        color: var(--primary);
    }

    .tp-type-row {
        display: flex; gap: 8px; margin-bottom: 18px; overflow-x: auto;
        -webkit-overflow-scrolling: touch; padding-bottom: 2px;
    }
    .tp-type-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 10px 16px; border-radius: 999px;
        border: 2px solid #94a3b8; background: var(--surface);
        cursor: pointer; transition: all .15s; font-family: inherit;
        flex: 0 0 auto; white-space: nowrap;
    }
    .tp-type-btn svg { width: 18px; height: 18px; color: var(--muted); }
    .tp-type-btn span { font-size: 13px; font-weight: 700; color: var(--text); }
    .tp-type-btn:hover { border-color: var(--primary); }
    .tp-type-btn.selected {
        background: var(--primary); border-color: var(--primary);
        box-shadow: 0 3px 12px rgba(0,122,255,.22);
    }
    .tp-type-btn.selected svg { color: #fff; }
    .tp-type-btn.selected span { color: #fff; }

    .tp-field { margin-bottom: 14px; }
    .tp-label {
        display: block; font-size: 12px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .04em;
        color: var(--muted); margin: 0 0 6px;
    }
    .tp-input {
        width: 100%; padding: 14px; border: 2px solid #94a3b8;
        border-radius: 12px; font-size: 16px; font-family: inherit;
        background: var(--surface); color: var(--text);
        outline: none; transition: border .15s, box-shadow .15s;
        -webkit-appearance: none; appearance: none;
    }
    .tp-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,122,255,.12); }
    .tp-input::placeholder { color: #cbd5e1; }
    .tp-amount-wrap { position: relative; display: flex; align-items: center; }
    .tp-amount-wrap .tp-prefix {
        position: absolute; left: 16px; font-size: 22px;
        font-weight: 800; color: var(--muted); pointer-events: none;
    }
    .tp-amount-wrap input {
        padding-left: 42px; padding-top: 16px; padding-bottom: 16px;
        font-size: 22px; font-weight: 800; text-align: left;
    }
    .tp-upload-mini {
        border: 2.5px dashed #94a3b8; border-radius: 14px;
        padding: 22px 16px; text-align: center; cursor: pointer;
        transition: border-color .15s, background .15s;
        background: var(--surface-2); margin-bottom: 18px;
    }
    .tp-upload-mini:hover { border-color: var(--primary); background: var(--primary-soft); }
    .tp-upload-mini-icon {
        width: 44px; height: 44px; border-radius: 12px;
        background: var(--primary-soft); color: var(--primary);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 8px; border: 1.5px solid #94a3b8;
    }
    .tp-upload-mini-icon svg { width: 22px; height: 22px; }
    .tp-upload-mini p { margin: 0; font-size: 13px; font-weight: 700; color: var(--text); }
    .tp-upload-mini span { font-size: 11px; color: var(--muted); display: block; margin-top: 2px; }

    .tp-save-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%; padding: 16px; border: none; border-radius: 14px;
        background: var(--primary); color: #fff;
        font-size: 16px; font-weight: 800; cursor: pointer;
        font-family: inherit; transition: background .15s;
        box-shadow: 0 4px 16px rgba(0,122,255,.25);
    }
    .tp-save-btn:hover { background: var(--primary-strong); }
    .tp-save-btn svg { width: 20px; height: 20px; }

    /* Edit modal extras */
    .tp-photo-preview {
        position: relative; display: inline-block; margin-bottom: 18px;
    }
    .tp-photo-preview-thumb {
        width: 80px; height: 80px; border-radius: 12px;
        border: 2px solid #94a3b8; overflow: hidden;
        background: var(--surface-2);
        display: flex; align-items: center; justify-content: center;
    }
    .tp-photo-preview-thumb svg { width: 28px; height: 28px; color: #94a3b8; }
    .tp-photo-preview-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .tp-photo-remove {
        position: absolute; top: -6px; right: -6px;
        width: 24px; height: 24px; border-radius: 50%;
        background: #ef4444; color: #fff; border: 2px solid var(--surface);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: transform .15s;
        font-size: 0; line-height: 0;
    }
    .tp-photo-remove:hover { transform: scale(1.15); }
    .tp-photo-remove svg { width: 12px; height: 12px; }
    .tp-edit-actions { display: flex; gap: 10px; }
    .tp-btn-save {
        flex: 1; padding: 15px; border: none; border-radius: 14px;
        background: var(--primary); color: #fff;
        font-size: 15px; font-weight: 800; cursor: pointer;
        font-family: inherit; transition: background .15s;
        box-shadow: 0 4px 14px rgba(0,122,255,.2);
        display: flex; align-items: center; justify-content: center; gap: 7px;
    }
    .tp-btn-save:hover { background: var(--primary-strong); }
    .tp-btn-save svg { width: 18px; height: 18px; }
    .tp-btn-delete {
        flex: 0 0 auto; padding: 15px 20px; border-radius: 14px;
        background: transparent; color: #ef4444;
        border: 2px solid #ef4444;
        font-size: 14px; font-weight: 700; cursor: pointer;
        font-family: inherit; transition: all .15s;
        display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .tp-btn-delete:hover { background: #fee2e2; }
    .tp-btn-delete svg { width: 17px; height: 17px; }

    /* Bottom buttons */
    .tp-bottom { display: flex; flex-direction: column; gap: 10px; margin-top: 8px; }
    .tp-add-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%; padding: 16px; border: none; border-radius: 14px;
        background: var(--primary); color: #fff;
        font-size: 16px; font-weight: 800; cursor: pointer;
        font-family: inherit; transition: background .15s;
        box-shadow: 0 4px 16px rgba(0,122,255,.25);
    }
    .tp-add-btn:hover { background: var(--primary-strong); }
    .tp-add-btn svg { width: 20px; height: 20px; }

    .tp-finish-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%; padding: 14px; border-radius: 14px;
        background: transparent; color: var(--text);
        border: 2px solid #94a3b8;
        font-size: 15px; font-weight: 700; cursor: pointer;
        font-family: inherit; transition: all .15s;
        text-decoration: none;
    }
    .tp-finish-btn:hover { border-color: #22c55e; color: #15803d; background: #f0fdf4; }
    .tp-finish-btn svg { width: 18px; height: 18px; }

    /* Desktop responsive */
    .tp-layout-desktop { display: block; }
    @media (min-width: 768px) {
        .tp-page { max-width: 100%; padding: 0; }
        .tp-title { font-size: 26px; }
        .tp-subtitle { font-size: 15px; }
        .tp-total-card { padding: 32px 28px; }
        .tp-total-amount { font-size: 48px; }
        .tp-total-label { font-size: 14px; }
        .tp-list { gap: 12px; }
        .tp-row { padding: 16px 20px; }
        .tp-row-label { font-size: 15px; }
        .tp-row-amount { font-size: 17px; }
        .tp-row-icon { width: 48px; height: 48px; }
        .tp-row-icon svg { width: 24px; height: 24px; }
        .tp-add-btn { max-width: 300px; margin: 0 auto; }
        .tp-finish-btn { max-width: 300px; margin: 0 auto; }
        .tp-bottom { gap: 12px; }
    }
    @media (min-width: 1024px) {
        .tp-layout-desktop {
            display: grid; grid-template-columns: 1fr 1fr; gap: 24px;
            align-items: flex-start;
        }
        .tp-total-card { margin-bottom: 0; }
        .tp-list-section { margin-top: 24px; grid-column: 1 / -1; }
        .tp-bottom { grid-column: 1 / -1; }
    }
</style>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.0/dist/cdn.min.js"></script>
@endendpush

@php
    $initialData = [
        'id' => $trip->id,
        'total' => (float) $trip->total_computed,
        'gastos' => $trip->expenses->map(fn($e) => [
            'id' => $e->id,
            'type' => $e->type,
            'label' => $e->label,
            'amount' => (float) $e->amount,
            'time_label' => $e->created_at->isToday()
                ? 'Hoy, ' . $e->created_at->format('g:i A')
                : $e->created_at->format('d/m/Y, g:i A'),
        ])->values(),
        'urls' => [
            'store' => route('admin.trips.expense', $trip),
            'update' => route('admin.trips.expense.update', ['trip' => $trip->id, 'expense' => '__ID__']),
            'destroy' => route('admin.trips.expense.destroy', ['trip' => $trip->id, 'expense' => '__ID__']),
        ],
    ];
@endphp
@section('content')
<div class="tp-page" x-data="viajeGastos({{ Js::from($initialData) }})">

    {{-- Header --}}
    <div class="tp-header">
        <a href="{{ route('admin.viatics.index') }}" class="tp-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div class="tp-title-row">
            <h1 class="tp-title">Viaje en Curso</h1>
            <span class="tp-live-badge">
                <span class="pulse"></span>
                En curso
            </span>
        </div>
    </div>
    <p class="tp-subtitle">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        {{ $trip->place ?: 'Sin lugar' }} · {{ $trip->vehicle_name ?: 'Sin vehículo' }}
    </p>

    {{-- Desktop layout: total card + expense list side by side --}}
    <div class="tp-layout-desktop">

    {{-- Total card --}}
    <div class="tp-total-card">
        <p class="tp-total-label">Total acumulado</p>
        <p class="tp-total-amount" x-text="formatMoney(total)">${{ number_format((float) $trip->total_computed, 2) }}</p>
        <p class="tp-total-meta">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            <span x-text="count + ' gastos'">{{ $trip->expenses->count() }} gastos</span> · Iniciado {{ $trip->started_at?->format('H:i') }}
        </p>
    </div>

    {{-- Expense list --}}
    </div>

    {{-- Expense list section --}}
    <div class="tp-list-section">
    <h3 class="tp-list-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/></svg>
        Gastos del viaje
        <span class="tp-list-count" x-text="count">{{ $trip->expenses->count() }}</span>
    </h3>

    <template x-if="gastos.length > 0">
        <div class="tp-list">
            <template x-for="g in gastos" :key="g.id">
                <div class="tp-row">
                    <div class="tp-row-icon" :class="g.type">
                        <template x-if="g.type === 'toll'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20M4 12v6h16v-6M8 12V8a4 4 0 0 1 8 0v4"/></svg></template>
                        <template x-if="g.type === 'fuel'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18M3 22h12M15 8h2a2 2 0 0 1 2 2v8a2 2 0 0 0 2 2 2 2 0 0 0 2-2V9.5L19 5"/></svg></template>
                        <template x-if="g.type === 'meal'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2M7 2v20M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3z"/></svg></template>
                        <template x-if="g.type === 'other'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg></template>
                    </div>
                    <div class="tp-row-info">
                        <p class="tp-row-label" x-text="g.label"></p>
                        <p class="tp-row-time">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            <span x-text="g.time_label"></span>
                        </p>
                    </div>
                    <div class="tp-row-right">
                        <span class="tp-row-amount" x-text="formatMoney(g.amount)"></span>
                        <button type="button" class="tp-row-edit" @click="openEdit(g)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </template>
    <template x-if="gastos.length === 0">
        <div class="tp-empty-list">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
            <p>No hay gastos registrados</p>
            <span>Agrega el primer gasto del viaje con el botón de abajo</span>
        </div>
    </template>
    </div>{{-- /tp-list-section --}}
    </div>{{-- /tp-layout-desktop --}}

    {{-- Bottom buttons --}}
    <div class="tp-bottom">
        <button type="button" class="tp-add-btn" @click="openAdd()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Agregar gasto
        </button>
        <form method="POST" action="{{ route('admin.trips.finish', $trip) }}">
            @csrf
            <button type="submit" class="tp-finish-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Finalizar viaje
            </button>
        </form>
    </div>

</div>

{{-- Add Expense Modal --}}
<div class="tp-overlay" :class="{ 'open': showAdd }" @click.self="showAdd = false" x-cloak>
    <div class="tp-modal">
        <div class="tp-modal-handle"></div>
        <h2 class="tp-modal-title">Agregar Gasto</h2>

        <form @submit.prevent="submitAdd()">
            {{-- Type selector: pill-shaped --}}
            <div class="tp-type-row">
                <button type="button" class="tp-type-btn" :class="{ selected: form.type === 'toll' }" @click="form.type = 'toll'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20M4 12v6h16v-6M8 12V8a4 4 0 0 1 8 0v4"/></svg>
                    <span>Caseta</span>
                </button>
                <button type="button" class="tp-type-btn" :class="{ selected: form.type === 'fuel' }" @click="form.type = 'fuel'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18M3 22h12M15 8h2a2 2 0 0 1 2 2v8a2 2 0 0 0 2 2 2 2 0 0 0 2-2V9.5L19 5"/></svg>
                    <span>Gasolina</span>
                </button>
                <button type="button" class="tp-type-btn" :class="{ selected: form.type === 'meal' }" @click="form.type = 'meal'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2M7 2v20M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3z"/></svg>
                    <span>Viático</span>
                </button>
                <button type="button" class="tp-type-btn" :class="{ selected: form.type === 'other' }" @click="form.type = 'other'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    <span>Adicional</span>
                </button>
            </div>

            {{-- Amount --}}
            <div class="tp-field">
                <label class="tp-label">Monto</label>
                <div class="tp-amount-wrap">
                    <span class="tp-prefix">$</span>
                    <input type="number" x-model.number="form.amount" class="tp-input" placeholder="0.00" step="0.01" min="0" inputmode="decimal" required>
                </div>
            </div>

            {{-- Description --}}
            <div class="tp-field">
                <label class="tp-label">Descripción (opcional)</label>
                <input type="text" x-model="form.label" class="tp-input" placeholder="Ej. Caseta - Guadalajara Norte">
            </div>

            {{-- Photo upload --}}
            <div class="tp-upload-mini">
                <div class="tp-upload-mini-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                </div>
                <p>Agregar foto del ticket</p>
                <span>Toca para tomar o seleccionar una foto</span>
            </div>

            {{-- Save button --}}
            <button type="submit" class="tp-save-btn" :disabled="loading">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span x-text="loading ? 'Guardando...' : 'Guardar gasto'"></span>
            </button>
        </form>
    </div>
</div>

{{-- Edit Expense Modal --}}
<div class="tp-overlay" :class="{ 'open': showEdit }" @click.self="showEdit = false" x-cloak>
    <div class="tp-modal">
        <div class="tp-modal-handle"></div>
        <h2 class="tp-modal-title">Editar Gasto</h2>

        <form @submit.prevent="submitEdit()">
            <input type="hidden" x-model="editForm.id">

            {{-- Type selector --}}
            <div class="tp-type-row">
                <button type="button" class="tp-type-btn" :class="{ selected: editForm.type === 'toll' }" @click="editForm.type = 'toll'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20M4 12v6h16v-6M8 12V8a4 4 0 0 1 8 0v4"/></svg>
                    <span>Caseta</span>
                </button>
                <button type="button" class="tp-type-btn" :class="{ selected: editForm.type === 'fuel' }" @click="editForm.type = 'fuel'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18M3 22h12M15 8h2a2 2 0 0 1 2 2v8a2 2 0 0 0 2 2 2 2 0 0 0 2-2V9.5L19 5"/></svg>
                    <span>Gasolina</span>
                </button>
                <button type="button" class="tp-type-btn" :class="{ selected: editForm.type === 'meal' }" @click="editForm.type = 'meal'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2M7 2v20M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3z"/></svg>
                    <span>Viático</span>
                </button>
                <button type="button" class="tp-type-btn" :class="{ selected: editForm.type === 'other' }" @click="editForm.type = 'other'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    <span>Adicional</span>
                </button>
            </div>

            {{-- Amount --}}
            <div class="tp-field">
                <label class="tp-label">Monto</label>
                <div class="tp-amount-wrap">
                    <span class="tp-prefix">$</span>
                    <input type="number" x-model.number="editForm.amount" class="tp-input" placeholder="0.00" step="0.01" min="0" inputmode="decimal" required>
                </div>
            </div>

            {{-- Description --}}
            <div class="tp-field">
                <label class="tp-label">Descripción (opcional)</label>
                <input type="text" x-model="editForm.label" class="tp-input" placeholder="Ej. Caseta - Guadalajara Norte">
            </div>

            {{-- Photo preview --}}
            <div class="tp-photo-preview">
                <div class="tp-photo-preview-thumb">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/></svg>
                </div>
                <button type="button" class="tp-photo-remove" aria-label="Quitar foto">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Actions: Save + Delete --}}
            <div class="tp-edit-actions">
                <button type="submit" class="tp-btn-save" :disabled="loading">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <span x-text="loading ? 'Guardando...' : 'Guardar cambios'"></span>
                </button>
                <button type="button" class="tp-btn-delete" @click="deleteExpense()" :disabled="loading">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    Eliminar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function viajeGastos(data) {
        return {
            gastos: data.gastos,
            total: data.total,
            count: data.gastos.length,
            loading: false,
            showAdd: false,
            showEdit: false,
            form: { type: 'toll', amount: '', label: '' },
            editForm: { id: null, type: 'toll', amount: '', label: '' },

            get csrfToken() {
                return document.querySelector('meta[name="csrf-token"]').content;
            },

            formatMoney(val) {
                return '$' + Number(val || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            openAdd() {
                this.form = { type: 'toll', amount: '', label: '' };
                this.showAdd = true;
            },

            async submitAdd() {
                if (this.loading) return;
                this.loading = true;
                try {
                    const res = await fetch(data.urls.store, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            type: this.form.type,
                            amount: this.form.amount,
                            label: this.form.label,
                        }),
                    });
                    const result = await res.json();
                    if (!res.ok) throw new Error(result.message || 'Error');
                    this.gastos.push(result.gasto);
                    this.total = result.total;
                    this.count = result.count;
                    this.showAdd = false;
                    if (window.showToast) window.showToast('Gasto agregado: ' + this.formatMoney(result.gasto.amount), 'ok');
                } catch (e) {
                    if (window.showToast) window.showToast(e.message, 'warn');
                } finally {
                    this.loading = false;
                }
            },

            openEdit(g) {
                this.editForm = { id: g.id, type: g.type, amount: g.amount, label: g.label };
                this.showEdit = true;
            },

            async submitEdit() {
                if (this.loading || !this.editForm.id) return;
                this.loading = true;
                try {
                    const url = data.urls.update.replace('__ID__', this.editForm.id);
                    const res = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            type: this.editForm.type,
                            amount: this.editForm.amount,
                            label: this.editForm.label,
                        }),
                    });
                    const result = await res.json();
                    if (!res.ok) throw new Error(result.message || 'Error');
                    const idx = this.gastos.findIndex(g => g.id === result.gasto.id);
                    if (idx > -1) this.gastos[idx] = result.gasto;
                    this.total = result.total;
                    this.showEdit = false;
                    if (window.showToast) window.showToast('Gasto actualizado', 'ok');
                } catch (e) {
                    if (window.showToast) window.showToast(e.message, 'warn');
                } finally {
                    this.loading = false;
                }
            },

            async deleteExpense() {
                if (this.loading || !this.editForm.id) return;
                if (!confirm('¿Eliminar este gasto?')) return;
                this.loading = true;
                try {
                    const url = data.urls.destroy.replace('__ID__', this.editForm.id);
                    const res = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                    });
                    const result = await res.json();
                    if (!res.ok) throw new Error(result.message || 'Error');
                    this.gastos = this.gastos.filter(g => g.id !== this.editForm.id);
                    this.total = result.total;
                    this.count = result.count;
                    this.showEdit = false;
                    if (window.showToast) window.showToast('Gasto eliminado', 'ok');
                } catch (e) {
                    if (window.showToast) window.showToast(e.message, 'warn');
                } finally {
                    this.loading = false;
                }
            },
        };
    }
</script>
@endsection

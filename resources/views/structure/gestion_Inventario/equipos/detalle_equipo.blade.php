@extends('layouts.dashboard')

@php
    $equipmentData = array_merge([
        'code' => '',
        'name' => '',
        'category' => '',
        'brand' => '',
        'model' => '',
        'serial_number' => '',
        'warehouse' => '',
        'description' => '',
        'stock_current' => 0,
        'stock_min' => 0,
        'stock_max' => 0,
        'status' => 'Activo',
        'thumb' => 'tower',
    ], $equipment ?? []);
@endphp

@section('title', 'Detalle del equipo')
@section('page-title', 'Detalle del equipo')
@section('page-sub', 'Gestion de Inventario > Equipos > Detalle del equipo')

@push('head')
<style>
    .equipment-detail { max-width: 1200px; margin: 0 auto; }
    .equipment-detail .rgrid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
    @media (max-width: 820px) { .equipment-detail .rgrid-2 { grid-template-columns: 1fr; } }
    .detail-card {
        padding: 20px;
        border-radius: 14px;
        background: linear-gradient(145deg, rgba(8,18,40,0.88), rgba(4,12,30,0.88));
        border: 1px solid rgba(0,168,255,0.55);
        box-shadow: 0 8px 28px rgba(0,0,0,0.35), 0 0 14px rgba(0,168,255,0.2), inset 0 1px 0 rgba(255,255,255,0.04);
    }
    .detail-card__title { font-size: 16px; font-weight: 700; margin: 0 0 18px; color: #00A8FF; }
    .detail-thumb-box {
        width: 120px;
        height: 120px;
        display: grid;
        place-items: center;
        border: 1px dashed rgba(0,168,255,0.55);
        border-radius: 12px;
        background: rgba(4,10,24,0.6);
        overflow: hidden;
        padding: 8px;
    }
    .detail-thumb-box svg { max-width: 100px; max-height: 100px; }
    .detail-name { margin: 0 0 8px; font-size: 1.25rem; font-weight: 800; color: #fff; }
    .detail-muted { color: rgba(255,255,255,0.55); margin: 0; }
    .detail-list { display: grid; gap: 10px; margin: 0; }
    .detail-row { display: grid; grid-template-columns: 120px minmax(0, 1fr); gap: 12px; }
    .detail-label { color: rgba(255,255,255,0.55); margin: 0; }
    .detail-value { margin: 0; font-weight: 700; color: #fff; }
    .detail-stat-label { color: rgba(255,255,255,0.55); margin: 0 0 4px; font-size: 0.9rem; font-weight: 700; }
    .detail-stat-value { margin: 0; font-size: 1.5rem; font-weight: 800; color: #fff; }
    .detail-actions { display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 18px; }
    .detail-btn {
        background: linear-gradient(135deg, #00A8FF, #7C3AED);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.15);
        padding: 10px 18px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 0 12px rgba(59,130,246,0.35), 0 0 30px rgba(124,58,237,0.2);
        transition: all 0.2s ease;
    }
    .detail-btn:hover { filter: brightness(1.1); }
    .detail-ghost {
        padding: 10px 18px;
        border: 1px solid rgba(0,168,255,0.55);
        border-radius: 12px;
        background: rgba(8,18,40,0.45);
        color: #00A8FF;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background .16s ease, border-color .16s ease;
    }
    .detail-ghost:hover { background: rgba(0,168,255,0.14); border-color: #00A8FF; }
    :root[data-theme="light"] .detail-card { background: linear-gradient(145deg, rgba(15,23,42,0.04), rgba(15,23,42,0.08)); border-color: rgba(15,23,42,0.14); }
    :root[data-theme="light"] .detail-card__title { color: var(--primary, #00A8FF); }
    :root[data-theme="light"] .detail-name { color: var(--text); }
    :root[data-theme="light"] .detail-muted { color: var(--muted); }
    :root[data-theme="light"] .detail-label { color: var(--muted); }
    :root[data-theme="light"] .detail-value { color: var(--text); }
    :root[data-theme="light"] .detail-stat-label { color: var(--muted); }
    :root[data-theme="light"] .detail-stat-value { color: var(--text); }
    :root[data-theme="light"] .detail-thumb-box { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.18); }
    :root[data-theme="light"] .detail-ghost { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.18); color: var(--text); }
</style>
@endpush

@section('content')
    <div class="equipment-detail">
        <div class="detail-actions">
            <a href="{{ route('inventory.equipos.index') }}" class="detail-ghost" style="text-decoration:none;">Volver</a>
            <a href="{{ route('inventory.equipos.edit', ['equipo' => $equipmentData['code']]) }}" class="detail-btn" style="text-decoration:none;">Editar equipo</a>
        </div>

        <div class="rgrid-2">
            <div class="detail-card">
                <h3 class="detail-card__title">Informacion general</h3>
                <div style="display:flex; align-items:center; gap:18px; margin-bottom:18px; flex-wrap:wrap;">
                    <div class="detail-thumb-box">
                        @include('structure.gestion_Inventario.equipos.partials.equipment-thumb', ['type' => $equipmentData['thumb']])
                    </div>
                    <div>
                        <h2 class="detail-name">{{ $equipmentData['name'] }}</h2>
                        <p class="detail-muted">Codigo: {{ $equipmentData['code'] }}</p>
                        <p class="detail-muted" style="margin-top:4px;">Estado: {{ $equipmentData['status'] }}</p>
                    </div>
                </div>

                <dl class="detail-list">
                    <div class="detail-row">
                        <dt class="detail-label">Categoria:</dt>
                        <dd class="detail-value">{{ $equipmentData['category'] }}</dd>
                    </div>
                    <div class="detail-row">
                        <dt class="detail-label">Marca:</dt>
                        <dd class="detail-value">{{ $equipmentData['brand'] }}</dd>
                    </div>
                    <div class="detail-row">
                        <dt class="detail-label">Modelo:</dt>
                        <dd class="detail-value">{{ $equipmentData['model'] }}</dd>
                    </div>
                    <div class="detail-row">
                        <dt class="detail-label">No. serie:</dt>
                        <dd class="detail-value">{{ $equipmentData['serial_number'] }}</dd>
                    </div>
                    <div class="detail-row">
                        <dt class="detail-label">Ubicacion:</dt>
                        <dd class="detail-value">{{ $equipmentData['warehouse'] }}</dd>
                    </div>
                    <div class="detail-row">
                        <dt class="detail-label">Descripcion:</dt>
                        <dd class="detail-value">{{ $equipmentData['description'] }}</dd>
                    </div>
                </dl>
            </div>

            <div class="detail-card">
                <h3 class="detail-card__title">Resumen de stock</h3>
                <div style="display:grid; gap:14px;">
                    <div>
                        <p class="detail-stat-label">Stock actual</p>
                        <p class="detail-stat-value">{{ $equipmentData['stock_current'] }} Pza</p>
                    </div>
                    <div>
                        <p class="detail-stat-label">Stock minimo</p>
                        <p class="detail-stat-value">{{ $equipmentData['stock_min'] }} Pza</p>
                    </div>
                    <div>
                        <p class="detail-stat-label">Stock maximo</p>
                        <p class="detail-stat-value">{{ $equipmentData['stock_max'] }} Pza</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

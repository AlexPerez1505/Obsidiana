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
    .equipment-detail {
        display: grid;
        gap: 18px;
    }

    .equipment-detail__bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .equipment-detail__intro {
        color: var(--muted);
        margin: 0;
        font-size: 0.94rem;
        font-weight: 600;
    }

    .equipment-detail__actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .equipment-detail-card {
        display: grid;
        grid-template-columns: minmax(150px, 0.5fr) minmax(320px, 1.5fr) minmax(170px, 0.55fr);
        gap: 28px;
        align-items: center;
        min-height: 260px;
        padding: 30px 44px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: var(--shadow);
    }

    .equipment-detail-image {
        min-height: 170px;
        display: grid;
        place-items: center;
    }

    .equipment-detail-image svg {
        width: min(170px, 92%);
        height: auto;
    }

    .equipment-detail-main {
        min-width: 0;
    }

    .equipment-detail-main h3 {
        color: var(--text);
        font-size: 1.18rem;
        font-weight: 900;
        margin: 0 0 22px;
    }

    .equipment-detail-list {
        display: grid;
        gap: 10px;
        margin: 0;
    }

    .equipment-detail-item {
        display: grid;
        grid-template-columns: 150px minmax(0, 1fr);
        gap: 18px;
        align-items: baseline;
    }

    .equipment-detail-item dt {
        color: var(--muted);
        font-size: 0.95rem;
        font-weight: 700;
        margin: 0;
    }

    .equipment-detail-item dd {
        color: var(--text);
        font-size: 0.9rem;
        font-weight: 900;
        margin: 0;
        overflow-wrap: anywhere;
    }

    .equipment-detail-stock {
        display: grid;
        gap: 18px;
        align-content: center;
    }

    .equipment-detail-stock__label {
        color: var(--muted);
        display: block;
        font-size: 0.96rem;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .equipment-detail-stock__value {
        color: var(--text);
        display: block;
        font-size: 1.25rem;
        font-weight: 900;
        line-height: 1.1;
    }

    @media (max-width: 980px) {
        .equipment-detail-card {
            grid-template-columns: 1fr;
            padding: 24px;
            align-items: start;
        }

        .equipment-detail-image {
            min-height: 120px;
            justify-content: start;
        }

        .equipment-detail-stock {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 680px) {
        .equipment-detail__bar,
        .equipment-detail__actions {
            align-items: stretch;
            justify-content: stretch;
        }

        .equipment-detail__actions,
        .equipment-detail__actions .btn {
            width: 100%;
        }

        .equipment-detail-item {
            grid-template-columns: 1fr;
            gap: 4px;
        }

        .equipment-detail-stock {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    <section class="equipment-detail">
        <div class="equipment-detail__bar">
            <p class="equipment-detail__intro">Consulta el detalle del equipo registrado en inventario.</p>

            <div class="equipment-detail__actions">
                <a href="{{ route('inventory.equipos.index') }}" class="btn btn--ghost" style="text-decoration:none;">
                    Cancelar
                </a>
                <a href="{{ route('inventory.equipos.edit', ['equipo' => $equipmentData['code']]) }}" class="btn" style="text-decoration:none;">
                    Aplicar ajuste
                </a>
            </div>
        </div>

        <article class="equipment-detail-card">
            <div class="equipment-detail-image" aria-label="Imagen de {{ $equipmentData['name'] }}">
                @include('structure.gestion_Inventario.equipos.partials.equipment-thumb', ['type' => $equipmentData['thumb']])
            </div>

            <div class="equipment-detail-main">
                <h3>{{ $equipmentData['name'] }}</h3>

                <dl class="equipment-detail-list">
                    <div class="equipment-detail-item">
                        <dt>Codigo:</dt>
                        <dd>{{ $equipmentData['code'] }}</dd>
                    </div>
                    <div class="equipment-detail-item">
                        <dt>Categoria:</dt>
                        <dd>{{ $equipmentData['category'] }}</dd>
                    </div>
                    <div class="equipment-detail-item">
                        <dt>Marca:</dt>
                        <dd>{{ $equipmentData['brand'] }}</dd>
                    </div>
                    <div class="equipment-detail-item">
                        <dt>Modelo:</dt>
                        <dd>{{ $equipmentData['model'] }}</dd>
                    </div>
                    <div class="equipment-detail-item">
                        <dt>Numero de serie:</dt>
                        <dd>{{ $equipmentData['serial_number'] }}</dd>
                    </div>
                    <div class="equipment-detail-item">
                        <dt>Ubicacion:</dt>
                        <dd>{{ $equipmentData['warehouse'] }}</dd>
                    </div>
                    <div class="equipment-detail-item">
                        <dt>Descripcion:</dt>
                        <dd>{{ $equipmentData['description'] }}</dd>
                    </div>
                </dl>
            </div>

            <aside class="equipment-detail-stock" aria-label="Resumen de stock">
                <div>
                    <span class="equipment-detail-stock__label">Stock actual</span>
                    <strong class="equipment-detail-stock__value">{{ $equipmentData['stock_current'] }} Pza</strong>
                </div>
                <div>
                    <span class="equipment-detail-stock__label">Stock minimo</span>
                    <strong class="equipment-detail-stock__value">{{ $equipmentData['stock_min'] }} Pza</strong>
                </div>
                <div>
                    <span class="equipment-detail-stock__label">Stock maximo</span>
                    <strong class="equipment-detail-stock__value">{{ $equipmentData['stock_max'] }} Pza</strong>
                </div>
                <div>
                    <span class="equipment-detail-stock__label">Estado</span>
                    <strong class="equipment-detail-stock__value">{{ $equipmentData['status'] }}</strong>
                </div>
            </aside>
        </article>
    </section>
@endsection

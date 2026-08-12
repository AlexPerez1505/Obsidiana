@extends('layouts.dashboard')

@section('title', 'Equipos')
@section('page-title', 'Equipos')
@section('page-sub', 'Gestion de Inventario > Equipos')

@php
    $statusTones = [
        'Activo' => 'green',
        'Bueno' => 'green',
        'Mantenimiento' => 'blue',
        'Malo' => 'red',
    ];

    $equipmentRows = collect($equipmentList ?? [])->map(function ($equipo) use ($statusTones) {
        return [
            'code' => $equipo->code,
            'name' => $equipo->name,
            'type' => $equipo->equipmentType?->name ?? 'Sin tipo',
            'location' => $equipo->warehouse ?? 'Sin ubicacion',
            'owner' => $equipo->assigned_to ?? 'Sin asignar',
            'status' => $equipo->status ?? 'Activo',
            'tone' => $statusTones[$equipo->status] ?? 'green',
            'thumb' => $equipo->thumb ?? 'tower',
        ];
    })->all();
@endphp

@push('head')
<style>
    .equipment-page {
        display: grid;
        gap: 18px;
    }

    .equipment-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .equipment-head p {
        margin: 0;
        color: #718096;
        font-size: 14px;
        font-weight: 600;
    }

    .equipment-create {
        min-height: 38px;
        margin-top: 22px;
        padding: 0 14px;
        border-radius: 4px;
        background: #158be8;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 900;
        box-shadow: 0 7px 16px rgba(21, 139, 232, .22);
        white-space: nowrap;
    }

    .equipment-create:hover {
        background: #0879d0;
    }

    .equipment-create svg,
    .equipment-search svg,
    .equipment-action svg {
        width: 16px;
        height: 16px;
        flex: 0 0 auto;
    }

    .equipment-search {
        position: relative;
        padding: 13px 16px;
        border-radius: 18px;
        background: rgba(255, 255, 255, .62);
        border: 1px solid rgba(226, 232, 240, .78);
    }

    .equipment-search svg {
        position: absolute;
        left: 28px;
        top: 50%;
        transform: translateY(-50%);
        color: #718096;
        pointer-events: none;
    }

    .equipment-search input {
        width: 100%;
        height: 40px;
        padding: 0 14px 0 42px;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        background: #f8fafc;
        color: #1f2937;
        font: inherit;
        font-size: 13px;
        outline: none;
    }

    .equipment-search input:focus {
        border-color: #158be8;
        box-shadow: 0 0 0 3px rgba(21, 139, 232, .14);
    }

    .equipment-table-panel {
        overflow: hidden;
        border: 1px solid #a8c5ff;
        border-radius: 5px;
        background: #fff;
    }

    .equipment-table-wrap {
        overflow-x: auto;
    }

    .equipment-table {
        width: 100%;
        min-width: 940px;
        border-collapse: collapse;
        color: #202938;
        font-size: 13px;
    }

    .equipment-table th {
        padding: 17px 16px;
        background: #d8e2ff;
        color: #111827;
        font-size: 12px;
        font-weight: 900;
        text-align: left;
        border-bottom: 1px solid #a8c5ff;
    }

    .equipment-table td {
        height: 70px;
        padding: 11px 16px;
        border-bottom: 1px solid #a8c5ff;
        background: #fff;
        vertical-align: middle;
        font-weight: 600;
    }

    .equipment-thumb {
        width: 82px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .equipment-thumb svg {
        width: 58px;
        height: 46px;
        display: block;
    }

    .equipment-state {
        min-width: 70px;
        min-height: 24px;
        padding: 0 10px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }

    .equipment-state.green {
        color: #16a329;
        border: 1px solid #22c943;
        background: #f7fff8;
    }

    .equipment-state.blue {
        color: #1689ff;
        border: 1px solid #1689ff;
        background: #f5fbff;
    }

    .equipment-state.red {
        color: #ff3131;
        border: 1px solid #ff4b4b;
        background: #fff8f8;
    }

    .equipment-action {
        width: 32px;
        height: 32px;
        border: 0;
        border-radius: 50%;
        background: transparent;
        color: #111827;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .equipment-action:hover {
        background: #eef4ff;
    }

    .equipment-action-menu {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .equipment-action-list {
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        min-width: 210px;
        padding: 6px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, .14);
        display: none;
        z-index: 20;
    }

    .equipment-action-menu.is-open .equipment-action-list {
        display: grid;
        gap: 3px;
    }

    .equipment-action-list a,
    .equipment-action-list button {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        min-height: 34px;
        padding: 0 10px;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: #111827;
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        font-family: inherit;
        text-align: left;
        white-space: nowrap;
        cursor: pointer;
    }

    .equipment-action-list a:hover,
    .equipment-action-list button:hover {
        background: #eef4ff;
        color: #0879d0;
    }

    .equipment-action-list a svg,
    .equipment-action-list button svg {
        width: 15px;
        height: 15px;
        flex: 0 0 auto;
    }

    .equipment-action-list .equipment-action-danger {
        color: #ef4444;
    }

    .equipment-action-list .equipment-action-danger:hover {
        background: #fff1f2;
        color: #dc2626;
    }

    .equipment-foot {
        min-height: 40px;
        padding: 0 16px;
        background: #d7e9ff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        color: #1689ff;
        font-size: 12px;
        font-weight: 700;
    }

    .equipment-foot button {
        border: 0;
        background: transparent;
        color: #1689ff;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    :root[data-theme="dark"] .equipment-search,
    :root[data-theme="dark"] .equipment-table-panel {
        background: var(--surface);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .equipment-search input,
    :root[data-theme="dark"] .equipment-table td {
        background: var(--surface-2);
        color: var(--text);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .equipment-table th {
        background: rgba(10, 132, 255, .18);
        color: var(--text);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .equipment-foot {
        background: rgba(10, 132, 255, .14);
    }

    :root[data-theme="dark"] .equipment-action {
        color: var(--text);
    }

    :root[data-theme="dark"] .equipment-action:hover,
    :root[data-theme="dark"] .equipment-action-list a:hover,
    :root[data-theme="dark"] .equipment-action-list button:hover {
        background: rgba(10, 132, 255, .16);
    }

    :root[data-theme="dark"] .equipment-action-list {
        background: var(--surface);
        border-color: var(--border);
        box-shadow: var(--shadow);
    }

    :root[data-theme="dark"] .equipment-action-list a,
    :root[data-theme="dark"] .equipment-action-list button {
        color: var(--text);
    }

    :root[data-theme="dark"] .equipment-action-list .equipment-action-danger {
        color: #f87171;
    }

    :root[data-theme="dark"] .equipment-action-list .equipment-action-danger:hover {
        background: rgba(248, 113, 113, .14);
        color: #fca5a5;
    }

    :root[data-theme="dark"] .equipment-head p,
    :root[data-theme="dark"] .equipment-search svg {
        color: var(--muted);
    }

    @media (max-width: 760px) {
        .equipment-head {
            align-items: stretch;
            flex-direction: column;
        }

        .equipment-create {
            width: 100%;
            margin-top: 0;
        }
    }
</style>
@endpush

@section('content')
    <section class="equipment-page">
        <div class="equipment-head">
            <div>
                <p>Administra todos los equipos del inventario.</p>
            </div>

            <a href="{{ route('inventory.equipos.create') }}" class="equipment-create">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14"></path>
                    <path d="M5 12h14"></path>
                </svg>
                Nuevo Equipo
            </a>
        </div>

        <div class="equipment-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input id="equipmentSearch" type="search" placeholder="Buscar por nombre, codigo o categoria..." autocomplete="off">
        </div>

        <div class="equipment-table-panel">
            <div class="equipment-table-wrap">
                <table class="equipment-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Imagen</th>
                            <th>Equipo</th>
                            <th>Tipo</th>
                            <th>Ubicacion</th>
                            <th>Responsable</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="equipmentBody">
                        @foreach ($equipmentRows as $equipment)
                            <tr data-search="{{ strtolower($equipment['code'].' '.$equipment['name'].' '.$equipment['type'].' '.$equipment['location'].' '.$equipment['owner'].' '.$equipment['status']) }}">
                                <td>{{ $equipment['code'] }}</td>
                                <td>
                                    <span class="equipment-thumb" aria-label="Imagen de {{ $equipment['name'] }}">
                                        @include('structure.gestion_Inventario.equipos.partials.equipment-thumb', ['type' => $equipment['thumb']])
                                    </span>
                                </td>
                                <td>{{ $equipment['name'] }}</td>
                                <td>{{ $equipment['type'] }}</td>
                                <td>{{ $equipment['location'] }}</td>
                                <td>{{ $equipment['owner'] }}</td>
                                <td><span class="equipment-state {{ $equipment['tone'] }}">{{ $equipment['status'] }}</span></td>
                                <td>
                                    <div class="equipment-action-menu" data-equipment-action-menu>
                                        <button class="equipment-action" type="button" aria-label="Acciones de {{ $equipment['code'] }}" aria-haspopup="true" aria-expanded="false" data-equipment-action-toggle>
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <circle cx="12" cy="5" r="1.8"></circle>
                                                <circle cx="12" cy="12" r="1.8"></circle>
                                                <circle cx="12" cy="19" r="1.8"></circle>
                                            </svg>
                                        </button>

                                        <div class="equipment-action-list" role="menu">
                                            <a href="{{ route('inventory.equipos.show', ['equipo' => $equipment['code']]) }}" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                Ver detalle
                                            </a>
                                            <a href="{{ route('inventory.equipos.edit', ['equipo' => $equipment['code']]) }}" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M12 20h9"></path>
                                                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>
                                                </svg>
                                                Editar
                                            </a>
                                            <button type="button" role="menuitem" data-equipment-action-message="Asignacion de responsable pendiente de conectar.">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M20 21a8 8 0 0 0-16 0"></path>
                                                    <circle cx="12" cy="7" r="4"></circle>
                                                </svg>
                                                Asignar responsable
                                            </button>
                                            <button type="button" role="menuitem" data-equipment-action-message="Cambio de ubicacion pendiente de conectar.">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0z"></path>
                                                    <circle cx="12" cy="10" r="3"></circle>
                                                </svg>
                                                Cambiar ubicacion
                                            </button>
                                            <button type="button" role="menuitem" data-equipment-action-message="Registro de mantenimiento pendiente de conectar.">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.1-3.1a6 6 0 0 1-7.8 7.8l-5.7 5.7a2.1 2.1 0 0 1-3-3l5.7-5.7a6 6 0 0 1 7.8-7.8l-3.1 3.1z"></path>
                                                </svg>
                                                Registrar mantenimiento
                                            </button>
                                            <button type="button" class="equipment-action-danger" role="menuitem" data-equipment-action-message="Eliminacion de equipo pendiente de confirmar.">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M3 6h18"></path>
                                                    <path d="M8 6V4h8v2"></path>
                                                    <path d="M19 6l-1 14H6L5 6"></path>
                                                    <path d="M10 11v5"></path>
                                                    <path d="M14 11v5"></path>
                                                </svg>
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="equipment-foot">
                <span id="equipmentCount">Mostrando {{ count($equipmentRows) ? 1 : 0 }} a {{ count($equipmentRows) }} de {{ count($equipmentRows) }} resultados</span>
                <button type="button">Ver mas &gt;</button>
            </div>
        </div>
    </section>

    <script>
        const equipmentSearch = document.getElementById('equipmentSearch');
        const equipmentRows = Array.from(document.querySelectorAll('#equipmentBody tr'));
        const equipmentCount = document.getElementById('equipmentCount');

        equipmentSearch.addEventListener('input', () => {
            const query = equipmentSearch.value.trim().toLowerCase();
            let visible = 0;

            equipmentRows.forEach((row) => {
                const show = !query || row.dataset.search.includes(query);
                row.style.display = show ? '' : 'none';
                if (show) visible += 1;
            });

            equipmentCount.textContent = visible === 0
                ? 'Sin resultados'
                : 'Mostrando 1 a ' + visible + ' de 25 resultados';
        });

        document.addEventListener('click', (event) => {
            const toggle = event.target.closest('[data-equipment-action-toggle]');
            const actionButton = event.target.closest('[data-equipment-action-message]');

            if (actionButton && window.showToast) {
                window.showToast(actionButton.dataset.equipmentActionMessage);
            }

            document.querySelectorAll('[data-equipment-action-menu]').forEach((menu) => {
                if (!toggle || menu !== toggle.closest('[data-equipment-action-menu]')) {
                    menu.classList.remove('is-open');
                    const button = menu.querySelector('[data-equipment-action-toggle]');
                    if (button) {
                        button.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            if (!toggle) {
                return;
            }

            const menu = toggle.closest('[data-equipment-action-menu]');
            const isOpen = menu.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('[data-equipment-action-menu]').forEach((menu) => {
                menu.classList.remove('is-open');
                const button = menu.querySelector('[data-equipment-action-toggle]');
                if (button) {
                    button.setAttribute('aria-expanded', 'false');
                }
            });
        });
    </script>
@endsection

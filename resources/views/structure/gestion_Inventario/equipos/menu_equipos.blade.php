@extends('layouts.dashboard')

@section('title', 'Equipos')
@section('page-title', 'Equipos')
@section('page-sub', 'Gestion de Inventario > Equipos')

@php
    $equipmentRows = [
        ['code' => 'PRO-0001', 'name' => 'Torre de endoscopia', 'type' => 'Endoscopia', 'location' => 'Quirofano 1', 'owner' => 'Ing. Joel Diaz', 'status' => 'Bueno', 'tone' => 'green', 'thumb' => 'tower'],
        ['code' => 'PRO-0002', 'name' => 'Torre de endoscopia', 'type' => 'Endoscopia', 'location' => 'Quirofano 1', 'owner' => 'Ing. Joel Diaz', 'status' => 'Mantenimiento', 'tone' => 'blue', 'thumb' => 'monitor'],
        ['code' => 'PRO-0003', 'name' => 'Torre de endoscopia', 'type' => 'Endoscopia', 'location' => 'Quirofano 1', 'owner' => 'Ing. Joel Diaz', 'status' => 'Mantenimiento', 'tone' => 'blue', 'thumb' => 'stack'],
        ['code' => 'PRO-0004', 'name' => 'Torre de endoscopia', 'type' => 'Endoscopia', 'location' => 'Quirofano 1', 'owner' => 'Ing. Joel Diaz', 'status' => 'Malo', 'tone' => 'red', 'thumb' => 'scope'],
        ['code' => 'PRO-0005', 'name' => 'Torre de endoscopia', 'type' => 'Endoscopia', 'location' => 'Quirofano 1', 'owner' => 'Ing. Joel Diaz', 'status' => 'Bueno', 'tone' => 'green', 'thumb' => 'cart'],
        ['code' => 'PRO-0006', 'name' => 'Torre de endoscopia', 'type' => 'Endoscopia', 'location' => 'Quirofano 1', 'owner' => 'Ing. Joel Diaz', 'status' => 'Malo', 'tone' => 'red', 'thumb' => 'unit'],
    ];
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
                <x-gravityui-plus />
                Nuevo Equipo
            </a>
        </div>

        <div class="equipment-search">
            <x-gravityui-magnifier />
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
                                            <x-gravityui-ellipsis-vertical />
                                        </button>

                                        <div class="equipment-action-list" role="menu">
                                            <a href="{{ route('inventory.equipos.show', ['equipo' => $equipment['code']]) }}" role="menuitem">
                                                <x-gravityui-eye />
                                                Ver detalle
                                            </a>
                                            <a href="{{ route('inventory.equipos.edit', ['equipo' => $equipment['code']]) }}" role="menuitem">
                                                <x-gravityui-pencil />
                                                Editar
                                            </a>
                                            <button type="button" role="menuitem" data-equipment-action-message="Asignacion de responsable pendiente de conectar.">
                                                <x-gravityui-person />
                                                Asignar responsable
                                            </button>
                                            <button type="button" role="menuitem" data-equipment-action-message="Cambio de ubicacion pendiente de conectar.">
                                                <x-gravityui-map-pin />
                                                Cambiar ubicacion
                                            </button>
                                            <button type="button" role="menuitem" data-equipment-action-message="Registro de mantenimiento pendiente de conectar.">
                                                <x-gravityui-wrench />
                                                Registrar mantenimiento
                                            </button>
                                            <button type="button" class="equipment-action-danger" role="menuitem" data-equipment-action-message="Eliminacion de equipo pendiente de confirmar.">
                                                <x-gravityui-trash-bin />
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
                <span id="equipmentCount">Mostrando 1 a {{ count($equipmentRows) }} de 25 resultados</span>
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

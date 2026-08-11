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
    .equipment-search {
        position: relative;
        margin-bottom: 18px;
    }
    .equipment-search svg {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--muted);
        width: 18px;
        height: 18px;
    }
    .equipment-search input {
        width: 100%;
        padding: 11px 14px 11px 42px;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: var(--surface);
        color: var(--text);
        font: inherit;
        font-size: 15px;
    }
    .equipment-search input:focus {
        outline: none;
        border-color: #7c3aed;
    }
    .equipment-state {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 70px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }
    .equipment-state.green { color: #16a329; border: 1px solid #22c943; background: #f7fff8; }
    .equipment-state.blue { color: #1689ff; border: 1px solid #1689ff; background: #f5fbff; }
    .equipment-state.red { color: #ff3131; border: 1px solid #ff4b4b; background: #fff8f8; }
    .equipment-action-menu { position: relative; display: inline-flex; }
    .equipment-action-list {
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        min-width: 210px;
        padding: 6px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--surface);
        box-shadow: var(--shadow);
        display: none;
        z-index: 20;
    }
    .equipment-action-menu.is-open .equipment-action-list { display: grid; gap: 3px; }
    .equipment-action-list a, .equipment-action-list button {
        display: flex; align-items: center; gap: 8px;
        width: 100%; min-height: 34px; padding: 0 10px;
        border: 0; border-radius: 7px; background: transparent;
        color: var(--text); text-decoration: none; font-size: 13px; font-weight: 700;
        text-align: left; white-space: nowrap; cursor: pointer;
    }
    .equipment-action-list a:hover, .equipment-action-list button:hover { background: #eef4ff; color: #0879d0; }
    .equipment-action-list .danger { color: #ef4444; }
    .equipment-action-list .danger:hover { background: #fff1f2; color: #dc2626; }
    .equipment-foot { display: flex; align-items: center; justify-content: space-between; padding-top: 14px; color: var(--muted); font-size: 13px; font-weight: 600; }
</style>
@endpush

@section('content')
    <div style="display:flex; justify-content:flex-end; margin-bottom:18px;">
        <a href="{{ route('inventory.equipos.create') }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo Equipo
        </a>
    </div>

    <x-ui.card>
        <div class="equipment-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input id="equipmentSearch" type="search" placeholder="Buscar por nombre, codigo o categoria..." autocomplete="off">
        </div>

        <div style="overflow-x:auto;">
            <table>
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
                    @forelse ($equipmentRows as $equipment)
                        <tr data-search="{{ strtolower($equipment['code'].' '.$equipment['name'].' '.$equipment['type'].' '.$equipment['location'].' '.$equipment['owner'].' '.$equipment['status']) }}">
                            <td style="font-weight:700;">{{ $equipment['code'] }}</td>
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
                                    <button type="button" class="btn btn--ghost" style="padding:6px;" aria-haspopup="true" aria-expanded="false" data-equipment-action-toggle>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <circle cx="12" cy="5" r="1.8"></circle>
                                            <circle cx="12" cy="12" r="1.8"></circle>
                                            <circle cx="12" cy="19" r="1.8"></circle>
                                        </svg>
                                    </button>

                                    <div class="equipment-action-list" role="menu">
                                        <a href="{{ route('inventory.equipos.show', ['equipo' => $equipment['code']]) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                            Ver detalle
                                        </a>
                                        <a href="{{ route('inventory.equipos.edit', ['equipo' => $equipment['code']]) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                                            Editar
                                        </a>
                                        <button type="button" role="menuitem" data-equipment-action-message="Asignacion de responsable pendiente de conectar.">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                                            Asignar responsable
                                        </button>
                                        <button type="button" role="menuitem" data-equipment-action-message="Cambio de ubicacion pendiente de conectar.">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                            Cambiar ubicacion
                                        </button>
                                        <button type="button" role="menuitem" data-equipment-action-message="Registro de mantenimiento pendiente de conectar.">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.1-3.1a6 6 0 0 1-7.8 7.8l-5.7 5.7a2.1 2.1 0 0 1-3-3l5.7-5.7a6 6 0 0 1 7.8-7.8l-3.1 3.1z"/></svg>
                                            Registrar mantenimiento
                                        </button>
                                        <button type="button" class="danger" role="menuitem" data-equipment-action-message="Eliminacion de equipo pendiente de confirmar.">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5"/><path d="M14 11v5"/></svg>
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center; padding:32px; color:var(--muted);">
                                No hay equipos registrados. Agrega uno para gestionar el inventario.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="equipment-foot">
            <span id="equipmentCount">Mostrando {{ count($equipmentRows) ? 1 : 0 }} a {{ count($equipmentRows) }} de {{ count($equipmentRows) }} resultados</span>
            <button type="button" class="btn btn--ghost" style="font-size:13px;">Ver mas &gt;</button>
        </div>
    </x-ui.card>

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
                    if (button) button.setAttribute('aria-expanded', 'false');
                }
            });

            if (!toggle) return;

            const menu = toggle.closest('[data-equipment-action-menu]');
            const isOpen = menu.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;

            document.querySelectorAll('[data-equipment-action-menu]').forEach((menu) => {
                menu.classList.remove('is-open');
                const button = menu.querySelector('[data-equipment-action-toggle]');
                if (button) button.setAttribute('aria-expanded', 'false');
            });
        });
    </script>
@endsection
